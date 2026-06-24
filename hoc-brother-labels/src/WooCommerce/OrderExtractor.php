<?php
/**
 * Extracts printable line items and raw data from a WC_Order.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderExtractor
 */
class OrderExtractor {

	/**
	 * Returns all printable line items for an order.
	 *
	 * Non-product items (shipping, fees, taxes, coupons) are skipped.
	 *
	 * @param \WC_Order $order Order object.
	 * @return \WC_Order_Item_Product[]
	 */
	public function get_printable_items( \WC_Order $order ) {
		$items = array();

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			/**
			 * Filters whether a given order item should be printed.
			 *
			 * @param bool                   $is_printable Whether the item is printable. Default true.
			 * @param \WC_Order_Item_Product $item         The order item.
			 * @param \WC_Order              $order        The order.
			 */
			$is_printable = apply_filters( 'hoc_brother_labels_is_printable_item', true, $item, $order );

			if ( $is_printable ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Extracts raw, normalized order/item metadata for a single line item.
	 *
	 * @param \WC_Order              $order Order object.
	 * @param \WC_Order_Item_Product $item  Order line item.
	 * @return array<string,mixed>
	 */
	public function extract_item_data( \WC_Order $order, \WC_Order_Item_Product $item ) {
		$product = $item->get_product();

		return array(
			'order_id'      => $order->get_id(),
			'order_number'  => $order->get_order_number(),
			'item_id'       => $item->get_id(),
			'product_id'    => $item->get_product_id(),
			'variation_id'  => $item->get_variation_id(),
			'quantity'      => $item->get_quantity(),
			'product_name'  => $item->get_name(),
			'sku'           => ( $product instanceof \WC_Product ) ? $product->get_sku() : '',
		);
	}
}
