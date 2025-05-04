<?php
/**
 * This file contains the implementation of [describe the purpose of the file briefly].
 *
 * @package CarteleraScrap
 *
 * @description [Provide a brief description of the file's functionality or purpose.]
 */

namespace Cartelera_Scrap;

use Cartelera_Scrap\Admin\Settings_Page;
use Cartelera_Scrap\Admin\Settings_Hooks;
use Cartelera_Scrap\Helpers\Queue_And_Results;

/**
 * The class Scrap_Actions handles the custom action triggered via a POST request
 */
class Scrap_Actions {

	/**
	 * Retrieves the list of shows from cartelera and sets in to the processing queue.
	 * The launches the first cron job, which will process the first show, and save the result,
	 * calling the next cron job if there are more shows to process
	 *
	 * @return void.
	 */
	public static function perform_scrap(): void {

		// Retrieve all html for the cartelera URL.
		// and set them to the processing queue.
		$all_shows = Simple_Scraper::scrap_all_shows_in_cartelera();
		if ( ! $all_shows || is_wp_error( $all_shows ) ) {
			wp_safe_redirect( add_query_arg(
				'error', 'Error: No shows found in cartelera.',
				admin_url( 'options-general.php?page=cartelera-scrap' )
			) );
			exit;
		}
		// launch the first one-time-off cron job in WP to strart processing the shows.
		Queue_And_Results::delete_show_results(); // clean the database and we will start from scratch.
		Queue_And_Results::update_shows_queue_option( $all_shows ); // set up the list of shows that we will process.
		update_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count', 0 ); // init the count of the shows being processed in this batch.
		if ( wp_next_scheduled( Settings_Hooks::ONETIMEOFF_CRONJOB_NAME ) ) {
			wp_clear_scheduled_hook( Settings_Hooks::ONETIMEOFF_CRONJOB_NAME );
		}
		if ( ! wp_next_scheduled( Settings_Hooks::ONETIMEOFF_CRONJOB_NAME ) ) {
			wp_schedule_single_event( time() + 30, Settings_Hooks::ONETIMEOFF_CRONJOB_NAME ); // exectute in a few secs.
		}
	}

	/**
	 * THIS IS THE CRON JOB.
	 * Processes (scraps in carteleradeteatro.mx and in ticketmaster) one show from the processing queue.
	 * Saves the result from both sources in the database.
	 * Once finished, it deletes the show from the queue and calls the next cron job if there are more shows to process.

	 * @return void
	 */
	public static function cartelera_process_one_batch(): void {

		// processing $batch_count/$shows_per_batch in this cron job.
		$shows_per_batch = (int) Settings_Page::get_plugin_setting( Settings_Page::NUMBER_PROCESSED_EACH_TIME ) ?? 10;
		$batch_count     = get_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count' );
		$batch_count     = ( (int) $batch_count ) + 1;
		if ( $batch_count > $shows_per_batch ) {
			return;
		}

		self::cartelera_process_one_single_show();

		/** Weel done, aonther show has been processed... Now...
		 * - save the option with the count of the shows processed in this batch.
		 * - call the processing of the next one.
		 *      - it can be straight away if the batch is not finished.
		 *      - or we can schedule the next cron job to process the next batch.
		*  */
		update_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count', $batch_count );
		if ( $batch_count === $shows_per_batch ) {
			if ( ! wp_next_scheduled( Settings_Hooks::ONETIMEOFF_CRONJOB_NAME ) ) {
				wp_schedule_single_event( time() + 5, Settings_Hooks::ONETIMEOFF_CRONJOB_NAME ); // ejecuta en 5s.
			}
		} elseif ( $batch_count < $shows_per_batch ) {
			self::cartelera_process_one_batch();
		}
	}

	/**
	 * Grab first show in the queue and processes it:
	 *
	 * @return void
	 */
	public static function cartelera_process_one_single_show(): void {
		// retrieve the show title and url in cartelera.
		$show = Queue_And_Results::get_first_queued_show();
		if ( ! $show ) {
			// We have finished processing all the shows in the queue.
			update_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count', 0 );
			return;
		}
		if ( $show && $show['text'] ) {

			// process the show.
			/**
			 * =============================================
			 * 1. GET THE DATA about the show FROM https://www.ticketmaster.com.mx/search
			 */

			// Get the ticketmaster URL.
			$result_tickermaster = Simple_Scraper::scrap_one_tickermaster_show( $show['text'] );
			if ( $result_tickermaster && ! is_wp_error( $result_tickermaster ) ) {

				/**
				 * =============================================
				 * 2. GET THE DATA (dates) about the show FROM cartelera https://carteleradeteatro.mx/2025/name-of-show
				 */
				$result_cartelera = Simple_Scraper::scrap_one_cartelera_show( $show['href'] );

				/**
				 * =============================================
				 * 3. SAVE BOTH DATA IN THE DB Results
				 */
				Queue_And_Results::add_show_result( [
					'title'        => Simple_Scraper::sanitize_scraped_text( $show['text'] ),
					'cartelera'    => $result_cartelera,
					'ticketmaster' => $result_tickermaster,
				] );

			}
		}

		/**
		 * =============================================
		 * 4. once finised, we:
		 * - delete the show from the processing queue
		 */
		Queue_And_Results::delete_first_queued_show();
	}
}
