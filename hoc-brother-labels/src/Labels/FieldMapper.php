<?php
/**
 * Maps raw WooCommerce/order data into the normalized label field schema.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Labels;

use HOC\BrotherLabels\Support\Options;
use HOC\BrotherLabels\WooCommerce\ItemMetaResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Class FieldMapper
 */
class FieldMapper {

	/**
	 * Item meta resolver dependency.
	 *
	 * @var ItemMetaResolver
	 */
	private $item_meta_resolver;

	/**
	 * Best-before calculator dependency.
	 *
	 * @var BestBeforeCalculator
	 */
	private $best_before_calculator;

	/**
	 * Constructor.
	 *
	 * @param ItemMetaResolver     $item_meta_resolver     Item meta resolver.
	 * @param BestBeforeCalculator $best_before_calculator Best-before calculator.
	 */
	public function __construct( ItemMetaResolver $item_meta_resolver, BestBeforeCalculator $best_before_calculator ) {
		$this->item_meta_resolver     = $item_meta_resolver;
		$this->best_before_calculator = $best_before_calculator;
	}

	/**
	 * Builds the normalized field map for a single order line item.
	 *
	 * @param \WC_Order              $order Order object.
	 * @param \WC_Order_Item_Product $item  Order line item.
	 * @return array<string,mixed>
	 */
	public function map( \WC_Order $order, \WC_Order_Item_Product $item ) {
		$grind    = $this->item_meta_resolver->resolve( $item, 'grind' );
		$weight   = $this->item_meta_resolver->resolve( $item, 'weight' );
		$strength = $this->item_meta_resolver->resolve( $item, 'strength' );
		$flavour  = $this->item_meta_resolver->resolve( $item, 'flavour' );
		$roast    = $this->item_meta_resolver->resolve( $item, 'roast' );

		$roast_date           = $this->item_meta_resolver->resolve( $item, 'roast_date' );
		$explicit_best_before = $this->item_meta_resolver->resolve( $item, 'best_before' );

		$best_before = $this->best_before_calculator->calculate( $explicit_best_before, $roast_date, $order );

		$quantity = max( 1, (int) $item->get_quantity() );
		$copies   = ( 'quantity' === Options::get( 'copies_mode', 'fixed' ) )
			? $quantity
			: max( 1, (int) Options::get( 'copies_per_item_default', 1 ) );

		$field_map = array(
			'brand'        => $this->get_brand_name(),
			'product_name' => $this->format_product_name( $item->get_name() ),
			'grind'        => $this->format_value( $grind ),
			'weight'       => $this->format_value( $weight ),
			'strength'     => $this->format_value( $strength ),
			'flavour'      => $this->format_value( $flavour ),
			'roast'        => $this->format_value( $roast ),
			'best_before'  => $best_before,
			'copies'       => $copies,
			'job_ref'      => sprintf( 'order-%d-item-%d', $order->get_id(), $item->get_id() ),
		);

		/**
		 * Filters the normalized label field map before it is sent to the print template.
		 *
		 * @param array<string,mixed>   $field_map Normalized field map.
		 * @param \WC_Order              $order     The order.
		 * @param \WC_Order_Item_Product $item      The order line item.
		 */
		return apply_filters( 'hoc_brother_labels_field_map', $field_map, $order, $item );
	}

	/**
	 * Returns the configured brand name for the label.
	 *
	 * @return string
	 */
	private function get_brand_name() {
		/**
		 * Filters the brand name shown at the top of the label.
		 *
		 * @param string $brand_name Brand name.
		 */
		return apply_filters( 'hoc_brother_labels_brand_name', 'House of Coffee' );
	}

	/**
	 * Normalizes a product name for print (uppercase, trimmed).
	 *
	 * @param string $product_name Raw product name.
	 * @return string
	 */
	private function format_product_name( $product_name ) {
		return mb_strtoupper( trim( wp_strip_all_tags( (string) $product_name ) ) );
	}

	/**
	 * Trims and strips tags from a generic field value.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function format_value( $value ) {
		return trim( wp_strip_all_tags( (string) $value ) );
	}
}
