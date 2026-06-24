<?php
/**
 * Builds the final structured print job payload for one label.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Labels;

use HOC\BrotherLabels\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class LabelBuilder
 */
class LabelBuilder {

	/**
	 * Field mapper dependency.
	 *
	 * @var FieldMapper
	 */
	private $field_mapper;

	/**
	 * Constructor.
	 *
	 * @param FieldMapper $field_mapper Field mapper.
	 */
	public function __construct( FieldMapper $field_mapper ) {
		$this->field_mapper = $field_mapper;
	}

	/**
	 * Builds the structured print job payload for a single order line item.
	 *
	 * @param \WC_Order              $order Order object.
	 * @param \WC_Order_Item_Product $item  Order line item.
	 * @return array<string,mixed>
	 */
	public function build_payload( \WC_Order $order, \WC_Order_Item_Product $item ) {
		$field_map = $this->field_mapper->map( $order, $item );

		$copies  = isset( $field_map['copies'] ) ? max( 1, (int) $field_map['copies'] ) : 1;
		$job_ref = isset( $field_map['job_ref'] ) ? (string) $field_map['job_ref'] : sprintf( 'order-%d-item-%d', $order->get_id(), $item->get_id() );

		$data = $field_map;
		unset( $data['copies'], $data['job_ref'] );

		$payload = array(
			'template'       => (string) Options::get( 'label_template', 'house-of-coffee-62mm' ),
			'printer'        => (string) Options::get( 'printer_model', 'ql-700' ),
			'label_width_mm' => (int) Options::get( 'label_width_mm', 62 ),
			'copies'         => $copies,
			'job_ref'        => $job_ref,
			'data'           => $data,
		);

		/**
		 * Filters the final print job payload before it is sent to the external print service.
		 *
		 * @param array<string,mixed>   $payload The print job payload.
		 * @param \WC_Order              $order   The order.
		 * @param \WC_Order_Item_Product $item    The order line item.
		 */
		return apply_filters( 'hoc_brother_labels_payload', $payload, $order, $item );
	}
}
