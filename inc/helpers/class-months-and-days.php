<?php
/**
 * Helper to deal with months and the days.
 *
 * @package Cartelera_Scrap
 * @subpackage Helpers
 */

namespace Cartelera_Scrap\Helpers;

/**
 * Class Months And Days.
 * Access to list of months in Spanish, and days of the week,
 * so we can easily find any reference to them in text.
 * Some other helpers related to identifying dates in text.
 */
class Months_And_Days {

	/**
	 * List of months
	 *
	 * @return array
	 */
	public static function months(): array {
		return [
			'enero'      => 'january',
			'febrero'    => 'february',
			'marzo'      => 'march',
			'abril'      => 'april',
			'mayo'       => 'may',
			'junio'      => 'june',
			'julio'      => 'july',
			'agosto'     => 'august',
			'septiembre' => 'september',
			'octubre'    => 'october',
			'noviembre'  => 'november',
			'diciembre'  => 'december',
		];
	}

	/**
	 * Hard-coded days of the week in spanish with
	 * translation in english.
	 *
	 * @return array
	 */
	public static function weekdays(): array {
		return [
			'lunes'     => 'monday',
			'martes'    => 'tuesday',
			'miercoles' => 'wednesday',
			'miércoles' => 'wednesday',
			'jueves'    => 'thursday',
			'viernes'   => 'friday',
			'sábado'    => 'saturday',
			'sabado'    => 'saturday',
			'sabados'   => 'saturday',
			'sábados'   => 'saturday',
			'domingo'   => 'sunday',
			'domingos'  => 'sunday',
		];
	}

	/**
	 * Checks if the text contains the current year or a month's name.
	 * @TODO, should work with this year, and nex year too (it might be dec 2025 and say Temporada 2026)
	 * @param string $text The text to evaluate.
	 * @return boolean
	 */
	public static function text_contains_a_date( string $text ): bool {
		$this_year = gmdate( 'Y' );
		if ( stripos( $text, 'de ' . $this_year ) !== false
		|| stripos( $text, 'temporada ' . $this_year ) !== false ) {
			return true;
		}
		foreach ( self::months() as $month => $number ) {
			if ( strpos( $text, 'de ' . $month ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * If $word is 4 digits and starts by 20.
	 *
	 * @param string $word ie 2025.
	 * @return boolean
	 */
	public static function is_year( string $word ) {
		return ( is_numeric( $word ) && 4 === strlen( $word ) && 0 === strpos( $word, '20', 0 ) );
	}
}
