<?php
/**
 * Resolves product-level data for a line item, used as a fallback layer.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductResolver
 */
class ProductResolver {

	/**
	 * Returns the WC_Product for an order item, preferring the variation if present.
	 *
	 * @param \WC_Order_Item_Product $item Order line item.
	 * @return \WC_Product|null
	 */
	public function get_product( \WC_Order_Item_Product $item ) {
		$product = $item->get_product();

		return ( $product instanceof \WC_Product ) ? $product : null;
	}

	/**
	 * Reads a meta value from the product (and its parent if it is a variation).
	 *
	 * @param \WC_Product $product  Product or product variation.
	 * @param string      $meta_key Meta key to read.
	 * @return string
	 */
	public function get_product_meta( \WC_Product $product, $meta_key ) {
		$value = $product->get_meta( $meta_key, true );

		if ( '' !== $value && null !== $value ) {
			return (string) $value;
		}

		if ( $product instanceof \WC_Product_Variation ) {
			$parent_id = $product->get_parent_id();
			$parent    = $parent_id ? wc_get_product( $parent_id ) : null;

			if ( $parent instanceof \WC_Product ) {
				$parent_value = $parent->get_meta( $meta_key, true );

				if ( '' !== $parent_value && null !== $parent_value ) {
					return (string) $parent_value;
				}
			}
		}

		return '';
	}

	/**
	 * Reads a variation attribute value, if the product is a variation.
	 *
	 * @param \WC_Product $product       Product or product variation.
	 * @param string      $attribute_key Attribute key, e.g. "grind".
	 * @return string
	 */
	public function get_variation_attribute( \WC_Product $product, $attribute_key ) {
		if ( ! $product instanceof \WC_Product_Variation ) {
			return '';
		}

		$attributes = $product->get_variation_attributes( false );

		foreach ( $attributes as $key => $value ) {
			$normalized_key = str_replace( array( 'attribute_pa_', 'attribute_' ), '', $key );

			if ( $normalized_key === $attribute_key && '' !== $value ) {
				return (string) $value;
			}
		}

		return '';
	}
}
