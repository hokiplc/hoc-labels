<?php
/**
 * Sanitization helpers for plugin settings and request data.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sanitizer
 */
class Sanitizer {

	/**
	 * Sanitizes the full settings array submitted via the Settings API.
	 *
	 * @param array<string,mixed> $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( array $input ) {
		$defaults = Options::defaults();
		$clean    = array();

		$clean['print_service_base_url'] = isset( $input['print_service_base_url'] )
			? sanitize_url( trim( (string) $input['print_service_base_url'] ) )
			: $defaults['print_service_base_url'];

		$clean['api_token'] = isset( $input['api_token'] )
			? sanitize_text_field( (string) $input['api_token'] )
			: $defaults['api_token'];

		$clean['printer_model'] = isset( $input['printer_model'] )
			? sanitize_text_field( (string) $input['printer_model'] )
			: $defaults['printer_model'];

		$clean['label_width_mm'] = isset( $input['label_width_mm'] )
			? absint( $input['label_width_mm'] )
			: $defaults['label_width_mm'];

		$clean['label_template'] = isset( $input['label_template'] )
			? sanitize_key( str_replace( '-', '_', (string) $input['label_template'] ) )
			: $defaults['label_template'];
		// Template names use hyphens by convention; restore after sanitize_key.
		$clean['label_template'] = str_replace( '_', '-', $clean['label_template'] );

		$clean['copies_per_item_default'] = isset( $input['copies_per_item_default'] )
			? max( 1, absint( $input['copies_per_item_default'] ) )
			: $defaults['copies_per_item_default'];

		$clean['copies_mode'] = isset( $input['copies_mode'] ) && in_array( $input['copies_mode'], array( 'fixed', 'quantity' ), true )
			? $input['copies_mode']
			: $defaults['copies_mode'];

		$valid_best_before_modes = array( 'roast_date', 'completed_date', 'today' );
		$clean['best_before_mode'] = isset( $input['best_before_mode'] ) && in_array( $input['best_before_mode'], $valid_best_before_modes, true )
			? $input['best_before_mode']
			: $defaults['best_before_mode'];

		$clean['shelf_life_days'] = isset( $input['shelf_life_days'] )
			? absint( $input['shelf_life_days'] )
			: $defaults['shelf_life_days'];

		foreach ( array( 'meta_key_grind', 'meta_key_weight', 'meta_key_strength', 'meta_key_flavour', 'meta_key_roast', 'meta_key_roast_date', 'meta_key_best_before' ) as $meta_key_field ) {
			$clean[ $meta_key_field ] = isset( $input[ $meta_key_field ] )
				? sanitize_text_field( (string) $input[ $meta_key_field ] )
				: $defaults[ $meta_key_field ];
		}

		$clean['debug_logging']      = self::sanitize_checkbox( $input, 'debug_logging' );
		$clean['auto_print_enabled'] = self::sanitize_checkbox( $input, 'auto_print_enabled' );

		$clean['auto_print_order_status'] = isset( $input['auto_print_order_status'] )
			? sanitize_key( (string) $input['auto_print_order_status'] )
			: $defaults['auto_print_order_status'];

		$clean['print_jobs_endpoint_path'] = isset( $input['print_jobs_endpoint_path'] )
			? '/' . ltrim( sanitize_text_field( (string) $input['print_jobs_endpoint_path'] ), '/' )
			: $defaults['print_jobs_endpoint_path'];

		$clean['request_timeout_seconds'] = isset( $input['request_timeout_seconds'] )
			? max( 1, absint( $input['request_timeout_seconds'] ) )
			: $defaults['request_timeout_seconds'];

		return $clean;
	}

	/**
	 * Normalizes a checkbox field to 'yes'/'no'.
	 *
	 * @param array<string,mixed> $input Raw input.
	 * @param string               $key   Field key.
	 * @return string
	 */
	private static function sanitize_checkbox( array $input, $key ) {
		return ! empty( $input[ $key ] ) ? 'yes' : 'no';
	}
}
