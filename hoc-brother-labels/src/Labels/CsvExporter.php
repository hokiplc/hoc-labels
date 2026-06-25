<?php
/**
 * Builds CSV rows of normalized label fields for one or more orders, for use
 * with external mail-merge / database-connect label design tools (e.g.
 * Brother P-touch Editor) when bench-testing a printer template directly.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Labels;

use HOC\BrotherLabels\WooCommerce\OrderExtractor;

defined( 'ABSPATH' ) || exit;

/**
 * Class CsvExporter
 */
class CsvExporter {

	/**
	 * Fixed CSV column order. Matches the normalized field map produced by
	 * FieldMapper::map(), so each column maps directly to a field key.
	 *
	 * @var string[]
	 */
	private const COLUMNS = array(
		'job_ref',
		'product_name',
		'brand',
		'grind',
		'weight',
		'strength',
		'flavour',
		'roast',
		'best_before',
		'copies',
	);

	/**
	 * Order extractor dependency.
	 *
	 * @var OrderExtractor
	 */
	private $order_extractor;

	/**
	 * Field mapper dependency.
	 *
	 * @var FieldMapper
	 */
	private $field_mapper;

	/**
	 * Constructor.
	 *
	 * @param OrderExtractor $order_extractor Order extractor.
	 * @param FieldMapper    $field_mapper    Field mapper.
	 */
	public function __construct( OrderExtractor $order_extractor, FieldMapper $field_mapper ) {
		$this->order_extractor = $order_extractor;
		$this->field_mapper    = $field_mapper;
	}

	/**
	 * Returns the CSV header row.
	 *
	 * @return string[]
	 */
	public function get_header() {
		return self::COLUMNS;
	}

	/**
	 * Builds CSV rows for a single order's printable line items.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array<int,array<string,mixed>>
	 */
	public function build_rows_for_order( \WC_Order $order ) {
		$rows = array();

		foreach ( $this->order_extractor->get_printable_items( $order ) as $item ) {
			$field_map = $this->field_mapper->map( $order, $item );

			$row = array();
			foreach ( self::COLUMNS as $column ) {
				$row[ $column ] = isset( $field_map[ $column ] ) ? $field_map[ $column ] : '';
			}

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Builds CSV rows across multiple orders.
	 *
	 * @param \WC_Order[] $orders Order objects.
	 * @return array<int,array<string,mixed>>
	 */
	public function build_rows_for_orders( array $orders ) {
		$rows = array();

		foreach ( $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$rows = array_merge( $rows, $this->build_rows_for_order( $order ) );
		}

		return $rows;
	}

	/**
	 * Streams the given rows as a CSV file download and terminates the request.
	 *
	 * @param array<int,array<string,mixed>> $rows     CSV rows (associative, keyed by column).
	 * @param string                          $filename Suggested download filename.
	 * @return void
	 */
	public function stream_csv( array $rows, $filename ) {
		nocache_headers();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

		$handle = fopen( 'php://output', 'w' );

		fputcsv( $handle, $this->get_header() );

		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}

		fclose( $handle );
	}
}
