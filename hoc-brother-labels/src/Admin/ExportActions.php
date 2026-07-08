<?php
/**
 * Adds an "Export Labels CSV" row action and bulk action to the orders list,
 * letting an admin download normalized label fields as CSV for use with
 * external mail-merge / database-connect label design tools (e.g. Brother
 * P-touch Editor) when bench-testing a printer template directly against
 * the QL-700, without going through the print microservice.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Admin;

use HOC\BrotherLabels\Labels\CsvExporter;
use HOC\BrotherLabels\Support\Capability;

defined( 'ABSPATH' ) || exit;

/**
 * Class ExportActions
 */
class ExportActions {

	/**
	 * Admin-post action name for single-order CSV export.
	 *
	 * @var string
	 */
	public const ACTION = 'hoc_bl_export_csv';

	/**
	 * Bulk action slug.
	 *
	 * @var string
	 */
	public const BULK_ACTION = 'hoc_bl_export_csv_labels';

	/**
	 * Nonce action name for single-order export.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'hoc_bl_export_csv_nonce';

	/**
	 * CSV exporter dependency.
	 *
	 * @var CsvExporter
	 */
	private $csv_exporter;

	/**
	 * Constructor.
	 *
	 * @param CsvExporter $csv_exporter CSV exporter.
	 */
	public function __construct( CsvExporter $csv_exporter ) {
		$this->csv_exporter = $csv_exporter;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_order_list_action' ), 25, 2 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_export_request' ) );

		$screens = array( 'edit-shop_order', 'woocommerce_page_wc-orders' );

		foreach ( $screens as $screen ) {
			add_filter( "bulk_actions-{$screen}", array( $this, 'register_bulk_action' ) );
			add_filter( "handle_bulk_actions-{$screen}", array( $this, 'handle_bulk_action' ), 10, 3 );
		}
	}

	/**
	 * Adds an "Export Labels CSV" button to the WooCommerce orders list row actions.
	 *
	 * @param array<string,mixed> $actions Existing row actions.
	 * @param \WC_Order            $order   Order object.
	 * @return array<string,mixed>
	 */
	public function add_order_list_action( $actions, $order ) {
		if ( ! $order instanceof \WC_Order || ! Capability::current_user_can_print() ) {
			return $actions;
		}

		$actions['hoc_bl_export_csv'] = array(
			'url'    => $this->build_export_url( $order->get_id() ),
			'name'   => __( 'Export Labels CSV', 'hoc-brother-labels' ),
			'action' => 'hoc_bl_export_csv',
		);

		return $actions;
	}

	/**
	 * Builds the nonce-protected admin-post URL for exporting a single order's CSV.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	public function build_export_url( $order_id ) {
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
	 * Registers the bulk action option.
	 *
	 * @param array<string,string> $bulk_actions Existing bulk actions.
	 * @return array<string,string>
	 */
	public function register_bulk_action( $bulk_actions ) {
		$bulk_actions[ self::BULK_ACTION ] = __( 'Export Labels CSV', 'hoc-brother-labels' );

		return $bulk_actions;
	}

	/**
	 * Handles the bulk CSV export by streaming the file directly and exiting.
	 *
	 * WordPress verifies the bulk-orders nonce before firing this filter, so
	 * no additional nonce check is needed here.
	 *
	 * @param string         $redirect_to Redirect URL (returned unchanged on mismatch).
	 * @param string         $action_name Bulk action name being handled.
	 * @param array<int,int> $order_ids   Selected order IDs.
	 * @return string
	 */
	public function handle_bulk_action( $redirect_to, $action_name, $order_ids ) {
		if ( self::BULK_ACTION !== $action_name ) {
			return $redirect_to;
		}

		if ( ! Capability::current_user_can_print() ) {
			wp_die( esc_html__( 'You do not have permission to export labels.', 'hoc-brother-labels' ) );
		}

		$order_ids = array_map( 'absint', (array) $order_ids );
		$orders    = array_filter( array_map( 'wc_get_order', $order_ids ) );
		$filename  = 'hoc-labels-export-bulk-' . gmdate( 'Ymd-His' ) . '.csv';
		$rows      = $this->csv_exporter->build_rows_for_orders( $orders );

		$this->csv_exporter->stream_csv( $rows, $filename );
		exit;
	}

	/**
	 * Handles the admin-post request and streams the CSV download for a
	 * single order (triggered via the row action link).
	 *
	 * @return void
	 */
	public function handle_export_request() {
		if ( ! Capability::current_user_can_print() ) {
			wp_die( esc_html__( 'You do not have permission to export labels.', 'hoc-brother-labels' ) );
		}

		if ( ! isset( $_GET['order_id'] ) ) {
			wp_die( esc_html__( 'No order specified for export.', 'hoc-brother-labels' ) );
		}

		$order_id = absint( wp_unslash( $_GET['order_id'] ) );

		check_admin_referer( self::NONCE_ACTION . '_' . $order_id );

		$order    = wc_get_order( $order_id );
		$orders   = $order instanceof \WC_Order ? array( $order ) : array();
		$filename = 'hoc-labels-export-order-' . $order_id . '.csv';
		$rows     = $this->csv_exporter->build_rows_for_orders( $orders );

		$this->csv_exporter->stream_csv( $rows, $filename );
		exit;
	}
}
