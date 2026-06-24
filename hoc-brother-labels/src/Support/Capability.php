<?php
/**
 * Capability checks for plugin actions.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Class Capability
 */
class Capability {

	/**
	 * Capability required to manage WooCommerce orders / print labels.
	 *
	 * @var string
	 */
	public const MANAGE_ORDERS = 'manage_woocommerce';

	/**
	 * Capability required to change plugin settings.
	 *
	 * @var string
	 */
	public const MANAGE_SETTINGS = 'manage_woocommerce';

	/**
	 * Whether the current user can print labels.
	 *
	 * @return bool
	 */
	public static function current_user_can_print() {
		return current_user_can( self::MANAGE_ORDERS );
	}

	/**
	 * Whether the current user can manage plugin settings.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage_settings() {
		return current_user_can( self::MANAGE_SETTINGS );
	}
}
