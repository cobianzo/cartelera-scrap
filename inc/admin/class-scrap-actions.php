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

	/**
	 * Initializes the class by hooking into WordPress actions.
	 */
	public static function init(): void {
		// Hook the handle_scrap_action method to the 'admin_init' action.
		add_action( 'admin_init', [ __CLASS__, 'handle_scrap_action' ] );
	}

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
			isset( $_POST['mi_accion_trigger'] ) &&
			isset( $_POST['mi_accion_nonce'] )
		) {
			// Verify the nonce to ensure the request is valid.
			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['mi_accion_nonce'] ), 'mi_accion_custom' ) ) {
				// Nonce verification failed, redirect to the admin page with an error.
				redirect_with_error( 'nonce' );
			}

			// phpcs:ignore (WordPress.PHP.DevelopmentFunctions.error_log_error_log)
			error_log( 'Acción ejecutada' );

			// Perform the scrap action.
			self::perform_scrap();

			// Redirect back to the admin page after the action is executed.
			wp_safe_redirect( admin_url( 'options-general.php?page=cartelera-scrap' ) );

			// Exit to ensure no further code is executed.
			exit;
		}
	}

	/**
	 * Performs the scrap action by retrieving and displaying the cartelera URL.
	 */
	public static function perform_scrap(): void {
		// Get the cartelera URL from the plugin.
		$cartelera_url = Cartelera_Scrap_Plugin::get_cartelera_url();

		// Retrieve all html for the cartelera URL.
		$html = wp_remote_get( $cartelera_url );
		$html = wp_remote_retrieve_body( ( $html && ! is_wp_error( $html ) ) ? $html : '' );
		if ( is_wp_error( $html ) ) {
			// Handle the error if the request fails.
			self::redirect_with_error( 'Error retrieving cartelera URL' . $html->get_error_message());
		} elseif ( ! $html ) {
			// Handle the case where the response is empty.
			self::redirect_with_error( 'Empty response from cartelera URL. It\'s empty' );
		}

		// start scrapping the html with DOM.;
		$scraper = new Simple_Scrapper( $html );
		$shows   = $scraper->getTextsAndHrefs("//div[@id='content-obras']//li/a[1]");

		$results = [];
		// For each show, we scrap the result in ticketmaster.
		foreach ( $shows as $show_index => $show ) {
			echo "$show_index => " . $show['text'] . "<br/>";
			if ( ! str_contains( $show['text'], 'Gala' )  ) { // todelete
				continue;
			}
			// Get the title and URL from the show.
			$title = $show['text'];
			$url   = $show['href'];

			// Get the ticketmaster URL.
			$ticketmaster_url = Cartelera_Scrap_Plugin::get_ticketmaster_url( $title );

			echo '<br>';
			echo $ticketmaster_url . '<br>';
			$html = wp_remote_get( $ticketmaster_url );
			$html = wp_remote_retrieve_body( ( $html && ! is_wp_error( $html ) ) ? $html : '' );
			if ( is_wp_error( $html ) ) {
				// Handle the error if the request fails.
				self::redirect_with_error( 'Error retrieving ticketmaster URL' . $html->get_error_message());
			} elseif ( ! $html ) {
				// Handle the case where the response is empty.
				self::redirect_with_error( 'Empty response from ticketmaster URL. It\'s empty' );
			}
			// start scrapping the html with DOM.;
			echo $show['text'] . '<br>';
			$scraper = new Simple_Scrapper( $html );
			$nodes = $scraper->get_root()->query('//ul[@data-testid="eventList"]/li');
			print_r($nodes);
			echo '<br>';
			// retrieve all timetables for the show.
			foreach ($nodes as $i => $li_item) {
				$div = $li_item->firstChild;
				$div = $div->firstChild;
				$all_divs  = $div->getElementsByTagName( 'div' );
				$all_spans = $div->getElementsByTagName( 'span' );
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

				echo $printed_date->textContent . '<br>';
				echo $complete_date->textContent . '<br>';
				echo $time->textContent . '<br>';
				// echo $node->textContent . '<br>';
			// 	if ($i > 10) { // todelete
			// 		break;
			// 	}
			}

			if ( $result_tickermaster ) {
				// now we retrieve the data from cartelera and compare it with the ticketmaster data.
				$show_in_cartelera = wp_remote_get( $show['href'] );
				$show_in_cartelera = wp_remote_retrieve_body( ( $show_in_cartelera && ! is_wp_error( $show_in_cartelera ) ) ? $show_in_cartelera : '' );
				if ( is_wp_error( $show_in_cartelera ) ) {
					// Handle the error if the request fails.
					self::redirect_with_error( 'Error retrieving cartelera URL' . $show_in_cartelera->get_error_message());
				} elseif ( ! $show_in_cartelera ) {
					// Handle the case where the response is empty.
					self::redirect_with_error( 'Empty response from cartelera URL. It\'s empty' );
				}
				// start scrapping the html with DOM.;
				$scraper = new Simple_Scrapper( $show_in_cartelera );
				$nodes = $scraper->get_root()->document->getElementsByTagName( 'strong' );
				foreach ($nodes as $i => $strongNode) {
					if ( str_contains( $strongNode->textContent, 'Horario de' ) ) {
						// Obtiene el texto que está justo después de ese <strong>
						$texto = '';
						if ($strongNode && $strongNode->nextSibling) {
								$nextNode = $strongNode->nextSibling;
								while ($nextNode && $nextNode->nodeName !== 'br') {
										if ($nextNode->nodeType === XML_TEXT_NODE || $nextNode->nodeType === XML_ELEMENT_NODE) {
												$texto .= $nextNode->textContent;
										}
										$nextNode = $nextNode->nextSibling;
								}
								$texto = trim($texto);
						}
						$result_tickermaster['cartelera_horario'] = Simple_Scrapper::sanear_texto_scrap( $texto );
						$result_tickermaster['cartelera_url']   = $show['href'];
						echo "HORARIOL: " . Simple_Scrapper::sanear_texto_scrap( $texto ) . '<br>';
						break;
					}
				}
			}

			// Store the result in an array.
			// TODO:...

		} // end for each show in cartelera

		// Terminate the script to prevent further execution.
		wp_die();
	}
}

// Initialize the Scrap_Actions class.
Scrap_Actions::init();
