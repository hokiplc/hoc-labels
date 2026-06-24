<?php
/**
 * Settings/options helper.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Class Options
 *
 * Centralised access to the single plugin settings array.
 */
class Options {

	/**
	 * Option key used to store all settings.
	 *
	 * @var string
	 */
	public const OPTION_KEY = 'hoc_brother_labels_settings';

	/**
	 * Returns default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'print_service_base_url'  => '',
			'api_token'                => '',
			'printer_model'            => 'ql-700',
			'label_width_mm'           => 62,
			'label_template'           => 'house-of-coffee-62mm',
			'copies_per_item_default'  => 1,
			'copies_mode'              => 'fixed', // fixed|quantity.
			'best_before_mode'         => 'today', // roast_date|completed_date|today.
			'shelf_life_days'          => 365,
			'meta_key_grind'           => '_hoc_grind',
			'meta_key_weight'          => '_hoc_weight',
			'meta_key_strength'        => '_hoc_strength',
			'meta_key_flavour'         => '_hoc_flavour',
			'meta_key_roast'           => '_hoc_roast',
			'meta_key_roast_date'      => '_hoc_roast_date',
			'meta_key_best_before'     => '_hoc_best_before',
			'debug_logging'            => 'no',
			'auto_print_enabled'       => 'no',
			'auto_print_order_status'  => 'completed',
			'print_jobs_endpoint_path' => '/print-jobs',
			'request_timeout_seconds'  => 10,
		);
	}

	/**
	 * Returns all settings merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function all() {
		$saved = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Returns a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Fallback value if not present.
	 * @return mixed
	 */
	public static function get( $key, $fallback = '' ) {
		$all = self::all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Persists the full settings array.
	 *
	 * @param array<string,mixed> $settings Settings to save.
	 * @return void
	 */
	public static function update( array $settings ) {
		update_option( self::OPTION_KEY, $settings );
	}

	/**
	 * Seeds default options on activation if none exist yet.
	 *
	 * @return void
	 */
	public static function set_defaults_if_missing() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			update_option( self::OPTION_KEY, self::defaults() );
		}
	}
}
