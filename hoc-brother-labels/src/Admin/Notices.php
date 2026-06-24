<?php
/**
 * Displays admin notices after print actions, driven by query args.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Notices
 */
class Notices {

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	/**
	 * Renders admin notices based on query args set by redirects.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! isset( $_GET['hoc_bl_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only, no state change.
			return;
		}

		$notice = sanitize_key( wp_unslash( $_GET['hoc_bl_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$succeeded = isset( $_GET['hoc_bl_success'] ) ? absint( wp_unslash( $_GET['hoc_bl_success'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$failed    = isset( $_GET['hoc_bl_failed'] ) ? absint( wp_unslash( $_GET['hoc_bl_failed'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orders    = isset( $_GET['hoc_bl_orders'] ) ? absint( wp_unslash( $_GET['hoc_bl_orders'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		switch ( $notice ) {
			case 'print_single':
				$this->render_single_notice( $succeeded, $failed );
				break;
			case 'print_bulk':
				$this->render_bulk_notice( $orders, $succeeded, $failed );
				break;
			default:
				break;
		}
	}

	/**
	 * Renders the notice for a single-order print action.
	 *
	 * @param int $succeeded Number of labels printed successfully.
	 * @param int $failed    Number of labels that failed to print.
	 * @return void
	 */
	private function render_single_notice( $succeeded, $failed ) {
		$class = ( 0 === $failed ) ? 'notice-success' : 'notice-warning';

		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html(
				sprintf(
					/* translators: 1: number of successful labels, 2: number of failed labels. */
					__( 'HoC Labels: %1$d label(s) sent successfully, %2$d failed.', 'hoc-brother-labels' ),
					$succeeded,
					$failed
				)
			)
		);
	}

	/**
	 * Renders the notice for a bulk print action.
	 *
	 * @param int $orders    Number of orders processed.
	 * @param int $succeeded Number of labels printed successfully.
	 * @param int $failed    Number of labels that failed to print.
	 * @return void
	 */
	private function render_bulk_notice( $orders, $succeeded, $failed ) {
		$class = ( 0 === $failed ) ? 'notice-success' : 'notice-warning';

		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html(
				sprintf(
					/* translators: 1: number of orders, 2: number of successful labels, 3: number of failed labels. */
					__( 'HoC Labels: processed %1$d order(s). %2$d label(s) sent successfully, %3$d failed.', 'hoc-brother-labels' ),
					$orders,
					$succeeded,
					$failed
				)
			)
		);
	}
}
