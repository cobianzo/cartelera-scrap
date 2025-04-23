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
		add_action( self::CRONJOB_NAME, [ __CLASS__, 'cartelera_process_one_show' ] );
	}

	/**
	 * CRUD for the processing queue of shows in the options table.
	 * Decalre the option name.
	 *
	 * @return string
	 */
	public static function get_option_shows_process_queue(): string {
		return CARTELERA_PLUGIN_SLUG . '_shows_queue';
	}
	/**
	 * Update the shows options in the database.
	 *
	 * @param array $shows_text_href
	 * @return bool
	 */
	public static function update_shows_queue_option( array $shows_text_href ): bool {
		return update_option( self::get_option_shows_process_queue(), $shows_text_href );
	}
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
	public static function get_queued_show(): array {
		return (array) get_option( self::get_option_shows_process_queue() );
	}
	public static function delete_first_queued_show(): array {
		// Delete the first show option from the database.
		$shows = get_option( self::get_option_shows_process_queue() );
		if ( ! $shows ) {
			return [];
		}
		array_shift( $shows );
		update_option( self::get_option_shows_process_queue(), $shows );

		return self::get_queued_show();
	}

	/**
	 * ============================================
	 * CRUD for the shows results in the options table.
	 * name of the option in the database.
	 *
	 * @return string
	 */
	public static function get_shows_results_option(): string {
		return CARTELERA_PLUGIN_SLUG . '_shows_results';
	}

	public static function get_show_results(): array {
		// Retrieve the shows results from the database.
		$results = get_option( self::get_shows_results_option() );
		if ( ! $results ) {
			return [];
		}
		return $results;
	}
	public static function update_show_results( array $results ): void {
		// Update the shows results in the database.
		update_option( self::get_shows_results_option(), $results );
	}
	public static function append_show_result( array $result ): void {
		// Append a new show result to the existing results in the database.
		$results = self::get_show_results();
		if ( ! $results ) {
			$results = [];
		}
		$results[] = $result;
		update_option( self::get_shows_results_option(), $results );
	}

	// Delete the shows results from the database.
	public static function delete_show_results(): void {
		delete_option( self::get_shows_results_option() );
	}


	// ====

	public static function redirect_with_error( string $error ): void {
		// Redirect to the admin page with an error message.
		wp_safe_redirect( add_query_arg(
			'error', $error,
			admin_url( 'options-general.php?page=cartelera-scrap' )
		) );
		exit;
	}

	/**
	 * Handles the custom scrap action triggered via a POST request.
	 */
	public static function handle_scrap_action(): void {
		// Check if the custom action and nonce are set in the POST request.
		if (
			isset( $_POST['start_scrapping_shows'] ) &&
			isset( $_POST['nonce_action_scrapping'] )
		) {
			// Verify the nonce to ensure the request is valid.
			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce_action_scrapping'] ), 'nonce_action_field' ) ) {
				redirect_with_error( 'Error: Nonce verification failed.' );
			}

			// phpcs:ignore (WordPress.PHP.DevelopmentFunctions.error_log_error_log)
			error_log( 'Acción ejecutada' );

			// Perform the scrap action -> calls the cron job to start processing the shows.
			self::perform_scrap();

			// Redirect back to the admin page after the action is executed.
			wp_safe_redirect( admin_url( 'options-general.php?page=cartelera-scrap' ) );

			// Exit to ensure no further code is executed.
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
		$html = wp_remote_get( Cartelera_Scrap_Plugin::get_cartelera_url() );
		$html = wp_remote_retrieve_body( ( $html && ! is_wp_error( $html ) ) ? $html : '' );
		if ( is_wp_error( $html ) ) {
			// Handle the error if the request fails.
			self::redirect_with_error( 'Error retrieving cartelera URL' . $html->get_error_message() );
		} elseif ( ! $html ) {
			// Handle the case where the response is empty.
			self::redirect_with_error( 'Empty response from cartelera URL. It\'s empty' );
		}

		// start scrapping the html with DOM.;
		$scraper = new Simple_Scrapper( $html );
		$shows   = $scraper->getTextsAndHrefs( "//div[@id='content-obras']//li/a[1]" ); // [ text=> , href=> ]

		// launch the first one-time-off cron job in WP to strart processing the shows.
		self::delete_show_results(); // clean the database and we will start from scratch.
		self::update_shows_queue_option( $shows ); // set up the list of shows that we will process.
		if ( wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_clear_scheduled_hook( self::CRONJOB_NAME );
		}
		if ( ! wp_next_scheduled( self::CRONJOB_NAME ) ) {
			wp_schedule_single_event( 0, self::CRONJOB_NAME ); // ejecuta en 5s
		}

	}

	public static function cartelera_process_one_show(): void {
		$show = self::get_first_queued_show();
		if ( ! is_array( $shows ) || empty( $shows ) || ! $show ) {
			delete_option( $option_name );
			// finish the cron job.
			return;
		}

		$show = self::get_first_queued_show();

		// process the show.

		// ============================
		// ============================


		/**
		 * =============================================
		 * 1. GET THE DATA about the show FROM ticketmaster
		 */

		// Get the ticketmaster URL.
		$title = $show['text'];
		$ticketmaster_url = Cartelera_Scrap_Plugin::get_ticketmaster_url( $title );

		$html = wp_remote_get( $ticketmaster_url );
		$html = wp_remote_retrieve_body( ( $html && ! is_wp_error( $html ) ) ? $html : '' );
		if ( $html && ! is_wp_error( $html ) ) {

			// start scrapping the html with DOM.;
			echo $show['text'] . '<br>'; // todelete
			$scraper = new Simple_Scrapper( $html );
			$nodes   = $scraper->get_root()->query( '//ul[@data-testid="eventList"]/li' );

			// retrieve all timetables for the show.
			foreach ( $nodes as $i => $li_item ) {
				$div           = $li_item->firstChild;
				$div           = $div->firstChild;
				$all_divs      = $div->getElementsByTagName( 'div' );
				$all_spans     = $div->getElementsByTagName( 'span' );
				$printed_date  = $all_divs->item( 0 );
				$complete_date = $all_spans->item( 0 );
				$time          = $all_spans->item( 10 ); // 8:30 p.m.
				$time_24h      = \DateTime::createFromFormat( 'g:i a', str_replace( '.', '', strtolower( $time->textContent ) ) )->format( 'H:i' );

				$result_tickermaster = [
					'printed_date'  => $printed_date->textContent,
					'complete_date' => $complete_date->textContent,
					'time'          => $time->textContent,
					'time_24h'      => $time_24h,
				];

				echo $printed_date->textContent . '<br>'; // todelete
				echo $complete_date->textContent . '<br>';
				echo $time->textContent . '<br>';
				// echo $node->textContent . '<br>';
				// if ($i > 10) { // todelete
				// break;
				// }
			}


			/**
			 * =============================================
			 * 2. GET THE DATA about the show FROM cartelera
			 */
			$result_cartelera = [];
			if ( $result_tickermaster ) {
				// now we retrieve the data from cartelera and compare it with the ticketmaster data.
				$show_in_cartelera = wp_remote_get( $show['href'] );
				$show_in_cartelera = wp_remote_retrieve_body( ( $show_in_cartelera && ! is_wp_error( $show_in_cartelera ) ) ? $show_in_cartelera : '' );
				if ( is_wp_error( $show_in_cartelera ) ) {
					// Handle the error if the request fails.
					self::redirect_with_error( 'Error retrieving cartelera URL' . $show_in_cartelera->get_error_message() );
				} elseif ( ! $show_in_cartelera ) {
					// Handle the case where the response is empty.
					self::redirect_with_error( 'Empty response from cartelera URL. It\'s empty' );
				}
				// start scrapping the html with DOM.;
				$scraper = new Simple_Scrapper( $show_in_cartelera );
				$nodes   = $scraper->get_root()->document->getElementsByTagName( 'strong' );
				foreach ( $nodes as $i => $strongNode ) {
					if ( str_contains( $strongNode->textContent, 'Horario de' ) ) {
						// Obtiene el texto que está justo después de ese <strong>
						$texto = '';
						if ( $strongNode && $strongNode->nextSibling ) {
								$nextNode = $strongNode->nextSibling;
							while ( $nextNode && $nextNode->nodeName !== 'br' ) {
								if ( $nextNode->nodeType === XML_TEXT_NODE || $nextNode->nodeType === XML_ELEMENT_NODE ) {
										$texto .= $nextNode->textContent;
								}
									$nextNode = $nextNode->nextSibling;
							}
								$texto = trim( $texto );
						}
						$result_cartelera['cartelera_horario'] = Simple_Scrapper::sanear_texto_scrap( $texto );
						$result_cartelera['cartelera_url']     = $show['href'];
						echo 'HORARIOL: ' . Simple_Scrapper::sanear_texto_scrap( $texto ) . '<br>'; // todelete
						break;
					}
				}
			}

			/**
			 * =============================================
			 * 3. SAVE BOTH DATA IN THE DB Results
			 */
			$new_result = [
				'cartelera' => $result_cartelera,
				'ticketmaster' => $result_tickermaster,
			];

			self::append_show_result( $new_result );

		}


		/**
		 * =============================================
		 * 4. once finised, we delete the show from the processing queue
		 * and call the processing of the next one.
		 */

		if ( self::delete_first_queued_show() ) {
			if ( ! wp_next_scheduled( self::CRONJOB_NAME ) ) {
				wp_schedule_single_event( time() + 5, self::CRONJOB_NAME ); // ejecuta en 5s
			}
		}
	}
}

// Initialize the Scrap_Actions class.
Scrap_Actions::init();
