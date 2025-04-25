<?php
/**
 * Text Parser Class
 * Examples of tests to parse
 *
 * 2 y 9 de mayo de 2025.
 * 27 de abril, 4 y 11 de mayo.
 * 21, 22 y 23 de abril de 2025.
 * 17, 18, 24 y 25 de mayo de 2025
 * 1, 2,6, 8 y 9 de mayo  de 2025.
 * 23 y 30 de marzo y 6 abril de 2025.
 * Del 24 de abril al 8 de junio de 2025
 * Del 24 de abril al 8 de junio de 2025 (Suspende 1, 10 y 15 de mayo)
 * Viernes 21:30 horas. – Acceso al Foro Stelaris (piso 25) | 22:30 hrs – Inicio del Show | DJ a partir de las 00:00 hrs
 * En temporada 2025.
 */

namespace Cartelera_Scrap;

/**
 *
*/
class Text_Parser {

	public static function months(): array {
		return [
			'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5,
			'junio' => 6, 'julio' => 7, 'agosto' => 8, 'septiembre' => 9,
			'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
		];
	}

	public static function text_contains_a_date( string $text ): bool {
		$this_year = date( 'Y' );
		if ( strpos( $text, 'de ' . $this_year ) !== false ) {
			return true;
		}
		foreach ( self::months() as $month => $number ) {
			if ( strpos( $text, 'de ' . $month ) !== false ) {
				return true;
			}
		}

		return false;
	}

	public static function get_type_of_sentence( string $text ): array {

		// 17, 18, 24 y 25 de mayo de 2025 || 12 y 13 de abril, 5, 6 y 7 de mayo. || 12 y 13 de abril, 5, 6 y 7 de mayo de 2025
		$patterns = [
			'/^(\d{1,2}(?:,\s?\d{1,2})*\sy\s\d{1,2})\sde\s(\w+)(?:\sde\s(\d{4}))?$/', // 17, 18, 24 y 25 de mayo de 2025
			'/^(\d{1,2}\sy\s\d{1,2}\sde\s\w+,\s(?:\d{1,2},\s?)*\d{1,2}\sy\s\d{1,2}\sde\s\w+)(?:\sde\s(\d{4}))?$/', // 12 y 13 de abril, 5, 6 y 7 de mayo.
			'/^(\d{1,2}\sy\s\d{1,2}\sde\s\w+,\s(?:\d{1,2},\s?)*\d{1,2}\sy\s\d{1,2}\sde\s\w+\sde\s\d{4})$/' // 12 y 13 de abril, 5, 6 y 7 de mayo de 2025
		];

		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $text, $matches)) {
				return ['type' => 'days month and ?year', 'matches' => $matches];
			}
		}

		return [];


	}


}
