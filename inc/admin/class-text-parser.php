<?php
/**
 * This file contains the Text_Parser class, which provides methods for parsing and analyzing text
 * to identify dates and classify sentence types.
 *
 * @package Cartelera_Scrap
 */

namespace Cartelera_Scrap;

use Cartelera_Scrap\Admin\Settings_Page;
use Cartelera_Scrap\Cartelera_Scrap_Plugin;

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

	const DATE_COMPARE_FORMAT = 'Y-m-d';
	const TIME_COMPARE_FORMAT = 'H:i';

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

	public static function split_by_months( string $phrase ): array {
		$months_names   = array_keys( self::months() );
    $months_pattern = implode('|', array_map('preg_quote', $months_names));
    $pattern = '/((?:\d+-)?(?:' . $months_pattern . '))/';

    $parts = preg_split($pattern, $phrase, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    // Filtrar y agrupar correctamente
    $result = [];
    $buffer = '';

    foreach ($parts as $part) {
        if (preg_match('/^(?:\d+-)?(?:' . $months_pattern . ')$/', $part)) {
            $result[] = $buffer . $part;
            $buffer = '';
        } else {
            $buffer .= $part;
        }
    }

    if (!empty($buffer)) {
        $result[] = $buffer;
    }

    return array_filter($result);
}

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

	public static function get_weekday( string $date ) {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$timestamp = strtotime( $date );
			if ( $timestamp !== false ) {
				return strtolower( date( 'l', $timestamp ) ); // Returns the full name of the day in lowercase
			}
		}
		return null; // Return null if the date format is invalid or conversion fails
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
	 * if @word is 4 digits and starts by 20
	 *
	 * @param string $word ie 2025
	 * @return boolean
	 */
	public static function is_year( string $word ) {
		return ( is_numeric($word) &&  4 === strlen( $word ) && 0 === strpos( $word, '20', 0 ) ) ;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $datetimes
	 * @return array
	 */
	public static function remove_dates_previous_of_today( array $datetimes ): array {

		return array_filter( $datetimes, function ( $datetime ) {
			$today_start = strtotime( 'today 00:01' ); // int
			$timestamp   = strtotime( $datetime ); // int
			return $timestamp >= $today_start;
		} );
	}

	/**
	 * Return the timestamp of the limit of dates to evaluate in both ticketmaster and cartelera.
	 * Previous dates to this one won't be used for the comparison.
	 *
	 * @return integer|null
	 */
	public static function get_limit_datetime(): int|null {
		$days_from_now_limit  = (int) Settings_Page::get_plugin_setting( Settings_Page::$limit_days_forward_compare ) ?? null;
		$date_limit_timestamp = $days_from_now_limit ? strtotime( "+$days_from_now_limit days" ) : null;
		return $date_limit_timestamp;
	}

	/**
	 * given an array of dates (normally in format YYYY-mm-dd, exclude the ones too far ahead in time,
	 * beyond the limit of days set by the user in the settings page)
	 *
	 * @param array $datetimes
	 * @return array
	 */
	public static function remove_dates_after_limit( array $datetimes ): array {

		$date_limit_timestamp = self::get_limit_datetime();
		return array_filter( $datetimes, function ( $datetime ) use ( $date_limit_timestamp ) {
			// accept any date beofre the limit date set in settings.
			$timestamp = strtotime( $datetime ); // int
			return $date_limit_timestamp ? $timestamp <= $date_limit_timestamp : true;
		} );
	}

	// to compare both ticketmaster and cartelera datetimes.
	public static function compare_arrays( array $a, array $b ): array|bool {
		// Find elements only in A
		$only_in_a = array_diff( $a, $b );

		// Find elements only in B
		$only_in_b = array_diff( $b, $a );

		// If both diffs are empty, arrays are identical
		if ( empty( $only_in_a ) && empty( $only_in_b ) ) {
			return true;
		}

		// Otherwise, return the differences
		return [
			'only_in_a' => array_values( $only_in_a ),
			'only_in_b' => array_values( $only_in_b ),
		];
	}




	/**
	 * Basically trimming
	 */
	public static function cleanup_sentences( array $sentences ) {
		// Sustitución de "hrs" por "horas"
		$sentences = array_map( function ( $sentence ) {

			$input_text = str_ireplace( [ 'hrs.', 'hrs', 'Hrs', 'HRS' ], 'horas', $sentence );

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
	 * - Uses sanitize_title() for basic slug formatting.
	 * - Removes unwanted numbers (only years > 2025 or days 1-31 are allowed).
	 * - Keeps only specific words (del, suspende, al) and Spanish month names.
	 *
	 * @param string $dates_sentence Input phrase.
	 * @return string Sanitized slug.
	 */
	public static function sanitize_dates_sentence( string $dates_sentence ): string {
		// Convert to lowercase
		$text = sanitize_title( $dates_sentence );

		// Remove numbers not corresponding to year >= 2025 or day 1-31
		$text = preg_replace_callback( '/\b\d+\b/', function ( $matches ) {
			$num = (int) $matches[0];
			if ( ( $num >= 1 && $num <= 31 ) || $num >= 2025 ) {
				return $matches[0];
			}
			return '';
		}, $text );

		// Define allowed Spanish month names
		$months = array_map( fn( string $month_name ) => $month_name, array_keys( self::months() ) );

		// Allow only "del-", "-al-", "suspende", months, numbers, hyphens, and years numbers
		$allowed_pattern = '/(?:del\-|suspende|cierre|finalizo|\-de\-temporada|temporada|(?:' . implode( '|', $months ) . ')|-al-[0-9]{1,2}|\b[0-9]{1,2}\b|\b20[0-9]{2}\b|-)/';

		// Remove unwanted parts
		preg_match_all( $allowed_pattern, $text, $matches );
		$valid_text = implode( '', $matches[0] );

		// Cleanup: remove multiple hyphens
		$valid_text = preg_replace( '/-+/', '-', $valid_text );
		$valid_text = trim( $valid_text, '-' );

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
		$frases = preg_split( $pattern, $texto, -1, \PREG_SPLIT_NO_EMPTY );

		return array_values( array_filter( array_map( 'trim', $frases ) ) );
	}

	/**
	 * Converts 'Del 24 de abril al 8 de junio de 2025 (Suspende 1, 10 y 15 de mayo)'
	 * into [ 'del-24-abril-al-8-junio-2025', 'suspende-1-10-15-mayo' ]
	 *
	 * @param string $input_date_text
	 * @return array
	 */
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

		// converts into lower case with dashes 'del-24-abril-al-8-junio-2025'
		$sentences = array_map( fn( $s ) => self::sanitize_dates_sentence( $s ), $sentences );

		// in order to be valid, the sentence must contain:
		// at least one number between 1 and 31 and one name of month (in spanish)
		$pattern         = '/\b(3[01]|[12][0-9]|[1-9])\b.*\b(' . implode( '|', array_keys( self::months() ) ) . ')\b/i';
		$valid_sentences = [];
		foreach ( $sentences as $i => $phrase ) {
			if ( str_contains( $phrase, 'temporada' ) && str_contains( $phrase, '20' ) ) {
				$is_valid = true;
			} else {
				$is_valid = preg_match( $pattern, $phrase ) && strlen( trim( $phrase ) );
			}
			if ( $is_valid ) {
				$valid_sentences[] = trim( $phrase );
			}
		}

		return $valid_sentences;
	}

	/**
	 * "Jueves y viernes, 20:00 horas, sábados 19:00 horas y domingos 18:00 horas"
	 * converts into [ jueves-viernes-20:00, sabados-19:00, domingos-18:00 ]
	 *
	 * @param string $input_time_text
	 * @return array
	 */
	public static function first_acceptance_of_times_text( string $input_time_text ): array {

		// Lista de patrones válidos
		$pattern_weekdays = implode( '|', array_keys( self::weekdays() ) );

		$valid_patterns = [

			// "Jueves y viernes, 20:00 horas, sábados 19:00 horas y domingos 18:00 horas"; (see the ',')
			// '/((?:' . $pattern_dias_plural . ')(?:\s*(?:,|y)\s*(?:' . $pattern_dias_plural . '))*)\s*,?\s*((?:\d{1,2}:\d{2})(?:\s*(?:,|y)\s*\d{1,2}:\d{2})*)\s*horas/iu',
			'/(?:' . $pattern_weekdays . ')[^h]*?horas/iu',

			//
			// '/\b(?:' . $pattern_weekdays . ')\b(?:[^:]*?:\d{2}\s*horas(?:,?\s*y?\s*)?)+/iu',

			//
			// '/(?<!\w)(?:' . $pattern_weekdays . ')(?:\s*(?:\d{1,2}:\d{2})(?:\s*y\s*|\s*,\s*|\s+)?)*(?:\s*horas)?(?!\w)/iu',
		];


		// Validar patrones
		$valid_weekdays_times = [];
		foreach ( $valid_patterns as $pattern ) {
			preg_match_all( $pattern, $input_time_text, $matches );
			$valid_weekdays_times = array_merge( $valid_weekdays_times, $matches[0] );
		}

		// Lower case the sentence and with dashes
		// jueves-y-viernes-20:00-horas, sabados-19:00-horas, domingos-18:00-horas
		$valid_weekdays_times = array_map( function ( $time_dayweek ) {
			$text = strtolower( $time_dayweek ); // Convert to lowercase
			$text = str_replace(
				[ 'á', 'é', 'í', 'ó', 'ú', 'ñ' ],
				[ 'a', 'e', 'i', 'o', 'u', 'n' ],
				$text
			); // Replace accented vowels and ñ
			$text = preg_replace( '/[^a-z0-9:]+/', '-', $text ); // Replace non-allowed characters with dashes
			$text = preg_replace( '/-+/', '-', $text ); // Replace multiple dashes with a single dash
			$text = trim( $text, '-' ); // Trim dashes from the start and end
			return $text;
		}, $valid_weekdays_times );

		// filter to remove any word whihc is not a time or a day of the week.
		$valid_weekdays_times = array_map( function ( $input ) {
			$palabras = explode( '-', $input );

			$resultado = array_filter( $palabras, function ( $palabra ) {
					$palabra_lower = mb_strtolower( $palabra );

					// Mantener si es día de la semana
				if ( in_array( $palabra_lower, array_keys( self::weekdays() ) ) ) {
						return true;
				}

					// Mantener si es hora (ej: 18:00)
				if ( preg_match( '/^\d{1,2}:\d{2}$/', $palabra ) ) {
						return true;
				}

					return false;
			} );

			$resultado = array_map( function ( $palabra ) {
				$array_weekdays_translation = self::weekdays();
				// Translate if it's a day of the week
				if ( isset( $array_weekdays_translation[ $palabra ] ) ) {
					return $array_weekdays_translation[ $palabra ];
				}
				return $palabra;
			}, $resultado );

			return implode( '-', $resultado );
		}, $valid_weekdays_times );


		return $valid_weekdays_times;
	}

	/**
	 * Given sentences like ['saturday-19:00', 'sunday-19:00'] extracts [ 'saturday', 'sunday' ]
	 *
	 * @param array $sentences ie ['saturday-19:00', 'sunday-19:00']
	 * @return array all days of the week metioned in the array of sentences in english and lowercase
	 */
	public static function get_all_days_of_week_in_sentences( array $sentences ): array {
		$weekdays = [];
		foreach ( $sentences as $sentence ) {
			$words = explode( '-', $sentence );
			foreach ( $words as $word ) {
				if ( in_array( $word, self::weekdays() ) ) {
					$weekdays[] = $word;
				}
			}
		}
		return $weekdays;
	}

	/**
	 * Given a sanitized text with dates information, extract the
	 *  Converts that text into array of dates [ yyyy-mm-dd, ... ]
	 * First checks what kind of text explaining the dates is: is it a range of dates (del-xxx-al...),
	 * or is it specific days (12-14-abril-2025) , and other options.
	 * The susing regular expressions applies the analusus
	 *
	 * @param string $sanitized_date_sentence:
	 *                              Intro text: del-24-abril-al-8-junio-2025 (this format is 'range'),
	 *                              4-11-18-mayo-2025 (format `singledays`)
	 * @return array of dates [  '2025-05-17', '2025-05-18'  ... ]
	 */
	public static function identify_dates_sentence_daterange_or_singledays( string $sanitized_date_sentence ): array {

		if ( strpos( $sanitized_date_sentence, 'del-' ) === 0 ) { // && strpos( $sanitized_date_sentence, '-al-' ) !== false ) {
			$type = 'range';
		} elseif ( strpos( $sanitized_date_sentence, 'temporada' ) === 0 && strpos( $sanitized_date_sentence, '20' ) !== false ) {
			$type = 'temporada';
		} elseif ( strpos( $sanitized_date_sentence, 'finalizo' ) === 0 && strpos( $sanitized_date_sentence, '20' ) !== false ) {
			$type = 'finalizo';
		} else {
			$type = 'singledays';
		}

		echo "<br><h1>$type</h1>";

		$months_names = array_keys( self::months() );
		$current_year = date( 'Y' );
		$all_dates    = [];

		if ( 'singledays' === $type || 'finalizo' === $type ) {
			// Split the sentence with the separator of a year text (4-digit numbers starting with 20)
			// $year_pattern = '/\b20\d{2}\b/';
			$year_pattern = '/(20\d{2})/';  // Note the capturing parentheses
			$parts        = preg_split( $year_pattern, $sanitized_date_sentence, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

			// normally only one part.
			// Every part should have among its elements at least one number (for day of month), one month name and end with the year.,
			// if it's missing the month or the year, we append it (and make sure that the month is in english).
			echo "<h2>Evaluates </h2>"; dd($parts);
			// parts  is
			/*
			(
    			[0] => 13-27-abril-4-mayo-
    			[1] => 2025
					.. and potentially there could be more, but in practice I didnt find more
				) */
			// evaluate a set of dates like '12-13-abril-2025' or '12-abril'
			foreach ( $parts as $idx => $part ) {

				if ( self::is_year( $part[0] ) ) {
					continue;
				}

				// get closest year
				$year_for_this_part = date( 'Y' );
				for ( $j = $idx + 1, $j < count ( $part ); $j++ ) {
					if ( self:is_year( $part[ $j ] ) ) {
						$year_for_this_part = $part[ $j ];
						break;
					}
				}

				// explode the part but month name
				$subparts = self::split_by_months( $part );

				foreach ( $subparts as $subpart ) {
					echo "<h1>Evaluating subpart</h1>";
					print_r($subpart);

					// Extract numbers and the month name
					preg_match_all( '/\b(3[01]|[12][0-9]|[1-9])\b|\b(' . implode( '|', $months_names ) . ')\b/i', $subpart, $matches );
					$numbers = $matches[1];
					$numbers = array_filter( $numbers, fn( $numb ) => is_numeric( $numb ) );
					$months  = array_filter( $matches[2] ); // only one

					// Combine numbers and months into valid dates
					echo '<h1>numbers</h1>'; dd($numbers);
					echo '<h1>months</h1>'; dd($months);

					// Translate the month from spanihs to english
					foreach ( $numbers as $number ) {
						foreach ( $months as $month ) {
							$month_english = self::months()[ strtolower( $month ) ];
							$all_dates[]   = date( self::DATE_COMPARE_FORMAT, strtotime( "$number $month_english" ) );
						}
					}
				}

			}

			dd( $parts );
			dd( $all_dates );
			// finished with $all_dates filled in.

		} elseif ( 'range' === $type ) {
			/**
			 * Cases of range:
			 * del-1-al-30-marzo-2025
			 * del-1-abril-al-30-marzo-2025
			 * del-1-abril-2025-al-30-marzo-2025
			 * del-1-abril
			 * del-1-abril-2025
			 */


			// Extract months mentioned in the sentence
			preg_match_all( '/\b(' . implode( '|', array_keys( self::months() ) ) . ')\b/i', $sanitized_date_sentence, $matches );
			$months  = array_unique( $matches[1] );
			$from_to = [];
			// if ! count($months) || count($months) > 2  ==> error


			// the text `del-1-al-30-marzo-2025` (contains the name of a month 1 time)
			if ( 1 === count( $months ) ) {
				$common_month = $months[0];
				if ( str_contains( $sanitized_date_sentence, '-al-' ) ) {
					$from_to = explode( '-al-', $sanitized_date_sentence );
				} else {
					$from_to = [ $sanitized_date_sentence, '31-diciembre-' . date( 'Y' ) ];
				}
				// Filter parts: keep only numbers or month names
				foreach ( $from_to as $i => $part ) {
					$words       = explode( '-', $part );
					$valid_words = array_filter( $words, function ( $word ) use ( $months_names ) {
						return is_numeric( $word ) || in_array( strtolower( $word ), $months_names );
					} );
					// Translate months into english
					$valid_words   = array_map( fn( $word ) => in_array( $word, $months_names ) ? self::months()[ $word ] : $word, $valid_words );
					$from_to[ $i ] = $valid_words;
				}

				// append month to 'from' part, translated to english.
				$contains_month = false;
				foreach ( self::months() as $month_english ) {
					if ( in_array( $month_english, $from_to[0] ) ) {
						$contains_month = true;
						break;
					}
				}
				if ( ! $contains_month ) {
					$from_to[0][] = self::months()[ $common_month ];
				}

				// append current year to each part if not included.
				$from_to = array_map( function ( $words ) use ( $current_year ) {
					$last_word = end( $words );
					if ( ! is_numeric( $last_word ) || (int) $last_word <= 2024 ) {
						$words[] = $current_year;
					}
					return implode( '-', $words );  // convert array into dashed sentence string
				}, $from_to );
			}
			// the text `del-1-marzo-al-30-marzo-2025` (contains the name of a month 2 times)
			if ( 2 === count( $months ) ) {
				$first_month    = $months[0];
				$split_position = strpos( $sanitized_date_sentence, $first_month );
				if ( $split_position !== false ) {
					$parts = [
						substr( $sanitized_date_sentence, 0, $split_position + strlen( $first_month ) ),
						substr( $sanitized_date_sentence, $split_position + strlen( $first_month ) ),
					];

					foreach ( $parts as $part ) {
						$words = explode( '-', $part );

						// Filter parts: keep only numbers or month names
						$valid_words = array_filter( $words, function ( $word ) use ( $months_names ) {
							return is_numeric( $word ) || in_array( strtolower( $word ), $months_names );
						} );
						// Translate months into english
						$valid_words = array_map( fn( $word ) => in_array( $word, $months_names ) ? self::months()[ $word ] : $word, $valid_words );

						// if the $valid_words doesnt finish in a year later than 2024, then add the current year to the text
						$last_word = end( $valid_words );
						if ( ! is_numeric( $last_word ) || (int) $last_word <= 2024 ) {
							$valid_words[] = '-' . $current_year;
						}

						$from_to[] = implode( '-', $valid_words );
					}
					// return [$first_part, $second_part];
				}
			}

			$all_dates  = [];
			$start_date = strtotime( $from_to[0] );
			$end_date   = strtotime( $from_to[1] );

			if ( $start_date && $end_date && $start_date <= $end_date ) {
				while ( $start_date <= $end_date ) {
					$all_dates[] = date( self::DATE_COMPARE_FORMAT, $start_date );
					$start_date  = strtotime( '+1 day', $start_date );
				}
			}
		} elseif ( 'temporada' === $type ) {
			preg_match( '/\b20([2-9]\d|[3-9]\d{2}|[1-9]\d{3})\b/', $sanitized_date_sentence, $matches );
			if ( ! empty( $matches ) ) {
				$year       = (int) $matches[0];
				$start_date = strtotime( "1 January $year" );
				$end_date   = strtotime( "31 December $year" );

				while ( $start_date <= $end_date ) {
					$all_dates[] = date( self::DATE_COMPARE_FORMAT, $start_date );
					$start_date  = strtotime( '+1 day', $start_date );
				}
			}
		}

		// Remove everything that is not a month or a number

		return $all_dates;
	}

	/**
	 * with params (sunday, saturday-sunday-18:00-21:00) returns [`18:00`, `21:00`]
	 *
	 * @param string $weekday
	 * @param array $weekday_and_times_sentences
	 * @return array
	 */
	public static function get_times_for_weekday( string $weekday, array $weekday_and_times_sentences ): array {
		foreach ( $weekday_and_times_sentences as $sentence ) {
			if ( strpos( $sentence, $weekday ) !== false ) { // found the sentence with the given weekday
				// get the occurrences with text like '18:00'
				preg_match_all( '/\b\d{1,2}:\d{2}\b/', $sentence, $time_matches );
				return $time_matches[0];
			}
		}
		return [];
	}

	/**
	 * Undocumented function
	 *
	 * @param array $valid_dates
	 * @param array $weekday_and_times
	 * @return array
	 */
	public static function definitive_dates_and_times( array $valid_dates, array $weekday_and_times, array $sentences_dates ): array {

		$definitive_dates_and_times = [];
		print_r( $valid_dates );
		foreach ( $valid_dates as $date ) {

			if ( ! empty( $sentences_dates ) && 0 === strpos( $sentences_dates[0], 'finalizo-', 0 ) ) {
				$times = [ '23:59' ];
			} else {
				$weekday = self::get_weekday( $date );
				$times   = self::get_times_for_weekday( $weekday, $weekday_and_times );
			}

			if ( count( $times ) ) {
				foreach ( $times as $specific_time ) {
					$date_time = $date . ' ' . $specific_time;
					$date_time = date( self::DATE_COMPARE_FORMAT . ' ' . self::TIME_COMPARE_FORMAT, strtotime( $date_time ) );
					if ( ! in_array( $date_time, $definitive_dates_and_times, true ) ) {
						$definitive_dates_and_times[] = $date_time;
					}
				}
			}
		}
		sort( $definitive_dates_and_times );
		return $definitive_dates_and_times;
	}
}
