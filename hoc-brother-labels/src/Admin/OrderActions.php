<?php
/**
 * Adds a "Print HoC Labels" row action to the orders list and handles the
 * resulting admin-post request for printing a single order.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Admin;

use HOC\BrotherLabels\API\PrintJobController;
use HOC\BrotherLabels\Support\Capability;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderActions
 */
class OrderActions {

	/**
	 * Admin-post action name for printing a single order.
	 *
	 * @var string
	 */
	public const ACTION = 'hoc_bl_print_order';

	/**
	 * Nonce action name.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'hoc_bl_print_order_nonce';

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
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_order_list_action' ), 20, 2 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_print_request' ) );
	}

	/**
	 * Adds a "Print HoC Labels" button to the WooCommerce orders list row actions.
	 *
	 * @param array<string,mixed> $actions Existing row actions.
	 * @param \WC_Order            $order   Order object.
	 * @return array<string,mixed>
	 */
	public function add_order_list_action( $actions, $order ) {
		if ( ! $order instanceof \WC_Order || ! Capability::current_user_can_print() ) {
			return $actions;
		}

		$url = $this->build_print_url( $order->get_id() );

		$actions['hoc_bl_print'] = array(
			'url'    => $url,
			'name'   => __( 'Print HoC Labels', 'hoc-brother-labels' ),
			'action' => 'hoc_bl_print preview',
		);

		return $actions;
	}

	/**
	 * Builds the nonce-protected admin-post URL for printing a given order.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public function build_print_url( $order_id ) {
		$url = add_query_arg(
			array(
				'action'   => self::ACTION,
				'order_id' => $order_id,
			),
			admin_url( 'admin-post.php' )
		);

		return wp_nonce_url( $url, self::NONCE_ACTION . '_' . $order_id );
	}

	/**
	 * Handles the admin-post request to print a single order's labels.
	 *
	 * @return void
	 */
	public function handle_print_request() {
		if ( ! Capability::current_user_can_print() ) {
			wp_die( esc_html__( 'You do not have permission to print labels.', 'hoc-brother-labels' ) );
		}

		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;

		check_admin_referer( self::NONCE_ACTION . '_' . $order_id );

		$order = $order_id ? wc_get_order( $order_id ) : false;

		$succeeded = 0;
		$failed    = 0;

		if ( $order instanceof \WC_Order ) {
			$summary   = $this->print_job_controller->print_order( $order );
			$succeeded = $summary['success'];
			$failed    = $summary['failed'];
		} else {
			$failed = 1;
		}

		$redirect_to = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-orders' );

		$redirect_to = add_query_arg(
			array(
				'hoc_bl_notice'  => 'print_single',
				'hoc_bl_success' => $succeeded,
				'hoc_bl_failed'  => $failed,
			),
			$redirect_to
		);

		wp_safe_redirect( $redirect_to );
		exit;
	}
}
