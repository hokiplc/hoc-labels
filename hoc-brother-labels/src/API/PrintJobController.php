<?php
/**
 * Orchestrates building and sending print jobs for an order.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\API;

use HOC\BrotherLabels\Labels\LabelBuilder;
use HOC\BrotherLabels\PrintService\Client;
use HOC\BrotherLabels\PrintService\Request;
use HOC\BrotherLabels\PrintService\Response;
use HOC\BrotherLabels\WooCommerce\OrderExtractor;

defined( 'ABSPATH' ) || exit;

/**
 * Class PrintJobController
 */
class PrintJobController {

	/**
	 * Order extractor dependency.
	 *
	 * @var OrderExtractor
	 */
	private $order_extractor;

	/**
	 * Label builder dependency.
	 *
	 * @var LabelBuilder
	 */
	private $label_builder;

	/**
	 * Print service client dependency.
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param OrderExtractor $order_extractor Order extractor.
	 * @param LabelBuilder   $label_builder   Label builder.
	 * @param Client         $client          Print service client.
	 */
	public function __construct( OrderExtractor $order_extractor, LabelBuilder $label_builder, Client $client ) {
		$this->order_extractor = $order_extractor;
		$this->label_builder    = $label_builder;
		$this->client           = $client;
	}

	/**
	 * Prints labels for every printable item on an order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array{success:int,failed:int,responses:array<int,array{job_ref:string,success:bool,message:string}>}
	 */
	public function print_order( \WC_Order $order ) {
		$items   = $this->order_extractor->get_printable_items( $order );
		$summary = array(
			'success'   => 0,
			'failed'    => 0,
			'responses' => array(),
		);

		foreach ( $items as $item ) {
			$response = $this->print_item( $order, $item );

			$summary[ $response->is_success() ? 'success' : 'failed' ]++;

			$summary['responses'][] = array(
				'job_ref' => $this->build_job_ref( $order, $item ),
				'success' => $response->is_success(),
				'message' => $response->get_message(),
			);
		}

		return $summary;
	}

	/**
	 * Prints a single line item label.
	 *
	 * @param \WC_Order              $order Order object.
	 * @param \WC_Order_Item_Product $item  Order line item.
	 * @return Response
	 */
	public function print_item( \WC_Order $order, \WC_Order_Item_Product $item ) {
		$payload = $this->label_builder->build_payload( $order, $item );
		$request = new Request( $payload );

		return $this->client->send( $request );
	}

	/**
	 * Builds a print job payload for preview purposes without sending it.
	 *
	 * @param \WC_Order              $order Order object.
	 * @param \WC_Order_Item_Product $item  Order line item.
	 * @return array<string,mixed>
	 */
	public function preview_item_payload( \WC_Order $order, \WC_Order_Item_Product $item ) {
		return $this->label_builder->build_payload( $order, $item );
	}

	/**
	 * Builds the job_ref string for an order/item pair.
	 *
	 * @param \WC_Order              $order Order object.
	 * @param \WC_Order_Item_Product $item  Order line item.
	 * @return string
	 */
	private function build_job_ref( \WC_Order $order, \WC_Order_Item_Product $item ) {
		return sprintf( 'order-%d-item-%d', $order->get_id(), $item->get_id() );
	}
}
