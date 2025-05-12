<?php
/**
 * Class QueueAndResults
 *
 * We create a queue of events to be processed.
 * And we store the results in the DB too.
 * This class is responsible for managing a queue and storing results.
 * It provides methods to handle tasks in a queue and retrieve the processed results.
 *
 * @package Cartelera_Scrap
 * @subpackage Helpers
 */

namespace Cartelera_Scrap\Helpers;

/**
 *  * =======
 * CRUD QUEUE: for the processing queue of shows in the options table.
 */
class Queue_To_Process {

	/** The name of the option_name in the DB to store tha array of shows to process */
	const OPTION_QUEUE = CARTELERA_SCRAP_PLUGIN_SLUG . '_shows_queue';

	const OPTION_START_QUEUE_TIMESTAMP = CARTELERA_SCRAP_PLUGIN_SLUG . '_start_process_timestamp';

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

		self::save_timestamp_start_process();
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
	 * Save the current timestamp in the database with the specified format.
	 *
	 * @return void
	 */
	public static function save_timestamp_start_process(): void {
		update_option( self::OPTION_START_QUEUE_TIMESTAMP, gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Retrieve the timestamp indicating when the queue processing started.
	 *
	 * @param string $datetime_format The format in which to return the timestamp. Default is 'Y-m-d H:i:s'.
	 * @return string|null Formatted timestamp or null if no timestamp is set.
	 */
	public static function get_timestamp_start_process( $datetime_format =	'Y-m-d H:i:s' ): ?string {
		$time = get_option( self::OPTION_START_QUEUE_TIMESTAMP );
		if ( ! $time ) {
			return null;
		}
		return gmdate( $datetime_format, strtotime( $time ) );
	}

	/**
	 * Deletes the timestamp indicating when the queue processing started.
	 *
	 * @return void
	 */
	public static function delete_timestamp_start_process(): void {
		delete_option( self::OPTION_START_QUEUE_TIMESTAMP );
	}
}
