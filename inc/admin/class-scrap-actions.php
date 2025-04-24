<?php
/**
 * This file contains the implementation of [describe the purpose of the file briefly].
 *
 * @package [Specify the package or module name, if applicable]
 * @author [Your Name]
 * @copyright [Year] [Your Organization or Name]
 * @license [Specify the license, e.g., MIT, GPL, etc.]
 * @version [Version of the file, if applicable]
 *
 * @description [Provide a brief description of the file's functionality or purpose.]
 */

namespace Cartelera_Scrap;

use Cartelera_Scrap\Admin\Settings_Page;

/**
 * The class Scrap_Actions handles the custom action triggered via a POST request
 */
class Scrap_Actions {

	const CRONJOB_NAME = 'cartelera_process_next_show';
	/**
	 * Initializes the class by hooking into WordPress actions.
	 */
	public static function init(): void {
		// Hook the handle_scrap_action method to the 'admin_init' action.
		add_action( 'admin_init', [ __CLASS__, 'handle_scrap_action' ] );
		add_action( self::CRONJOB_NAME, [ __CLASS__, 'cartelera_process_one_batch' ] );
	}

	/**
	 * =======
	 * CRUD QUEUE: for the processing queue of shows in the options table.
	 * Decalre the option name.
	 *
	 * @define CARTELERA_SCRAP_PLUGIN_SLUG string
	 * @return string
	 */
	public static function get_option_shows_process_queue(): string {
		return (string) CARTELERA_SCRAP_PLUGIN_SLUG . '_shows_queue';
	}
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
		return update_option( self::get_option_shows_process_queue(), $shows_text_href );
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
		$shows = get_option( self::get_option_shows_process_queue() );
		if ( empty( $shows ) ) {
			return null;
		}
		return $shows[0];
	}
	/**
	 * Retrieve the shows options from the database.
	 *
	 * @return array
	 */
	public static function get_queued_shows(): array {
		return (array) get_option( self::get_option_shows_process_queue() );
	}

	/**
	 * Delete the first show option from the database.
	 * To be used once that first option has been processed.
	 *
	 * @return array
	 */
	public static function delete_first_queued_show(): array {
		// Delete the first show option from the database.
		$shows = get_option( self::get_option_shows_process_queue() );
		if ( ! $shows ) {
			return [];
		}
		array_shift( $shows );
		update_option( self::get_option_shows_process_queue(), $shows );

		return self::get_queued_shows();
	}

	/**
	 * ============================================.
	 * CRUD Results: for the shows results in the options table.
	 * name of the option in the database.
	 *
	 * @return string
	 */
	public static function get_shows_results_option(): string {
		return CARTELERA_SCRAP_PLUGIN_SLUG . '_shows_results';
	}

	/**
	 * Retrieve the shows results from the database.
	 *
	 * @return array
	 */
	public static function get_show_results(): array {
		// Retrieve the shows results from the database.
		$results = get_option( self::get_shows_results_option() );
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
		update_option( self::get_shows_results_option(), $results );
	}

	/**
	 * Delete the shows results from the database.
	 *
	 * @return void
	 */
	public static function delete_show_results(): void {
		delete_option( self::get_shows_results_option() );
	}

	/**
	 * Append a new show result to the existing results in the database.
	 *
	 * @param array $result info about a show in both sources: [ title=>..., cartelera=>... ticketmaster=>...]  ] .
	 * @return void
	 */
	public static function append_show_result( array $result ): void {
		// Append a new show result to the existing results in the database.
		$results   = self::get_show_results();
		$results   = (array) $results; // Ensure $results is an array.
		$results[] = $result;
		self::update_show_results( $results );
	}

	// ====

	/**
	 * Handles the custom scrap action triggered via a POST request.
	 */
	public static function handle_scrap_action(): void {

		$message = 'Updated';

		// Check if the custom action and nonce are set in the POST request.
		if ( isset( $_POST['nonce_action_scrapping'] ) ) {
			// Verify the nonce to ensure the request is valid.
			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce_action_scrapping'] ), 'nonce_action_field' ) ) {
				wp_safe_redirect( add_query_arg(
					'error', 'Error: Nonce verification failed.',
					admin_url( 'options-general.php?page=cartelera-scrap' )
				) );
				exit;
			}

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- ignoring error_log usage for debugging purposes
			error_log( 'Acción ejecutada' );

			if ( isset( $_POST['start_scrapping_shows'] ) ) {

				// Perform the scrap action -> calls the cron job to start processing the shows.
				self::perform_scrap();

				// Redirect back to the admin page after the action is executed.
				$message = 'Scrap action executed successfully.';
			} elseif ( isset( $_POST['process_next_scheduled_show'] ) ) {
				update_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count', 0 ); // init the count of the shows being processed in this batch.
				self::cartelera_process_one_batch();
				$shows_per_batch = Cartelera_Scrap_Plugin::get_plugin_setting( Settings_Page::$number_processed_each_time ) ?? 10;
				$message         = sprintf( __( 'Processed %s theatre shows.', 'cartelera-scrap' ), $shows_per_batch );
			}

			$redirect = add_query_arg( 'message', $message, admin_url( 'options-general.php?page=cartelera-scrap' ) );

			wp_safe_redirect( $redirect );
			exit;
		}
	}

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
		// launch the first one-time-off cron job in WP to strart processing the shows.
		self::delete_show_results(); // clean the database and we will start from scratch.
		self::update_shows_queue_option( $all_shows ); // set up the list of shows that we will process.
		update_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count', 0 ); // init the count of the shows being processed in this batch.
		if ( wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_clear_scheduled_hook( self::CRONJOB_NAME );
		}
		if ( ! wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_schedule_single_event( time() + 30, self::CRONJOB_NAME ); // exectute in a few secs.
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
		$shows_per_batch = (int) Cartelera_Scrap_Plugin::get_plugin_setting( Settings_Page::$number_processed_each_time ) ?? 10;
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
			if ( ! wp_next_scheduled( self::CRONJOB_NAME ) ) {
				wp_schedule_single_event( time() + 5, self::CRONJOB_NAME ); // ejecuta en 5s.
			}
		} elseif ( $batch_count < $shows_per_batch ) {
			self::cartelera_process_one_batch();
		}
	}

	/**
	 *
	 *
	 * @return void
	 */
	public static function cartelera_process_one_single_show(): void {
		// retrieve the show title and url in cartelera.
		$show = self::get_first_queued_show();
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
			$title               = $show['text'];
			$ticketmaster_url    = Cartelera_Scrap_Plugin::get_ticketmaster_url( $title );
			$result_tickermaster = Simple_Scraper::scrap_one_tickermaster_show( $ticketmaster_url );
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
				self::append_show_result( [
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
		self::delete_first_queued_show();
	}

	/**
	 * Converts the text dates into an array of dates.
	 * The function will use regex to convert the text into an array of dates.
	 *
	 * @param string $text_dates The text containing the dates to be converted.
	 * @return array An array of dates extracted from the text.
	 */
	public static function convert_test_dates_into_array( string $text_dates ): array {
		// Tengo que hacer un regex para convertir las fechas en un array.
		// Las fechas pueden tener dos formatos , uno de ellos

		// Del 24 de abril al 8 de junio de 2025
		// 28 de mayo de 2025.

		// Sólo 30 de abril de 2025.
		// Finalizó el 6 de abril de 2025.

		// 2 y 9 de mayo de 2025.
		// 27 de abril, 4 y 11 de mayo.
		// 21, 22 y 23 de abril de 2025.
		// 17, 18, 24 y 25 de mayo de 2025
		// 1, 2,6, 8 y 9 de mayo  de 2025.
		// 23 y 30 de marzo y 6 abril de 2025.
		// Del 24 de abril al 8 de junio de 2025
		// Del 24 de abril al 8 de junio de 2025 (Suspende 1, 10 y 15 de mayo)
		// Viernes 21:30 horas. – Acceso al Foro Stelaris (piso 25) | 22:30 hrs – Inicio del Show | DJ a partir de las 00:00 hrs
		// En temporada 2025.
		// -- Miércoles y jueves 20:00 horas, viernes 20:30 horas, sábado 16:30 y 20:30 horas, domingo 13:00 y 17:30 horas.








		return [];
	}
}

// Initialize the Scrap_Actions class.
Scrap_Actions::init();
