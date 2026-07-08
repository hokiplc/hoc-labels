<?php
/**
 * Plugin Name:       House of Coffee Brother Labels
 * Plugin URI:        https://hoki.com/plugins
 * Description:       Sends structured print jobs to an external Brother QL-700 label print service for House of Coffee bag labels (62mm continuous roll), based on WooCommerce order line items.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hoki Plugins
 * Author URI:        https://hoki.com/plugins
 * Text Domain:       hoc-brother-labels
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 6.0
 * WC tested up to:   9.0
 *
 * @package HOC\BrotherLabels
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'HOC_BROTHER_LABELS_VERSION', '1.0.0' );
define( 'HOC_BROTHER_LABELS_FILE', __FILE__ );
define( 'HOC_BROTHER_LABELS_PATH', plugin_dir_path( __FILE__ ) );
define( 'HOC_BROTHER_LABELS_URL', plugin_dir_url( __FILE__ ) );
define( 'HOC_BROTHER_LABELS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Declare WooCommerce HPOS (Custom Order Tables) compatibility.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				HOC_BROTHER_LABELS_FILE,
				true
			);
		}
	}
);

/**
 * Check WooCommerce dependency and bootstrap the plugin.
 */
add_action( 'plugins_loaded', 'hoc_brother_labels_bootstrap' );

/**
 * Bootstraps the plugin once all plugins are loaded.
 *
 * @return void
 */
function hoc_brother_labels_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			static function () {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'House of Coffee Brother Labels requires WooCommerce to be installed and active.', 'hoc-brother-labels' );
				echo '</p></div>';
			}
		);
		return;
	}

	require_once HOC_BROTHER_LABELS_PATH . 'src/Autoloader.php';
	\HOC\BrotherLabels\Autoloader::register();

	\HOC\BrotherLabels\Plugin::instance()->boot();
}

/**
 * Activation hook - nothing destructive, just ensures default options exist.
 */
register_activation_hook(
	HOC_BROTHER_LABELS_FILE,
	static function () {
		require_once HOC_BROTHER_LABELS_PATH . 'src/Autoloader.php';
		\HOC\BrotherLabels\Autoloader::register();
		\HOC\BrotherLabels\Support\Options::set_defaults_if_missing();
	}
);
