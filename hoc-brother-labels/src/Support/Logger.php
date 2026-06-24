<?php
/**
 * Logging helper that prefers the WooCommerce logger when available.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 */
class Logger {

	/**
	 * Log source/context name used by WC_Logger.
	 *
	 * @var string
	 */
	private const SOURCE = 'hoc-brother-labels';

	/**
	 * Logs an informational message when debug logging is enabled.
	 *
	 * @param string               $message Message to log.
	 * @param array<string,mixed>  $context Additional context (never include secrets).
	 * @return void
	 */
	public static function info( $message, array $context = array() ) {
		self::log( 'info', $message, $context );
	}

	/**
	 * Logs an error message regardless of the debug logging setting.
	 *
	 * @param string               $message Message to log.
	 * @param array<string,mixed>  $context Additional context (never include secrets).
	 * @return void
	 */
	public static function error( $message, array $context = array() ) {
		self::log( 'error', $message, $context, true );
	}

	/**
	 * Core logging routine.
	 *
	 * @param string               $level        Log level.
	 * @param string               $message      Message to log.
	 * @param array<string,mixed>  $context      Additional context.
	 * @param bool                 $force_log    Whether to log even if debug logging is disabled.
	 * @return void
	 */
	private static function log( $level, $message, array $context, $force_log = false ) {
		$debug_enabled = 'yes' === Options::get( 'debug_logging', 'no' );

		if ( ! $debug_enabled && ! $force_log ) {
			return;
		}

		$line = sprintf( '[HOC Brother Labels] %s', $message );

		if ( ! empty( $context ) ) {
			$line .= ' ' . wp_json_encode( $context );
		}

		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->log( $level, $line, array( 'source' => self::SOURCE ) );
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional fallback when WC logger unavailable and debug enabled.
		error_log( $line );
	}
}
