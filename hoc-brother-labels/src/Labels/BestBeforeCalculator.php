<?php
/**
 * Calculates the best-before date for a label.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Labels;

use HOC\BrotherLabels\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class BestBeforeCalculator
 *
 * Priority:
 * 1. Explicit best_before value (already resolved by caller).
 * 2. roast_date + shelf_life_days.
 * 3. order completed date + shelf_life_days (if configured mode is completed_date).
 * 4. current time + shelf_life_days (if configured mode is today).
 */
class BestBeforeCalculator {

	/**
	 * Output date format used on the printed label.
	 *
	 * @var string
	 */
	private const OUTPUT_FORMAT = 'd.m.Y';

	/**
	 * Calculates the best-before date string for display on the label.
	 *
	 * @param string         $explicit_best_before Raw explicit best_before value, if any (any parseable date string).
	 * @param string         $roast_date            Raw roast date value, if any (any parseable date string).
	 * @param \WC_Order|null $order                 The order, used for completed-date mode.
	 * @return string Formatted date string (dd.mm.YYYY), or empty string if it cannot be determined.
	 */
	public function calculate( $explicit_best_before, $roast_date, $order = null ) {
		$timestamp = $this->calculate_timestamp( $explicit_best_before, $roast_date, $order );

		if ( null === $timestamp ) {
			return '';
		}

		return wp_date( self::OUTPUT_FORMAT, $timestamp );
	}

	/**
	 * Calculates the best-before timestamp using the priority chain described above.
	 *
	 * @param string         $explicit_best_before Raw explicit best_before value.
	 * @param string         $roast_date            Raw roast date value.
	 * @param \WC_Order|null $order                 The order.
	 * @return int|null Unix timestamp, or null if undetermined.
	 */
	public function calculate_timestamp( $explicit_best_before, $roast_date, $order = null ) {
		$shelf_life_days = (int) Options::get( 'shelf_life_days', 365 );
		$mode            = (string) Options::get( 'best_before_mode', 'today' );

		$timestamp = null;

		if ( '' !== $explicit_best_before ) {
			$timestamp = $this->parse_timestamp( $explicit_best_before );
		}

		if ( null === $timestamp && '' !== $roast_date ) {
			$roast_timestamp = $this->parse_timestamp( $roast_date );

			if ( null !== $roast_timestamp ) {
				$timestamp = $roast_timestamp + ( $shelf_life_days * DAY_IN_SECONDS );
			}
		}

		if ( null === $timestamp && 'completed_date' === $mode && $order instanceof \WC_Order ) {
			$completed_date = $order->get_date_completed();

			if ( $completed_date instanceof \WC_DateTime ) {
				$timestamp = $completed_date->getTimestamp() + ( $shelf_life_days * DAY_IN_SECONDS );
			}
		}

		if ( null === $timestamp && 'today' === $mode ) {
			$timestamp = time() + ( $shelf_life_days * DAY_IN_SECONDS );
		}

		/**
		 * Filters the calculated best-before timestamp.
		 *
		 * @param int|null       $timestamp             Calculated timestamp, or null.
		 * @param string         $explicit_best_before  Raw explicit best_before value.
		 * @param string         $roast_date            Raw roast date value.
		 * @param \WC_Order|null $order                 The order.
		 */
		return apply_filters( 'hoc_brother_labels_best_before_timestamp', $timestamp, $explicit_best_before, $roast_date, $order );
	}

	/**
	 * Attempts to parse an arbitrary date string into a timestamp.
	 *
	 * @param string $value Date string.
	 * @return int|null
	 */
	private function parse_timestamp( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return null;
		}

		$timestamp = strtotime( $value );

		return ( false === $timestamp ) ? null : $timestamp;
	}
}
