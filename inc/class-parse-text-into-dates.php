<?php
/**
 * This class works right after we have filled the results in the DB.
 *
 * This file contains the Parse_Text_Into_Dates class,
 * which provides methods for parsing and analyzing text
 * to identify dates and classify sentence types.
 *
 * @package Cartelera_Scrap
 */

namespace Cartelera_Scrap;

use Cartelera_Scrap\Helpers\Text_Sanization;
use Cartelera_Scrap\Admin\Settings_Page;
use Cartelera_Scrap\Helpers\Months_And_Days;

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
class Parse_Text_Into_Dates {

	// Format to save the dates in the results.
	const DATE_COMPARE_FORMAT = 'Y-m-d';

	// Format for the time.
	const TIME_COMPARE_FORMAT = 'H:i';

	/**
	 * Helper: Used only for 'singledays' format of sentences, previously splitted
	 * by year.
	 *
	 * @param string $phrase ie '4-mayo-', or '29-mayo-2-junio-' (it's already sanitized and in Spanish).
	 * @return array of senteces with a single month each. ie [ 0 => '4-mayo' ].
	 */
	public static function split_by_months( string $phrase ): array {

		$months_names   = array_keys( Months_And_Days::months() );
		$months_pattern = implode( '|', array_map( 'preg_quote', $months_names ) );
		$pattern        = '/((?:\d+-)?(?:' . $months_pattern . '))/';

		$parts = preg_split( $pattern, $phrase, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		// Filtrar y agrupar correctamente.
		$result = [];
		$buffer = '';

		foreach ( $parts as $part ) {
			if ( preg_match( '/^(?:\d+-)?(?:' . $months_pattern . ')$/', $part ) ) {
				$result[] = $buffer . $part;
				$buffer   = '';
			} else {
				$buffer .= $part;
			}
		}

		if ( ! empty( $buffer ) && '-' !== $buffer ) {
			$result[] = $buffer;
		}

		return $result;
	}

	/**
	 * Helper: Given a date in format 'YYYY-MM-DD', returns the full name of the weekday in lowercase,
	 * or null if the date format is invalid or conversion fails.
	 *
	 * @param string $date the date in format 'YYYY-MM-DD'<div class=""></div>.
	 * @return null|string the full name of the weekday in lowercase (english), or null.
	 */
	public static function get_weekday( string $date ) {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$timestamp = strtotime( $date );
			if ( false !== $timestamp ) {
				return strtolower( gmdate( 'l', $timestamp ) ); // Returns the full name of the day in lowercase.
			}
		}
		return null; // Return null if the date format is invalid or conversion fails.
	}

	/**
	 * Given an array of dates, remove the previous dates to today.
	 * TODO: remove previous to now?.
	 *
	 * @param array $datetimes The array of dates.
	 * @return array The filtered array.
	 */
	public static function remove_dates_previous_of_today( array $datetimes ): array {

		return array_filter( $datetimes, function ( $datetime ) {
			$today_start = strtotime( 'now' );
			$timestamp   = strtotime( $datetime );
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
		$days_from_now_limit  = (int) ( Settings_Page::get_plugin_setting( Settings_Page::LIMIT_DAYS_FORWARD_COMPARE ) ?? null );
		$date_limit_timestamp = $days_from_now_limit ? strtotime( "+$days_from_now_limit days" ) : null;
		return $date_limit_timestamp;
	}

	/**
	 * Given an array of dates (normally in format YYYY-mm-dd, exclude the ones too far ahead in time,
	 * beyond the limit of days set by the user in the settings page).
	 *
	 * @param array $datetimes array of dates.
	 * @return array filtered array.
	 */
	public static function remove_dates_after_limit( array $datetimes ): array {

		$max_count = (int) Settings_Page::get_plugin_setting( Settings_Page::LIMIT_NUMBER_DATES_COMPARE );
		$datetimes = array_slice( $datetimes, 0, $max_count );

		$date_limit_timestamp   = self::get_limit_datetime();
		$dates_limit_by_faraway = array_filter( $datetimes, function ( $datetime ) use ( $date_limit_timestamp ) {
			// accept any date beofre the limit date set in settings.
			$timestamp = strtotime( $datetime );
			return $date_limit_timestamp ? $timestamp <= $date_limit_timestamp : true;
		} );


		return $dates_limit_by_faraway;
	}

	/**
	 * To compare both ticketmaster and cartelera datetimes.
	 * TODELETE: I think its not in use anymore.
	 *
	 * @param array $a The first array. Dates from cartelera.
	 * @param array $b The array of dates from ticketmaster.
	 * @return array|boolean
	 */
	public static function compare_arrays( array $a, array $b ): array|bool {
		// Find elements only in A.
		$only_in_a = array_diff( $a, $b );

		// Find elements only in B.
		$only_in_b = array_diff( $b, $a );

		// If both diffs are empty, arrays are identical.
		if ( empty( $only_in_a ) && empty( $only_in_b ) ) {
			return true;
		}

		// Otherwise, return the differences.
		return [
			'only_in_a' => array_values( $only_in_a ),
			'only_in_b' => array_values( $only_in_b ),
		];
	}

	/**
	 * Helper: Sanitize a phrase into a simplified slug using WordPress functions.
	 *
	 * This function prepares a text to be used as a clean slug:
	 * - Uses sanitize_title() for basic slug formatting.
	 * - Removes unwanted numbers (only years > 2025 or days 1-31 and are allowed).
	 * - Keeps only specific words (suspende, cierre, finalizo, -de-temporada, temporada).
	 *  and Spanish month names.
	 *
	 * @param string $dates_sentence Input phrase.
	 * @return string Sanitized slug.
	 */
	public static function sanitize_dates_sentence( string $dates_sentence ): string {
		// Convert to lowercase.
		$text = sanitize_title( $dates_sentence );

		// Remove numbers not corresponding to year >= 2025 or day 1-31.
		$text = preg_replace_callback( '/\b\d+\b/', function ( $matches ) {
			$num = (int) $matches[0];
			if ( ( $num >= 1 && $num <= 31 ) || $num >= 2025 ) {
				return $matches[0];
			}
			return '';
		}, $text );

		// Define allowed Spanish month names.
		$months = array_map( fn( string $month_name ) => $month_name, array_keys( Months_And_Days::months() ) );

		// Allow only "del-", "-al-", "suspende", months, numbers, hyphens, and years numbers.
		$allowed_pattern = '/(?:del\-|suspende|cierre|finalizo|\-de\-temporada|temporada|(?:' . implode( '|', $months ) . ')|-al-[0-9]{1,2}|\b[0-9]{1,2}\b|\b20[0-9]{2}\b|-)/';

		// Remove unwanted parts.
		preg_match_all( $allowed_pattern, $text, $matches );
		$valid_text = implode( '', $matches[0] );

		// Cleanup: remove multiple hyphens.
		$valid_text = preg_replace( '/-+/', '-', $valid_text );
		$valid_text = trim( $valid_text, '-' );

		return $valid_text;
	}


	/**
	 * Separates a text into sentences when it contains dates.
	 * The algorithm uses three types of separators:
	 * 1. A period followed by a space or the end of the line.
	 * 2. An opening parenthesis.
	 * 3. A year with 4 digits greater than 2025.
	 * The resulting sentences are then trimmed and filtered to remove empty strings.
	 *
	 * @param string $texto The text to split. ie 'Majestic Fórum Cultural: 4 de mayo de 2025. Las Torres: 11 de mayo de 2025.
	 * @return array An array of sentences. ie:
	 *  [ [0] => Majestic Fórum Cultural: 4 de mayo de 2025, [1] => Las Torres: 11 de mayo de 2025].
	 */
	public static function separate_dates_sentences( string $texto ): array {
		// Explicación:
		// 1. Punto seguido de espacio o fin de línea -> separador.
		// 2. Paréntesis de apertura '(' -> separador.
		// 3. Año de 4 dígitos mayor o igual que 2025 -> separador.
		$pattern = '/
        (\.\s+|\.?$)
        |
        (\()
        |
        (?=(\b(202[6-9]|20[3-9]\d|2[1-9]\d{2}|[3-9]\d{3})\b))  # Lookahead de año >= 2026
    /x';

		// Split based on sencence separators.
		$frases = preg_split( $pattern, $texto, -1, \PREG_SPLIT_NO_EMPTY );

		$return = array_values( array_filter( array_map( 'trim', $frases ) ) );
		return $return;
	}

	/**
	 * Converts 'Del 24 de abril al 8 de junio de 2025 (Suspende el 10 y 15 de mayo). Si hay mas texto se elimina.'
	 * into [ 'del-24-abril-al-8-junio-2025', 'suspende-1-10-15-mayo' ].
	 * It should also cleanup any non relevant text. We set some reserved words, and numbers, and month names.
	 *
	 * @param string $input_date_text Input phrase in Spanish, not modified.
	 * @param array  &$debug_data used to debug and test. It will hold the sentences and sanitized sentences.
	 * @return array
	 */
	public static function first_acceptance_of_date_text( string $input_date_text, &$debug_data = [] ): array {

		$sentences               = self::separate_dates_sentences( $input_date_text );
		$debug_data['sentences'] = $sentences;
		// $sentences = Text_Sanization::cleanup_sentences( $sentences ); not needed @TODELETE.

		// converts into lower case with dashes 'del-24-abril-al-8-junio-2025' and remove not relevant text.
		$sentences               = array_map( [ __CLASS__, 'sanitize_dates_sentence' ], $sentences );
		$debug_data['sanitized'] = $sentences;

		// in order to be valid, the sentence must contain:
		// at least one number between 1 and 31 and one name of month (in spanish).
		$pattern         = '/\b(3[01]|[12][0-9]|[1-9])\b.*\b(' . implode( '|', array_keys( Months_And_Days::months() ) ) . ')\b/i';
		$valid_sentences = [];
		foreach ( $sentences as $phrase ) {
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
	 * Criteria is:
	 * - to split the text into sentences, the word 'horas' must be in the sentence and it's a separator.
	 * - to remove any text not containing hours and days of the week in spanish,
	 * - translate the day of the week from spanish to english.
	 * sanitiz
	 *
	 * @param string $input_time_text the sencence to be parsed.
	 * @param array  &$debug_data used to debug and test. It will hold the sentences and sanitized sentences.
	 * @return array ie [ jueves-viernes-20:00, sabados-19:00, domingos-18:00 ].
	 */
	public static function first_acceptance_of_times_text( string $input_time_text, &$debug_data = [] ): array {

		// phpcs:disable
		// Text to output json that helps me to create data to test.
		// echo '<pre>'; // TODELETE
		// echo '{';
		// echo "   input: " . json_encode($input_time_text) . ", <br/>";
		// echo '</pre>';
		// phpcs:enable

		// List of valid days of the weel.
		$pattern_weekdays = implode( '|', array_keys( Months_And_Days::weekdays() ) );

		$valid_patterns = [

			// "Jueves y viernes, 20:00 horas, sábados 19:00 horas y domingos 18:00 horas"; (see the ',').
			// '/((?:' . $pattern_dias_plural . ')(?:\s*(?:,|y)\s*(?:' . $pattern_dias_plural . '))*)\s*,?\s*((?:\d{1,2}:\d{2})(?:\s*(?:,|y)\s*\d{1,2}:\d{2})*)\s*horas/iu',.
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

		$debug_data['sentences'] = $valid_weekdays_times;

		// Lower case the sentence and with dashes
		// jueves-y-viernes-20:00-horas, sabados-19:00-horas, domingos-18:00-horas
		$valid_weekdays_times = array_map( function ( $time_dayweek ) {
			$text = strtolower( $time_dayweek ); // Convert to lowercase
			$text = Text_Sanization::remove_accents( $text );
			$text = preg_replace( '/[^a-z0-9:]+/', '-', $text ); // Replace non-allowed characters with dashes
			$text = preg_replace( '/-+/', '-', $text ); // Replace multiple dashes with a single dash
			$text = trim( $text, '-' ); // Trim dashes from the start and end
			return $text;
		}, $valid_weekdays_times );

		$debug_data['sanitized'] = $valid_weekdays_times;

		// filter to remove any word which is not a time or a day of the week.
		// and translate the day of the week into English.
		$valid_weekdays_times = array_map( function ( $input ) {
			$palabras = explode( '-', $input );

			$resultado = array_filter( $palabras, function ( $palabra ) {
					$palabra_lower = mb_strtolower( $palabra );

					// Keep[ if it's day of the week.
				if ( in_array( $palabra_lower, array_keys( Months_And_Days::weekdays() ), true ) ) {
						return true;
				}

					// Keep if it's hour (ie: 18:00).
				if ( preg_match( '/^\d{1,2}:\d{2}$/', $palabra ) ) {
						return true;
				}

					return false;
			} );

			$resultado = array_map( function ( $palabra ) {
				$array_weekdays_translation = Months_And_Days::weekdays();
				// Translate if it's a day of the week.
				if ( isset( $array_weekdays_translation[ $palabra ] ) ) {
					return $array_weekdays_translation[ $palabra ];
				}
				return $palabra;
			}, $resultado );

			return implode( '-', $resultado );
		}, $valid_weekdays_times );


		// Text to output json that helps me to create data to test.
		// echo '<pre>';
		// echo "  output: " . json_encode($valid_weekdays_times) . ", <br/>";
		// echo '},';
		// echo '</pre>';
		return $valid_weekdays_times;
	}

	/**
	 * Given sentences like ['saturday-19:00', 'sunday-19:00'] extracts [ 'saturday', 'sunday' ].
	 * TODELETE: not in use.
	 *
	 * @param array $sentences ie ['saturday-19:00', 'sunday-19:00']
	 * @return array all days of the week metioned in the array of sentences in english and lowercase
	 */
	public static function get_all_days_of_week_in_sentences( array $sentences ): array {
		$weekdays = [];
		foreach ( $sentences as $sentence ) {
			$words = explode( '-', $sentence );
			foreach ( $words as $word ) {
				if ( in_array( $word, Months_And_Days::weekdays(), true ) ) {
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
	 * @param string $sanitized_date_sentence full text for the sentence:
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
		} elseif ( strpos( $sanitized_date_sentence, 'suspende' ) === 0 ) {
			$type = 'suspende';
		} else {
			$type = 'singledays';
		}

		// echo "<br><h1>$type</h1>"; // TODELETE.

		$months_names = array_keys( Months_And_Days::months() );
		$current_year = date( 'Y' );
		$all_dates    = [];

		if ( 'singledays' === $type || 'finalizo' === $type || 'suspende' === $type ) {
			// Split the sentence with the separator of a year text (4-digit numbers starting with 20)
			// $year_pattern = '/\b20\d{2}\b/';
			$year_pattern = '/(20\d{2})/';  // Note the capturing parentheses
			$parts        = preg_split( $year_pattern, $sanitized_date_sentence, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

			// normally only one part.
			// Every part should have among its elements at least one number (for day of month), one month name and end with the year.,
			// if it's missing the month or the year, we append it (and make sure that the month is in english).
			// echo '<h2>Evaluates </h2>'; // TODELETE
			// dd( $parts );
			// parts  is
			/*
			(
				[0] => 13-27-abril-4-mayo-
				[1] => 2025
					.. and potentially there could be more, but in practice I didnt find more
				) */
			// evaluate a set of dates like '12-13-abril-2025' or '12-abril'
			foreach ( $parts as $idx => $part ) {

				if ( Months_And_Days::is_year( $part ) ) {
					continue;
				}

				// get closest year
				$year_for_this_part = date( 'Y' );
				for ( $j = ( $idx + 1 ); $j < count( $parts ); $j++ ) {
					if ( Months_And_Days::is_year( $parts[ $j ] ) ) {
						$year_for_this_part = $parts[ $j ];
						break;
					}
				}

				// explode the part but month name
				$subparts = self::split_by_months( $part );

				foreach ( $subparts as $subpart ) {
					// echo '<h1>Evaluating subpart</h1>'; // TODELETE
					// print_r( $subpart );

					// Extract numbers and the month name
					preg_match_all( '/\b(3[01]|[12][0-9]|[1-9])\b|\b(' . implode( '|', $months_names ) . ')\b/i', $subpart, $matches );
					$numbers = $matches[1];
					$numbers = array_filter( $numbers, fn( $numb ) => is_numeric( $numb ) );
					$months  = array_filter( $matches[2] ); // only one

					if ( ( empty( $numbers ) ) || ( empty( $months ) ) ) {
						continue;
					}
					// Combine numbers and months into valid dates
					// TODELETE
					// echo '<h1>numbers</h1>';
					// dd( $numbers );
					// echo '<h1>months</h1>';
					// dd( $months );

					// Translate the month from spanihs to english
					foreach ( $numbers as $number ) {
						foreach ( $months as $month ) {
							$month_english = Months_And_Days::months()[ strtolower( $month ) ];
							// echo "<h1>converting $number $month_english $year_for_this_part</h1>"; // TODELETE
							$all_dates[] = date( self::DATE_COMPARE_FORMAT, strtotime( "$number $month_english $year_for_this_part" ) );
						}
					}
				}
			}

			// dd( $parts ); // TODELETE
			// dd( $all_dates );
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
			preg_match_all( '/\b(' . implode( '|', array_keys( Months_And_Days::months() ) ) . ')\b/i', $sanitized_date_sentence, $matches );
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
					$valid_words   = array_map( fn( $word ) => in_array( $word, $months_names ) ? Months_And_Days::months()[ $word ] : $word, $valid_words );
					$from_to[ $i ] = $valid_words;
				}

				// append month to 'from' part, translated to english.
				$contains_month = false;
				foreach ( Months_And_Days::months() as $month_english ) {
					if ( in_array( $month_english, $from_to[0] ) ) {
						$contains_month = true;
						break;
					}
				}
				if ( ! $contains_month ) {
					$from_to[0][] = Months_And_Days::months()[ $common_month ];
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
						$valid_words = array_map( fn( $word ) => in_array( $word, $months_names ) ? Months_And_Days::months()[ $word ] : $word, $valid_words );

						// if the $valid_words doesnt finish in a year later than 2024, then add the current year to the text
						$last_word = end( $valid_words );
						if ( ! is_numeric( $last_word ) || (int) $last_word <= 2024 ) {
							$valid_words[] = '-' . $current_year;
						}

						$from_to[] = implode( '-', $valid_words );
					}
					// return [$first_part, $second_part];
				}
			} // end if range

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
	 * with params ('sunday', saturday-sunday-18:00-21:00) returns [`18:00`, `21:00`]
	 * becuause the param $weekday is included in the param weekday_and_times_sentences.
	 *
	 * @param string $weekday Day of the week, English, lowercase
	 * @param array  $weekday_and_times_sentences Sanitized text with weekdays in english.
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
	 * Combines parsed date sentences and times to produce definitive date-time entries.
	 *
	 * @param array $sentences_dates An array of date sentences (sanitized):
	 *                              ie . [ 'del-24-abril-al-8-junio-2025', 'suspende-1-10-15-mayo' ].
	 * @param array $weekday_and_times An array of sentences containing weekdays and times.
	 *                              ie [ [0] => saturday-sunday-13:00 ].
	 * @param array &$debug_data We store some info that would help for debugging and improving analysis.
	 * @return array An array of definitive date-time strings, sorted and unique. ie [ 'yyyy-dd-mm H:i', 'yyyy-dd-mm H:i' ... ]
	 */
	public static function definitive_dates_and_times( array $sentences_dates, array $weekday_and_times, &$debug_data = [ 'ca' ] ): array {

		$valid_dates = [];
		$debug_data  = [
			'removing_dates'     => [],
			'dates_per_sentence' => [],
			'times'              => [],
		];
		foreach ( $sentences_dates as $dates_in_text ) {
			if ( 0 === strpos( $dates_in_text, 'suspende' ) ) {
				$removing_dates                 = self::identify_dates_sentence_daterange_or_singledays( $dates_in_text );
				$debug_data['removing_dates'][] = $removing_dates;
				$valid_dates                    = array_diff( $valid_dates, $removing_dates );
			} else {
				$dates_in_sentence                                  = self::identify_dates_sentence_daterange_or_singledays( $dates_in_text );
				$valid_dates                                        = array_merge(
					$valid_dates,
					$dates_in_sentence
				);
				$debug_data['dates_per_sentence'][ $dates_in_text ] = $dates_in_sentence;
			}
		}


		// Now cross the result of all dates (substracting the 'suspende' dates) with the days of the week,
		// and add the hour times.
		$definitive_dates_and_times = [];
		// print_r( $valid_dates ); // TODELETE
		foreach ( $valid_dates as $date ) {

			if ( ! empty( $sentences_dates ) && 0 === strpos( $sentences_dates[0], 'finalizo-', 0 ) ) {
				$times                           = [ '23:59' ];
				$debug_data['times']['finalizo'] = $times;
			} else {
				$weekday                           = self::get_weekday( $date );
				$times                             = self::get_times_for_weekday( $weekday, $weekday_and_times );
				$debug_data['times'][ "$weekday" ] = $times;
			}

			// $debug_data['times'] = $times;

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

	/**
	 * Returns an array with computed data from the scrap result.
	 * This method takes the scrap result from Cartelera_Scrap::scrap, and
	 * returns an array with the computed data from the scrap result:
	 * - first_acceptance_dates: intermediate values that we might want to see for debugging.
	 * - first_acceptance_times: intermediate values that we might want to see for debugging.
	 * - definitive_datetimes: array with all the dates, we'll want to compare them with ticketmaster's.
	 *
	 * @param array $result The scrap result from Cartelera_Scrap::scrap.
	 * @return array The computed data extracted from the saved scrap result, and the intermediate values..
	 */
	public static function computed_data_cartelera_result( array $result ): array {

		$computed_cartelera_result = [
			'first_acceptance_dates' => [],
			'first_acceptance_times' => [],
			'definitive_datetimes'  => [],
		];

		// validations
		if ( empty( $result['cartelera']['scraped_dates_text'] ) || empty( $result['cartelera']['scraped_time_text'] ) ) {
			return $computed_cartelera_result;
		}

		// Let's start by adding the computed calculated text for the dates.
		$input_dates            = $result['cartelera']['scraped_dates_text'];
		$debug_data             = [];
		$first_acceptance_dates = self::first_acceptance_of_date_text( $input_dates, $debug_data );
		$computed_cartelera_result['first_acceptance_dates']['output']    = $first_acceptance_dates;
		$computed_cartelera_result['first_acceptance_dates']['sentences'] = $debug_data['sentences'];
		$computed_cartelera_result['first_acceptance_dates']['sanitized'] = $debug_data['sanitized'];

		// Now for the days of the week and hours.
		$input_time             = $result['cartelera']['scraped_time_text'];
		$debug_data             = [];
		$first_acceptance_times = self::first_acceptance_of_times_text( $input_time, $debug_data );
		$computed_cartelera_result['first_acceptance_times']           = $debug_data;
		$computed_cartelera_result['first_acceptance_times']['output'] = $first_acceptance_times;

		// Now the definitive date times extracted from the text.
		$debug_data                                        = [];
		$definitive                                        = self::definitive_dates_and_times( $first_acceptance_dates, $first_acceptance_times, $debug_data );
		$computed_cartelera_result['definitive_datetimes'] = $debug_data;
		$computed_cartelera_result['definitive_datetimes']['output'] = $definitive;

		return $computed_cartelera_result;
	}

	/**
	 * Processes the Ticketmaster results to extract and return computed date-time data.
	 *
	 * This method takes the scrap result from Ticketmaster data and compiles a list
	 * of definitive date-time entries that are extracted from the provided result.
	 *
	 * @param array $result The scrap result containing Ticketmaster date and time information.
	 * @return array An array with the computed definitive date-time entries YYYY-mm-dd H:i.
	 */
	public static function computed_data_ticketmaster_result( array $result ): array {

		$computed_tm_result = [
			'definitive_datetimes' => [],
		];
		$datetimes          = [];
		if ( ! empty( $result['ticketmaster']['dates'] ) ) {
			foreach ( $result['ticketmaster']['dates'] as $k => $date ) {
				$datetime    = $date['date'] . ' ' . $date['time']; // YYYY-mm-dd H:i
				$datetimes[] = $datetime;
			}
			$computed_tm_result['definitive_datetimes']['output'] = $datetimes;
		}
		return $computed_tm_result;
	}

	/**
	 * Takes the computed results from Cartelera and Ticketmaster data and
	 * compares the extracted dates and times.
	 * Returns an array with the definitive date-time entries that are extracted from result
	 * also returns a boolean indicating if the comparison was successful.
	 *
	 * @param array $result The computed results from Cartelera and Ticketmaster data.
	 * @return array An array with the definitive date-time entries and a boolean indicating
	 *               if the comparison was successful.
	 */
	public static function computed_dates_comparison_result( array $result ): array {

		$dates_ca     = $result['computed']['cartelera']['definitive_datetimes']['output'] ?? [];
		$dates_tm     = $result['computed']['ticketmaster']['definitive_datetimes']['output'] ?? [];
		$merged_dates = array_merge( $dates_ca, $dates_tm );
		$comparison   = [];
		foreach ( $merged_dates as $datetime ) {
			$computed_for_date = [
				'datetime'     => $datetime,
				'cartelera'    => in_array( $datetime, $dates_ca, true ),
				'ticketmaster' => in_array( $datetime, $dates_tm, true ),
			];
			$computed_for_date['success']         = $computed_for_date['cartelera'] && $computed_for_date['ticketmaster'];
			$comparison[ strtotime( $datetime ) ] = $computed_for_date;
		}

		return $comparison;
	}

	/**
	 * If we remove the previous dates to today, and the dates that exceed the limit in count and
	 * days forward (set in setttings), then we can check if the comparison was successful.
	 *
	 * @param array $result
	 * @return boolean
	 */
	public static function computed_for_today_is_comparison_successful( array &$result ): ?bool {

		// retrieve the already computed data for comparisons and we
		// add an extra layer of validation: we don't compare dates outside
		// of the given ranges.
		$dates_info          = $result['computed']['comparison'] ?? [];
		$limit_below         = strtotime( 'now' );
		$limit_above         = self::get_limit_datetime();
		$number_events_limit = (int) ( Settings_Page::get_plugin_setting( Settings_Page::LIMIT_NUMBER_DATES_COMPARE ) ?? 20 );

		$count_valid_dates_cart = 0;
		$count_valid_dates_tm   = 0;
		$is_successful          = true;
		foreach ( $dates_info as $timestamp => $date_info ) {
			// init. We'll append this data into $result[computer][comparison][<the timestamp>][extra] .
			$extra_info = [
				'invalid-for-comparison' => '',
			];

			if ( $timestamp < $limit_below ) {
				$extra_info['invalid-for-comparison'] .= $extra_info['invalid-for-comparison'] ? ', ' : '' . 'date-already-passed';
			}
			if ( $timestamp > $limit_above ) {
				$extra_info['invalid-for-comparison'] .= $extra_info['invalid-for-comparison'] ? ', ' : '' . 'date-too-far-ahead';
			}

			// Now we dont evaluate the date if it's over the limit of comparable events.
			if ( empty( $extra_info['invalid-for-comparison'] ) ) {
				if ( $date_info['cartelera'] ) {
					$count_valid_dates_cart++;
					$extra_info['count-in-cartelera'] = $count_valid_dates_cart;
					if ( $count_valid_dates_cart > $number_events_limit
						&& ! $date_info['success'] ) {
							$extra_info['invalid-for-comparison'] .= $extra_info['invalid-for-comparison'] ? ', ' : '' . 'cartelera-dates-already-passed-the-limit';
					}
				}
				if ( $date_info['ticketmaster'] ) {
					$count_valid_dates_tm++;
					$extra_info['count-in-ticketmaster'] = $count_valid_dates_tm;
					if ( $count_valid_dates_tm > $number_events_limit
						&& ! $date_info['success'] ) {
							$extra_info['invalid-for-comparison'] .= $extra_info['invalid-for-comparison'] ? ', ' : '' . 'ticketmaster-dates-already-passed-the-limit';
					}
				}
			}

			// Now the definitive comparison if it comparable.
			if ( empty( $extra_info['invalid-for-comparison'] ) ) {
				$extra_info['success-icon'] = $is_successful ? '✅' : '❌';
				// the value for the whole result:
				$is_successful = $is_successful && $date_info['success'];
			} else {
				$extra_info['success-icon'] = '😵 (not evaluable)';
			}

			$dates_info[ $timestamp ]['extra'] = $extra_info;
		}

		// Update the result with extra information
		$result['computed']['comparison'] = $dates_info;

		// not evaluable if we didnt find info frmo icketmaster
		if ( empty( $result['ticketmaster']['dates'] ) ) {
			$is_successful = null;
		}


		$result['computed']['success'] = $is_successful;

		return $is_successful;
	}
}
