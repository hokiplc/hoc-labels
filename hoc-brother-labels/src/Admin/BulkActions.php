<?php
/**
 * Adds a "Print HoC Labels" bulk action to the WooCommerce orders list screens
 * (both legacy post-type screen and modern HPOS order screen).
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Admin;

use HOC\BrotherLabels\API\PrintJobController;
use HOC\BrotherLabels\Support\Capability;

defined( 'ABSPATH' ) || exit;

/**
 * Class BulkActions
 */
class BulkActions {

	/**
	 * Bulk action slug.
	 *
	 * @var string
	 */
	public const BULK_ACTION = 'hoc_bl_print_labels';

	/**
	 * Print job controller dependency.
	 *
	 * @var PrintJobController
	 */
	private $print_job_controller;

	/**
	 * Constructor.
	 *
	 * @param PrintJobController $print_job_controller Print job controller.
	 */
	public function __construct( PrintJobController $print_job_controller ) {
		$this->print_job_controller = $print_job_controller;
	}

	/**
	 * Registers hooks for both legacy (shop_order) and HPOS order list screens.
	 *
	 * @return void
	 */
	public function register() {
		$screens = array( 'edit-shop_order', 'woocommerce_page_wc-orders' );

		foreach ( $screens as $screen ) {
			add_filter( "bulk_actions-{$screen}", array( $this, 'register_bulk_action' ) );
			add_filter( "handle_bulk_actions-{$screen}", array( $this, 'handle_bulk_action' ), 10, 3 );
		}
	}

	/**
	 * Registers the bulk action option.
	 *
	 * @param array<string,string> $bulk_actions Existing bulk actions.
	 * @return array<string,string>
	 */
	public function register_bulk_action( $bulk_actions ) {
		$bulk_actions[ self::BULK_ACTION ] = __( 'Print HoC Labels', 'hoc-brother-labels' );

		return $bulk_actions;
	}

	/**
	 * Handles the bulk action and redirects with a result summary.
	 *
	 * @param string        $redirect_to Redirect URL.
	 * @param string        $action_name Bulk action name being handled.
	 * @param array<int,int> $order_ids   Selected order IDs.
	 * @return string
	 */
	public function handle_bulk_action( $redirect_to, $action_name, $order_ids ) {
		if ( self::BULK_ACTION !== $action_name ) {
			return $redirect_to;
		}

		if ( ! Capability::current_user_can_print() ) {
			wp_die( esc_html__( 'You do not have permission to print labels.', 'hoc-brother-labels' ) );
		}

		$orders_processed = 0;
		$succeeded         = 0;
		$failed            = 0;

		foreach ( (array) $order_ids as $order_id ) {
			$order = wc_get_order( absint( $order_id ) );

			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$summary = $this->print_job_controller->print_order( $order );

			$succeeded += $summary['success'];
			$failed    += $summary['failed'];
			++$orders_processed;
		}

		return add_query_arg(
			array(
				'hoc_bl_notice'  => 'print_bulk',
				'hoc_bl_orders'  => $orders_processed,
				'hoc_bl_success' => $succeeded,
				'hoc_bl_failed'  => $failed,
			),
			$redirect_to
		);
	}
}
