<?php
/**
 * Adds a meta box / action panel to the order edit screen for printing labels.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Admin;

use HOC\BrotherLabels\Support\Capability;
use HOC\BrotherLabels\WooCommerce\OrderExtractor;

defined( 'ABSPATH' ) || exit;

/**
 * Class OrderMetaBox
 *
 * Renders a "Print HoC Labels" button for the whole order. Per-line-item
 * printing is structured for future extension via OrderActions::build_print_url()
 * but the line item granularity is not currently surfaced in the UI to avoid
 * cluttering the order edit screen.
 */
class OrderMetaBox {

	/**
	 * Order actions dependency, used to build the print URL.
	 *
	 * @var OrderActions
	 */
	private $order_actions;

	/**
	 * Order extractor dependency.
	 *
	 * @var OrderExtractor
	 */
	private $order_extractor;

	/**
	 * Constructor.
	 *
	 * @param OrderActions   $order_actions   Order actions.
	 * @param OrderExtractor $order_extractor Order extractor.
	 */
	public function __construct( OrderActions $order_actions, OrderExtractor $order_extractor ) {
		$this->order_actions   = $order_actions;
		$this->order_extractor = $order_extractor;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Adds the meta box to both legacy and HPOS order edit screens.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		$screens = array( 'shop_order', wc_get_page_screen_id( 'shop-order' ) );

		foreach ( array_unique( array_filter( $screens ) ) as $screen ) {
			add_meta_box(
				'hoc_bl_print_labels',
				__( 'House of Coffee Labels', 'hoc-brother-labels' ),
				array( $this, 'render_meta_box' ),
				$screen,
				'side',
				'high'
			);
		}
	}

	/**
	 * Renders the meta box content.
	 *
	 * @param mixed $post_or_order_object Post object (legacy) or WC_Order (HPOS).
	 * @return void
	 */
	public function render_meta_box( $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof \WP_Post )
			? wc_get_order( $post_or_order_object->ID )
			: $post_or_order_object;

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ! Capability::current_user_can_print() ) {
			echo '<p>' . esc_html__( 'You do not have permission to print labels.', 'hoc-brother-labels' ) . '</p>';
			return;
		}

		$printable_items = $this->order_extractor->get_printable_items( $order );
		$item_count      = count( $printable_items );

		echo '<div class="hoc-bl-metabox">';

		if ( 0 === $item_count ) {
			echo '<p>' . esc_html__( 'No printable coffee line items found on this order.', 'hoc-brother-labels' ) . '</p>';
			echo '</div>';
			return;
		}

		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: %d: number of printable line items. */
					_n( '%d printable label item.', '%d printable label items.', $item_count, 'hoc-brother-labels' ),
					$item_count
				)
			)
		);

		printf(
			'<p><a href="%1$s" class="button button-primary">%2$s</a></p>',
			esc_url( $this->order_actions->build_print_url( $order->get_id() ) ),
			esc_html__( 'Print HoC Labels', 'hoc-brother-labels' )
		);

		echo '</div>';
	}
}
