<?php
/**
 * Lightweight service container / bootstrap for the plugin.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels;

use HOC\BrotherLabels\Admin\BulkActions;
use HOC\BrotherLabels\Admin\Notices;
use HOC\BrotherLabels\Admin\OrderActions;
use HOC\BrotherLabels\Admin\OrderMetaBox;
use HOC\BrotherLabels\Admin\SettingsPage;
use HOC\BrotherLabels\API\PrintJobController;
use HOC\BrotherLabels\Labels\BestBeforeCalculator;
use HOC\BrotherLabels\Labels\FieldMapper;
use HOC\BrotherLabels\Labels\LabelBuilder;
use HOC\BrotherLabels\PrintService\Client;
use HOC\BrotherLabels\Support\Options;
use HOC\BrotherLabels\WooCommerce\ItemMetaResolver;
use HOC\BrotherLabels\WooCommerce\OrderExtractor;
use HOC\BrotherLabels\WooCommerce\ProductResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Print job controller, built once and reused.
	 *
	 * @var PrintJobController
	 */
	private $print_job_controller;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor; use instance().
	 */
	private function __construct() {}

	/**
	 * Boots the plugin: builds dependencies and registers WordPress hooks.
	 *
	 * @return void
	 */
	public function boot() {
		$this->print_job_controller = $this->build_print_job_controller();

		$this->register_admin_components();
		$this->register_auto_print_hook();
		$this->load_textdomain();
	}

	/**
	 * Builds the print job controller and its full dependency graph.
	 *
	 * @return PrintJobController
	 */
	private function build_print_job_controller() {
		$product_resolver   = new ProductResolver();
		$item_meta_resolver  = new ItemMetaResolver( $product_resolver );
		$best_before_calc    = new BestBeforeCalculator();
		$field_mapper         = new FieldMapper( $item_meta_resolver, $best_before_calc );
		$label_builder         = new LabelBuilder( $field_mapper );
		$order_extractor       = new OrderExtractor();
		$client                = new Client();

		return new PrintJobController( $order_extractor, $label_builder, $client );
	}

	/**
	 * Registers all admin-facing components.
	 *
	 * @return void
	 */
	private function register_admin_components() {
		if ( ! is_admin() ) {
			return;
		}

		( new SettingsPage() )->register();
		( new Notices() )->register();

		$order_actions = new OrderActions( $this->print_job_controller );
		$order_actions->register();

		( new BulkActions( $this->print_job_controller ) )->register();

		$order_extractor = new OrderExtractor();
		( new OrderMetaBox( $order_actions, $order_extractor ) )->register();
	}

	/**
	 * Registers the optional auto-print-on-status-change hook.
	 *
	 * @return void
	 */
	private function register_auto_print_hook() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_auto_print' ), 10, 4 );
	}

	/**
	 * Automatically prints labels when an order reaches the configured status.
	 *
	 * @param int           $order_id   Order ID.
	 * @param string        $status_from Previous status.
	 * @param string        $status_to   New status.
	 * @param \WC_Order|null $order      Order object.
	 * @return void
	 */
	public function maybe_auto_print( $order_id, $status_from, $status_to, $order = null ) {
		if ( 'yes' !== Options::get( 'auto_print_enabled', 'no' ) ) {
			return;
		}

		$target_status = (string) Options::get( 'auto_print_order_status', 'completed' );

		if ( $status_to !== $target_status ) {
			return;
		}

		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order instanceof \WC_Order ) {
			$this->print_job_controller->print_order( $order );
		}
	}

	/**
	 * Returns the print job controller, e.g. for use by other integrations.
	 *
	 * @return PrintJobController
	 */
	public function get_print_job_controller() {
		return $this->print_job_controller;
	}

	/**
	 * Loads the plugin text domain.
	 *
	 * @return void
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'hoc-brother-labels', false, dirname( HOC_BROTHER_LABELS_BASENAME ) . '/languages' );
	}
}
