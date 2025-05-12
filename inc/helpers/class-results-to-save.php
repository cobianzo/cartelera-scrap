<?php
/**
 * Class Results_To_Save
 *
 * This class is responsible for storing results.
 * It provides methods to handle and retrieve the processed results.
 *
 * @package Cartelera_Scrap
 * @subpackage Helpers
 */

namespace Cartelera_Scrap\Helpers;

use Cartelera_Scrap\Parse_Text_Into_Dates;

/**
 *  * =======
 * CRUD RESULTS: we store the results of the scrapped text in the options table
 */
class Results_To_Save {

	/** The name of the option_name in the db for the results (array) */
	const OPTION_RESULTS = CARTELERA_SCRAP_PLUGIN_SLUG . '_shows_results';

	/** The name of the option_name in the db for the timestamp of the last update */
	const OPTION_TIMESTAMP = CARTELERA_SCRAP_PLUGIN_SLUG . '_update_lastsaved_results_timestamp';

	/**
	 * Retrieve the shows results from the database.
	 *
	 * @return array
	 */
	public static function get_show_results(): array {
		// Retrieve the shows results from the database.
		$results = get_option( self::OPTION_RESULTS );
		if ( ! $results ) {
			return [];
		}
		return $results;
	}

	/**
	 * Update the shows results in the database.
	 *
	 * @param array $results array of shows results [ [ title=>..., cartelera=>... ticketmaster=>...]  ] .
	 * @return void
	 */
	public static function update_show_results( array $results ): void {
		// Update the shows results in the database.
		update_option( self::OPTION_RESULTS, $results );
		self::update_lastsaved_results_timestamp();
	}

	/**
	 * Delete the shows results from the database.
	 *
	 * @return void
	 */
	public static function delete_show_results(): void {
		delete_option( self::OPTION_RESULTS );
		delete_option( self::OPTION_TIMESTAMP );
	}

	/**
	 * Append a new show result to the existing results in the database.
	 *
	 * @param array $result info about a show in both sources: [ title=>..., cartelera=>... ticketmaster=>...]  ] .
	 * @return void
	 */
	public static function save_show_result( array $result ): void {
		// Append a new show result to the existing results in the database.
		$results = self::get_show_results();

		// append computed data:
		$result['computed']                 = empty( $result['computed'] ) ? [] : $result['computed'];
		$result['computed']['cartelera']    = Parse_Text_Into_Dates::computed_data_cartelera_result( $result );
		$result['computed']['ticketmaster'] = Parse_Text_Into_Dates::computed_data_ticketmaster_result( $result );
		$result['computed']['comparison']   = Parse_Text_Into_Dates::computed_dates_comparison_result( $result );

		// TODELETE;
		// $is_successful = Parse_Text_Into_Dates::computed_for_today_is_comparison_successful( $result );
		// echo $is_successful ? '✅ ' : '❌ ';
		// \Cartelera_Scrap\ddie( $result );

		// First looks for the show with the same title, in case it needs to update, not append.
		foreach ( $results as $i => $existing_result ) {
			if ( isset( $existing_result['title'] ) && $existing_result['title'] === $result['title'] ) {
				$results[ $i ] = $result;
				self::update_show_results( $results );
				return;
			}
		}

		$results   = (array) $results; // Ensure $results is an array.
		$results[] = $result;
		self::update_show_results( $results );
	}

	// ---- End of the CRUD

	/**
	 * Every time we update the results table, we save the time.
	 *
	 * @return void
	 */
	public static function update_lastsaved_results_timestamp() {
		update_option( self::OPTION_TIMESTAMP, time() );
	}
}
