<?php
/**
 * House of Coffee 62mm continuous-roll label template.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\Labels\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * Class HouseOfCoffee62mmTemplate
 *
 * Describes the structured layout for the House of Coffee 62mm label.
 * This class does not render printer graphics; it documents the intended
 * layout regions consumed by the external print service.
 */
class HouseOfCoffee62mmTemplate {

	/**
	 * Template identifier sent to the print service.
	 *
	 * @var string
	 */
	public const NAME = 'house-of-coffee-62mm';

	/**
	 * Returns the layout regions for this template, in print order.
	 *
	 * The external print service is responsible for rendering these
	 * regions onto the physical label using its own layout engine.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_layout_regions() {
		return array(
			array(
				'region' => 'brand_logo',
				'type'   => 'logo',
				'field'  => 'brand',
			),
			array(
				'region' => 'product_name',
				'type'   => 'text',
				'field'  => 'product_name',
				'style'  => array(
					'uppercase'  => true,
					'size'       => 'large',
					'weight'     => 'bold',
				),
			),
			array(
				'region' => 'info_box',
				'type'   => 'bordered_table',
				'rows'   => array(
					array( 'label' => 'Grind', 'field' => 'grind' ),
					array( 'label' => 'Weight', 'field' => 'weight' ),
					array( 'label' => 'Strength', 'field' => 'strength' ),
					array( 'label' => 'Flavour', 'field' => 'flavour' ),
					array( 'label' => 'Roast', 'field' => 'roast' ),
				),
			),
			array(
				'region' => 'best_before',
				'type'   => 'text',
				'label'  => 'Best Before',
				'field'  => 'best_before',
			),
		);
	}
}
