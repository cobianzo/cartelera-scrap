<?php
/**
 * This file contains the Text_Parser class, which provides methods for parsing and analyzing text
 * to identify dates and classify sentence types.
 *
 * @package Cartelera_Scrap
 */

namespace Cartelera_Scrap;

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
 * Viernes 20:30 horas, sábados 18:00 y 20:30 horas y domingos 18:00 horas.
 * Viernes 21:30 horas. – Acceso al Foro Stelaris (piso 25) | 22:30 hrs – Inicio del Show | DJ a partir de las 00:00 hrs
 * En temporada 2025.
 */
class Text_Parser {



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
	public static function weekdays(): array {
		return [
			'lunes'     => 1,
			'martes'    => 2,
			'miércoles' => 3,
			'jueves'    => 4,
			'viernes'   => 5,
			'sábado'    => 6,
			'sabado'    => 6,
			'sabados'   => 6,
			'sábados'   => 6,
			'domingo'   => 7,
			'domingos'  => 7,
		];
	}

	/**
	 * Checks if the text contains the current year or a month's name
	 *
	 * @param string $text
	 * @return boolean
	 */
	public static function text_contains_a_date( string $text ): bool {
		$this_year = date( 'Y' );
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
	 * Basically trimming
	 *
	 */
	public static function cleanup_sentences( array $sentences ) {
		// Sustitución de "hrs" por "horas"
		$sentences = array_map( function( $sencence ) {

			$input_text = str_ireplace( [ 'hrs.', 'hrs', 'Hrs', 'HRS' ], 'horas', $sencence );

			// Limpiar espacios y comillas
			$input_text = trim( trim( $input_text ), '"' );

			return $input_text;
		}, $sentences );
		return $sentences;
	}

	/**
 * Sanitize a phrase into a simplified slug using WordPress functions.
 *
 * This function prepares a text to be used as a clean slug:
 * - Uses sanitize_title_with_dashes() for basic slug formatting.
 * - Removes unwanted numbers (only years > 2025 or days 1-31 are allowed).
 * - Keeps only specific words (del, suspende, al) and Spanish month names.
 *
 * @param string $dates_sentence Input phrase.
 * @return string Sanitized slug.
 */
public static function sanitize_dates_sentence( string $dates_sentence ): string {
	// Convert to lowercase
	$text = sanitize_title_with_dashes( $dates_sentence );

	// Remove numbers not corresponding to year >= 2025 or day 1-31
	$text = preg_replace_callback('/\b\d+\b/', function($matches) {
		$num = (int) $matches[0];
		if (($num >= 1 && $num <= 31) || $num >= 2025) {
			return $matches[0];
		}
		return '';
	}, $text);

	// Define allowed Spanish month names
	$months = array_map( fn( string $month_name ) => $month_name, array_keys( self::months() ) );

	// Allow only "del-", "-al-", "suspende", months, numbers, hyphens, and years numbers
	$allowed_pattern = '/(?:del\-|suspende|(?:' . implode('|', $months) . ')|-al-[0-9]{1,2}|\b[0-9]{1,2}\b|\b20[0-9]{2}\b|-)/';

	// Remove unwanted parts
	preg_match_all($allowed_pattern, $text, $matches);
	$valid_text = implode('', $matches[0]);

	// Cleanup: remove multiple hyphens
	$valid_text = preg_replace('/-+/', '-', $valid_text);
	$valid_text = trim($valid_text, '-');

	return $valid_text;
}
	public static function separate_dates_sentences( $texto ) {
    // Explicación:
    // 1. Punto seguido de espacio o fin de línea -> separador
    // 2. Paréntesis de apertura '(' -> separador
    // 3. Año de 4 dígitos mayor que 2025 -> separador
    $pattern = '/
        (\.\s+|\.?$)             # Punto seguido de espacio o final de texto
        |
        (\()                    # Paréntesis de apertura
        |
        (\b(202[6-9]|20[3-9]\d|2[1-9]\d{2}|[3-9]\d{3})\b) # Año > 2025
    /x';

    // Realizar la separación
    $frases = preg_split( $pattern, $texto, -1, \PREG_SPLIT_NO_EMPTY  );

    return array_values(array_filter(array_map('trim', $frases)));
	}

	public static function first_acceptance_of_date_text( string $input_date_text ): array {

		$sentences = self::separate_dates_sentences( $input_date_text );
		$sentences = self::cleanup_sentences( $sentences );



		// Lista de patrones válidos
		// esto funciona bastante bien pero opte por otro metodo.
		/*
		$pattern_meses = implode( '|', array_map( fn( string $month_name ) => $month_name, array_keys( self::months() ) ) );
		$valid_patterns = [
			// 23, 25 y 30 de marzo y 6 abril de 2025.
			'/(?:(?:\d{1,2}(?:, ?)?)+(?: y \d{1,2})? de (?:' . $pattern_meses . ')(?:,? ?))+de \d{4}\b|(?:\d{1,2}(?:, ?)?)+(?: y \d{1,2})? de (?:' . $pattern_meses . ')\b/',

			// Del 24 de abril al 8 de junio de 2025
			'/Del \d{1,2}(?: (?:de )?(?:' . $pattern_meses . '))? al \d{1,2} (?:de )?(?:' . $pattern_meses . ')(?: de 20\d{2})?(?:, del \d{1,2}(?: (?:de )?(?:' . $pattern_meses . '))? al \d{1,2} (?:de )?(?:' . $pattern_meses . ')(?: de 20\d{2})?)*\s*(?:\([^)]*\))?/iu',

			// En temporada 2025
			'/En temporada\s*(?:de\s*)?(20\d{2})(?:\s*y\s*(20\d{2}))?/iu',
		];




		// Validar patrones
		$valid_items = [];

		foreach ( $sentences as $item ) {
			foreach ( $valid_patterns as $pattern ) {
				if ( preg_match( $pattern, $item ) ) {
					$valid_items[] = $item;
					break;
				}
			}
		}
		*/

		$sentences = array_map( fn( $s ) => self::sanitize_dates_sentence( $s ), $sentences );
		// in order to be valid, the sencence must contain at least one number between 1 and 31 and one name of month

		$valid_sentences = array_filter($sentences, function ($sentence) {
			$pattern = '/\b(3[01]|[12][0-9]|[1-9])\b.*\b(' . implode('|', array_keys(self::months())) . ')\b/i';
			return preg_match($pattern, $sentence);
		});
		return $valid_sentences;
	}

	/**
	 *
	 *
	 * @param string $input_time_text
	 * @return array
	 */
	public static function first_acceptance_of_times_text( string $input_time_text ): array {

		// Lista de patrones válidos
		$pattern_weekdays = implode( '|', array_map( fn( string $day ) => $day, array_keys( self::weekdays() ) ) );
		$valid_patterns = [

			// "Jueves y viernes, 20:00 horas, sábados 19:00 horas y domingos 18:00 horas"; (see the ',')
			// '/((?:' . $pattern_dias_plural . ')(?:\s*(?:,|y)\s*(?:' . $pattern_dias_plural . '))*)\s*,?\s*((?:\d{1,2}:\d{2})(?:\s*(?:,|y)\s*\d{1,2}:\d{2})*)\s*horas/iu',
			'/(?:' . $pattern_weekdays . ')[^h]*?horas/iu',

			//
			// '/\b(?:' . $pattern_weekdays . ')\b(?:[^:]*?:\d{2}\s*horas(?:,?\s*y?\s*)?)+/iu',

			//
			//'/(?<!\w)(?:' . $pattern_weekdays . ')(?:\s*(?:\d{1,2}:\d{2})(?:\s*y\s*|\s*,\s*|\s+)?)*(?:\s*horas)?(?!\w)/iu',
		];


		// Validar patrones
		$valid_items = [];
		foreach ( $valid_patterns as $pattern ) {
			preg_match_all( $pattern, $input_time_text, $matches );
			$valid_items = array_merge( $valid_items, $matches[0] );
		}

		return $valid_items;
	}

	/**
	 * Intro text: del-24-abril-al-8-junio-2025 ,  4-11-18-mayo-2025 ...
	 *
	 * @param string $sanitized_date_sentence
	 * @return void
	 */
	public static function identify_dates_sencence_daterange_or_singledays( string $sanitized_date_sentence ): array {

		if (strpos($sanitized_date_sentence, 'del-') === 0 && strpos($sanitized_date_sentence, '-al-') !== false) {
			$type =  'range';
		} else {
			$type =  'singledays';
		}

		$months_names = array_keys( self::months() );
		$current_year = date('Y');
		$all_dates    = [];

		if ( 'singledays' === $type ) {
			// Split the sentence by year text (4-digit numbers starting with 20)
			$year_pattern = '/\b20\d{2}\b/';
			$parts = preg_split($year_pattern, $sanitized_date_sentence, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);



			foreach ($parts as $part) {
				// Extract numbers and month names
				preg_match_all('/\b(3[01]|[12][0-9]|[1-9])\b|\b(' . implode('|', $months_names) . ')\b/i', $part, $matches);

				$numbers = $matches[1];
				$months = array_filter($matches[2]);

				// If no year is present, append the current year
				if (!preg_match($year_pattern, $part)) {
					$numbers[] = $current_year;
				}

				// Combine numbers and months into valid dates
				foreach ($numbers as $number) {
					foreach ($months as $month) {
						$month_english = self::months()[strtolower($month)];
						$all_dates[] = date('Y-m-d', strtotime("$number $month_english"));
					}
				}
			}


		}
		elseif ( 'range' === $type ) {
			// Extract months mentioned in the sentence
			preg_match_all('/\b(' . implode('|', array_keys(self::months())) . ')\b/i', $sanitized_date_sentence, $matches);
			$months = array_unique($matches[1]);

			// if ! count($months) || count($months) > 2  ==> error


			if ( 1 === count( $months ) ) {
				$common_month = $months[0];
				$from_to      = explode('-al-', $sanitized_date_sentence);
				// Filter parts: keep only numbers or month names
				foreach ( $from_to as $i => $part ) {
					$words = explode( '-', $part );
					$valid_words = array_filter( $words, function( $word ) use ( $months_names ) {
						return is_numeric( $word ) || in_array( strtolower( $word ), $months_names );
					} );
					// Translate months into english
					$valid_words = array_map( fn( $word ) => in_array( $word, $months_names ) ? self::months()[$word] : $word, $valid_words );
					$from_to[ $i ] = $valid_words;
				}

				// append month to 'from' part, translated to english.
				$from_to[0][] = self::months()[$common_month];

				// append current year to each part if not included.
				$from_to = array_map( function( $words ) use ( $current_year ) {
					$last_word = end( $words );
					if ( ! is_numeric($last_word) || (int) $last_word <= 2024) {
						$words[] = $current_year;
					}
					return implode( '-', $words );  // convert array into dashed sentence string
				}, $from_to );
				print_r( $from_to );
			}
			if ( 2 === count( $months ) ) {
				$first_month = $months[0];
				print_r($first_month);
				$split_position = strpos($sanitized_date_sentence, $first_month);
				if ($split_position !== false) {
					$parts = [
						substr($sanitized_date_sentence, 0, $split_position + strlen($first_month)),
						substr($sanitized_date_sentence, $split_position + strlen($first_month))
					];

					$from_to = [];
					foreach ( $parts as $part ) {
						$words = explode( '-', $part );

						// Filter parts: keep only numbers or month names
						$valid_words = array_filter( $words, function( $word ) use ( $months_names ) {
							return is_numeric( $word ) || in_array( strtolower( $word ), $months_names );
						} );
						// Translate months into english
						$valid_words = array_map( fn( $word ) => in_array( $word, $months_names ) ? self::months()[$word] : $word, $valid_words );

						// if the $valid_words doesnt finish in a year later than 2024, then add the current year to the text
						$last_word = end($valid_words);
						if (!is_numeric($last_word) || (int)$last_word <= 2024) {
							$valid_words[] = '-' . $current_year;
						}

						$from_to[] = implode('-', $valid_words);
					}
					// return [$first_part, $second_part];
				}
			}

		}

		// Remove everything that is not a month or a number
		$all_dates = [];
		$start_date = strtotime($from_to[0]);
		$end_date = strtotime($from_to[1]);

		if ($start_date && $end_date && $start_date <= $end_date) {
			while ($start_date <= $end_date) {
				$all_dates[] = date('Y-m-d', $start_date);
				$start_date = strtotime('+1 day', $start_date);
			}
		}
		return $all_dates;
	}

	public static function transform_date_sentence_into_dates( $date_sentence ) {


	}
}
