<?php
/**
 * Resolves a single normalized field for an order line item using a fallback chain.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\WooCommerce;

use HOC\BrotherLabels\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class ItemMetaResolver
 *
 * Resolution order:
 * 1. Order item meta.
 * 2. Variation attribute / variation meta.
 * 3. Product meta.
 * 4. Empty string fallback.
 */
class ItemMetaResolver {

	/**
	 * Product resolver dependency.
	 *
	 * @var ProductResolver
	 */
	private $product_resolver;

	/**
	 * Constructor.
	 *
	 * @param ProductResolver $product_resolver Product resolver.
	 */
	public function __construct( ProductResolver $product_resolver ) {
		$this->product_resolver = $product_resolver;
	}

	/**
	 * Resolves a normalized field (e.g. "grind") for a given order item.
	 *
	 * @param \WC_Order_Item_Product $item       Order line item.
	 * @param string                 $field_name Normalized field name: grind|weight|strength|flavour|roast|roast_date|best_before.
	 * @return string
	 */
	public function resolve( \WC_Order_Item_Product $item, $field_name ) {
		$meta_key = (string) Options::get( 'meta_key_' . $field_name, '' );

		// 1. Order item meta.
		if ( '' !== $meta_key ) {
			$item_meta_value = $item->get_meta( $meta_key, true );

			if ( '' !== $item_meta_value && null !== $item_meta_value ) {
				return (string) $item_meta_value;
			}
		}

		// Also allow matching by human-readable meta label (common with attribute-based line items).
		$by_label = $this->resolve_item_meta_by_label( $item, $field_name );
		if ( '' !== $by_label ) {
			return $by_label;
		}

		$product = $this->product_resolver->get_product( $item );

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		// 2. Variation attribute.
		$variation_value = $this->product_resolver->get_variation_attribute( $product, $field_name );
		if ( '' !== $variation_value ) {
			return $variation_value;
		}

		// 3. Product meta.
		if ( '' !== $meta_key ) {
			$product_meta_value = $this->product_resolver->get_product_meta( $product, $meta_key );
			if ( '' !== $product_meta_value ) {
				return $product_meta_value;
			}
		}

		// 4. Fallback.
		return '';
	}

	/**
	 * Attempts to find an order item meta entry whose display label loosely matches the field name.
	 *
	 * @param \WC_Order_Item_Product $item       Order line item.
	 * @param string                 $field_name Normalized field name.
	 * @return string
	 */
	private function resolve_item_meta_by_label( \WC_Order_Item_Product $item, $field_name ) {
		foreach ( $item->get_formatted_meta_data( '_', true ) as $meta ) {
			$label = isset( $meta->key ) ? strtolower( (string) $meta->key ) : '';

			if ( '' !== $label && false !== strpos( $label, str_replace( '_', '', strtolower( $field_name ) ) ) ) {
				return wp_strip_all_tags( (string) $meta->value );
			}
		}

		return '';
	}
}
