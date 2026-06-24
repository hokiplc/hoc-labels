<?php
/**
 * PSR-4-style autoloader for the HOC\BrotherLabels namespace.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels;

defined( 'ABSPATH' ) || exit;

/**
 * Class Autoloader
 */
class Autoloader {

	/**
	 * Namespace prefix handled by this autoloader.
	 *
	 * @var string
	 */
	private const PREFIX = __NAMESPACE__ . '\\\\';

	/**
	 * Registers the autoloader with SPL.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::PREFIX ) );
		$relative_path  = str_replace( '\\\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';
		$file           = HOC_BROTHER_LABELS_PATH . 'src' . DIRECTORY_SEPARATOR . $relative_path;

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
}
