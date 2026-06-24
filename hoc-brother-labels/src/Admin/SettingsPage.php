<?php
/**
 * Plugin settings page, registered under the WooCommerce admin menu.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Admin;

use HOC\BrotherLabels\Support\Capability;
use HOC\BrotherLabels\Support\Options;
use HOC\BrotherLabels\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsPage
 */
class SettingsPage {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	public const PAGE_SLUG = 'hoc-brother-labels-settings';

	/**
	 * Settings group name used with register_setting().
	 *
	 * @var string
	 */
	private const SETTINGS_GROUP = 'hoc_brother_labels_settings_group';

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Adds the settings page under the WooCommerce admin menu.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			__( 'HoC Brother Labels', 'hoc-brother-labels' ),
			__( 'HoC Brother Labels', 'hoc-brother-labels' ),
			Capability::MANAGE_SETTINGS,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueues admin assets on the plugin's settings page only.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( (string) $hook_suffix, self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'hoc-brother-labels-admin',
			HOC_BROTHER_LABELS_URL . 'assets/admin.css',
			array(),
			HOC_BROTHER_LABELS_VERSION
		);
	}

	/**
	 * Registers the setting, sections and fields via the Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			Options::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => Options::defaults(),
			)
		);

		add_settings_section(
			'hoc_bl_connection',
			__( 'Print Service Connection', 'hoc-brother-labels' ),
			array( $this, 'render_connection_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'hoc_bl_label',
			__( 'Label Defaults', 'hoc-brother-labels' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_section(
			'hoc_bl_best_before',
			__( 'Best Before Calculation', 'hoc-brother-labels' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_section(
			'hoc_bl_meta_keys',
			__( 'Meta Key Mappings', 'hoc-brother-labels' ),
			array( $this, 'render_meta_keys_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'hoc_bl_advanced',
			__( 'Advanced', 'hoc-brother-labels' ),
			'__return_false',
			self::PAGE_SLUG
		);

		$this->register_connection_fields();
		$this->register_label_fields();
		$this->register_best_before_fields();
		$this->register_meta_key_fields();
		$this->register_advanced_fields();
	}

	/**
	 * Sanitizes settings submitted via the Settings API.
	 *
	 * @param array<string,mixed> $input Raw input.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( $input ) {
		if ( ! Capability::current_user_can_manage_settings() ) {
			return Options::all();
		}

		return Sanitizer::sanitize_settings( is_array( $input ) ? $input : array() );
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! Capability::current_user_can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hoc-brother-labels' ) );
		}

		?>
		<div class="wrap hoc-bl-settings">
			<h1><?php esc_html_e( 'House of Coffee Brother Labels', 'hoc-brother-labels' ); ?></h1>

			<div class="hoc-bl-help-box">
				<h3><?php esc_html_e( 'How this plugin works', 'hoc-brother-labels' ); ?></h3>
				<p>
					<?php esc_html_e( 'This plugin never talks to the Brother QL-700 printer directly. Instead, it sends a structured JSON print job over HTTP to an external print microservice running on your local network (for example, a brother_ql_web/label_api-style service). Configure the base URL of that service below.', 'hoc-brother-labels' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Expected behaviour of the external service: it should accept an HTTP POST request at the configured endpoint path, with a JSON body describing the template, printer model, label width, copies, job reference and label data fields. It should respond with a 2xx status code on success.', 'hoc-brother-labels' ); ?>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the intro text for the connection section.
	 *
	 * @return void
	 */
	public function render_connection_section_intro() {
		echo '<p>' . esc_html__( 'Configure how this plugin reaches your external Brother QL print microservice.', 'hoc-brother-labels' ) . '</p>';
	}

	/**
	 * Renders the intro text for the meta key mappings section.
	 *
	 * @return void
	 */
	public function render_meta_keys_section_intro() {
		echo '<p>' . esc_html__( 'Map normalized label fields to the WooCommerce order item / product meta keys used in your catalog.', 'hoc-brother-labels' ) . '</p>';
	}

	/**
	 * Registers the connection-related fields.
	 *
	 * @return void
	 */
	private function register_connection_fields() {
		$this->add_text_field( 'print_service_base_url', __( 'Print Service Base URL', 'hoc-brother-labels' ), 'hoc_bl_connection', __( 'e.g. http://192.168.1.50:8013', 'hoc-brother-labels' ) );
		$this->add_text_field( 'print_jobs_endpoint_path', __( 'Print Jobs Endpoint Path', 'hoc-brother-labels' ), 'hoc_bl_connection' );
		$this->add_password_field( 'api_token', __( 'API Token / Shared Secret', 'hoc-brother-labels' ), 'hoc_bl_connection' );
		$this->add_number_field( 'request_timeout_seconds', __( 'Request Timeout (seconds)', 'hoc-brother-labels' ), 'hoc_bl_connection' );
	}

	/**
	 * Registers the label default fields.
	 *
	 * @return void
	 */
	private function register_label_fields() {
		$this->add_text_field( 'printer_model', __( 'Printer Model', 'hoc-brother-labels' ), 'hoc_bl_label' );
		$this->add_number_field( 'label_width_mm', __( 'Label Width (mm)', 'hoc-brother-labels' ), 'hoc_bl_label' );
		$this->add_text_field( 'label_template', __( 'Label Template Name', 'hoc-brother-labels' ), 'hoc_bl_label' );
		$this->add_number_field( 'copies_per_item_default', __( 'Copies Per Item (default)', 'hoc-brother-labels' ), 'hoc_bl_label' );
		$this->add_select_field(
			'copies_mode',
			__( 'Copies Mode', 'hoc-brother-labels' ),
			'hoc_bl_label',
			array(
				'fixed'    => __( 'Always use the default copies value above', 'hoc-brother-labels' ),
				'quantity' => __( 'Use the line item quantity as the number of copies', 'hoc-brother-labels' ),
			)
		);
	}

	/**
	 * Registers the best-before calculation fields.
	 *
	 * @return void
	 */
	private function register_best_before_fields() {
		$this->add_select_field(
			'best_before_mode',
			__( 'Best Before Fallback Mode', 'hoc-brother-labels' ),
			'hoc_bl_best_before',
			array(
				'roast_date'     => __( 'From roast date meta + shelf life', 'hoc-brother-labels' ),
				'completed_date' => __( 'From order completed date + shelf life', 'hoc-brother-labels' ),
				'today'          => __( 'From today + shelf life', 'hoc-brother-labels' ),
			)
		);
		$this->add_number_field( 'shelf_life_days', __( 'Shelf Life (days)', 'hoc-brother-labels' ), 'hoc_bl_best_before' );
	}

	/**
	 * Registers the meta key mapping fields.
	 *
	 * @return void
	 */
	private function register_meta_key_fields() {
		$this->add_text_field( 'meta_key_grind', __( 'Grind Meta Key', 'hoc-brother-labels' ), 'hoc_bl_meta_keys' );
		$this->add_text_field( 'meta_key_weight', __( 'Weight Meta Key', 'hoc-brother-labels' ), 'hoc_bl_meta_keys' );
		$this->add_text_field( 'meta_key_strength', __( 'Strength Meta Key', 'hoc-brother-labels' ), 'hoc_bl_meta_keys' );
		$this->add_text_field( 'meta_key_flavour', __( 'Flavour Meta Key', 'hoc-brother-labels' ), 'hoc_bl_meta_keys' );
		$this->add_text_field( 'meta_key_roast', __( 'Roast Meta Key', 'hoc-brother-labels' ), 'hoc_bl_meta_keys' );
		$this->add_text_field( 'meta_key_roast_date', __( 'Roast Date Meta Key', 'hoc-brother-labels' ), 'hoc_bl_meta_keys' );
		$this->add_text_field( 'meta_key_best_before', __( 'Best Before Meta Key', 'hoc-brother-labels' ), 'hoc_bl_meta_keys' );
	}

	/**
	 * Registers advanced fields (debug logging, auto-print).
	 *
	 * @return void
	 */
	private function register_advanced_fields() {
		$this->add_checkbox_field( 'debug_logging', __( 'Enable Debug Logging', 'hoc-brother-labels' ), 'hoc_bl_advanced', __( 'Logs requests/responses via the WooCommerce logger (Status > Logs).', 'hoc-brother-labels' ) );
		$this->add_checkbox_field( 'auto_print_enabled', __( 'Automatically Print on Order Status Change', 'hoc-brother-labels' ), 'hoc_bl_advanced' );

		add_settings_field(
			'auto_print_order_status',
			__( 'Auto-Print Order Status', 'hoc-brother-labels' ),
			array( $this, 'render_order_status_select' ),
			self::PAGE_SLUG,
			'hoc_bl_advanced'
		);
	}

	/**
	 * Renders a select field listing WooCommerce order statuses.
	 *
	 * @return void
	 */
	public function render_order_status_select() {
		$value    = Options::get( 'auto_print_order_status', 'completed' );
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();

		echo '<select name="' . esc_attr( Options::OPTION_KEY ) . '[auto_print_order_status]">';

		foreach ( $statuses as $status_key => $status_label ) {
			$clean_key = str_replace( 'wc-', '', $status_key );
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $clean_key ),
				selected( $value, $clean_key, false ),
				esc_html( $status_label )
			);
		}

		echo '</select>';
	}

	/**
	 * Adds a basic text input field.
	 *
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $section     Settings section ID.
	 * @param string $placeholder Optional placeholder text.
	 * @return void
	 */
	private function add_text_field( $key, $label, $section, $placeholder = '' ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $placeholder ) {
				printf(
					'<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" placeholder="%4$s" />',
					esc_attr( Options::OPTION_KEY ),
					esc_attr( $key ),
					esc_attr( (string) Options::get( $key, '' ) ),
					esc_attr( $placeholder )
				);
			},
			self::PAGE_SLUG,
			$section
		);
	}

	/**
	 * Adds a password input field (used for secrets/tokens).
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $section Settings section ID.
	 * @return void
	 */
	private function add_password_field( $key, $label, $section ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key ) {
				printf(
					'<input type="password" class="regular-text" autocomplete="new-password" name="%1$s[%2$s]" value="%3$s" />',
					esc_attr( Options::OPTION_KEY ),
					esc_attr( $key ),
					esc_attr( (string) Options::get( $key, '' ) )
				);
			},
			self::PAGE_SLUG,
			$section
		);
	}

	/**
	 * Adds a number input field.
	 *
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $section Settings section ID.
	 * @return void
	 */
	private function add_number_field( $key, $label, $section ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key ) {
				printf(
					'<input type="number" min="1" class="small-text" name="%1$s[%2$s]" value="%3$s" />',
					esc_attr( Options::OPTION_KEY ),
					esc_attr( $key ),
					esc_attr( (string) Options::get( $key, '' ) )
				);
			},
			self::PAGE_SLUG,
			$section
		);
	}

	/**
	 * Adds a select field with the given options.
	 *
	 * @param string                $key     Option key.
	 * @param string                $label   Field label.
	 * @param string                $section Settings section ID.
	 * @param array<string,string>  $options Options as value => label.
	 * @return void
	 */
	private function add_select_field( $key, $label, $section, array $options ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $options ) {
				$current_value = (string) Options::get( $key, '' );

				echo '<select name="' . esc_attr( Options::OPTION_KEY ) . '[' . esc_attr( $key ) . ']">';

				foreach ( $options as $value => $option_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $value ),
						selected( $current_value, $value, false ),
						esc_html( $option_label )
					);
				}

				echo '</select>';
			},
			self::PAGE_SLUG,
			$section
		);
	}

	/**
	 * Adds a checkbox field.
	 *
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $section     Settings section ID.
	 * @param string $description Optional description shown beside the checkbox.
	 * @return void
	 */
	private function add_checkbox_field( $key, $label, $section, $description = '' ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $description ) {
				$checked = ( 'yes' === Options::get( $key, 'no' ) );

				printf(
					'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
					esc_attr( Options::OPTION_KEY ),
					esc_attr( $key ),
					checked( $checked, true, false ),
					esc_html( $description )
				);
			},
			self::PAGE_SLUG,
			$section
		);
	}
}
