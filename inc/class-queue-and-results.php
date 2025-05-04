<?php
/**
 * Class QueueAndResults
 *
 * We create a queue of events to be processed.
 * And we store the results in the DB too.
 * This class is responsible for managing a queue and storing results.
 * It provides methods to handle tasks in a queue and retrieve the processed results.
 *
 *
 *
 * @package CarteleraScrap
 * @subpackage Inc
 */

namespace Cartelera_Scrap\Helpers;

/**
 *  * =======
 * CRUD QUEUE: for the processing queue of shows in the options table.
 * CRUD RESULTS: we store the results of the scrapped text in the options table
 */
class Queue_And_Results {


	/** The name of the option_name in the DB to store tha array of shows to process */
	const OPTION_QUEUE = CARTELERA_SCRAP_PLUGIN_SLUG . '_shows_queue';

	/** The name of the option_name in the db for the results (array) */
	const OPTION_RESULTS = CARTELERA_SCRAP_PLUGIN_SLUG . '_shows_results';

	const OPTION_TIMESTAMP = CARTELERA_SCRAP_PLUGIN_SLUG . '_last_update_timestamp';

	/**
	 * Update the shows options in the database.
	 *
	 * @param array $shows_text_href    array of shows in format [ 'text' => '...', 'href' => '...' ].
	 * @return bool
	 */
	public static function update_shows_queue_option( array $shows_text_href = [] ): bool {
		if ( empty( $shows_text_href ) ) {
			return false;
		}
		return update_option( self::OPTION_QUEUE, $shows_text_href );
	}

	/**
	 * Get the number of shows to be processed still
	 *
	 * @return integer
	 */
	public static function get_queued_count(): int {
		$all_queued = self::get_queued_shows();
		return count( $all_queued );
	}

	/**
	 * Retrieve the first show option from the database (option `cartelera-scrap_shows_queue`').
	 *
	 * @return array|null
	 */
	public static function get_first_queued_show(): array|null {
		// Retrieve the first show option from the database.
		$shows = get_option( self::OPTION_QUEUE );
		if ( empty( $shows ) ) {
			return null;
		}
		return $shows[0];
	}

	/**
	 * Adds at the beggining of the queue (will be evaluated next in the parsing process)
	 *
	 * @param array $show_data The text title and the url on the scrapping processing queue.
	 * @return boolean
	 */
	public static function add_first_queued_show( array $show_data ): bool {
		$all_queued = self::get_queued_shows();
		if ( ! isset( $show_data['text'] ) || ! isset( $show_data['href'] ) ) {
			return false;
		}
		array_unshift( $all_queued, $show_data );
		return self::update_shows_queue_option( $all_queued );
	}

	/**
	 * Retrieve the shows options from the database.
	 *
	 * @return array
	 */
	public static function get_queued_shows(): array {
		return (array) get_option( self::OPTION_QUEUE );
	}

	/**
	 * Delete the first show option from the database.
	 * To be used once that first option has been processed.
	 *
	 * @return array
	 */
	public static function delete_first_queued_show(): array {
		$shows = get_option( self::OPTION_QUEUE );
		if ( ! $shows ) {
			return [];
		}
		array_shift( $shows );
		update_option( self::OPTION_QUEUE, $shows );

		return self::get_queued_shows();
	}

	/**
	 * ============================================.
	 * CRUD Results: for the shows results in the options table.
	 * name of the option in the database.
	 */

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
		self::last_update_results_timestamp();
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
	public static function add_show_result( array $result ): void {
		// Append a new show result to the existing results in the database.
		$results = self::get_show_results();

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
	public static function last_update_results_timestamp() {
		update_option( self::OPTION_TIMESTAMP, time() );
	}
}
