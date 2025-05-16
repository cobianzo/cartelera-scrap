<?php
/**
 * Static methods for easier scrapping from carteleradeteatro webpages.
 *
 * @package Cartelera_Scrap
 * @subpackage Scraper
 */

namespace Cartelera_Scrap\Scraper;

use Cartelera_Scrap\Cartelera_Scrap_Plugin;
use Cartelera_Scrap\Parse_Text_Into_Dates;
use Cartelera_Scrap\Helpers\Text_Sanization;
use Cartelera_Scrap\Helpers\Months_And_Days;

use DOMDocument;
use DOMXPath;
use DOMElement;
use DOMNode;

/**
 * Class Scraper_Cartelera.
 * A simple scraper class for easy access to DOM elements and values.
 * Usage: $scraper = new Scraper_Cartelera($html);
 */
class Scraper_Cartelera extends Scraper {

	/**
	 * Scrapes all shows listed in the cartelera.
	 *
	 * This function retrieves the HTML content from the cartelera URL,
	 * parses it, and extracts the titles and links of all shows listed
	 * in the specified section of the page.
	 *
	 * @return array|\WP_Error An array of shows with their titles and links, or a WP_Error object on failure.
	 */
	public static function scrap_all_shows_in_cartelera(): array|\WP_Error {
		$url  = Cartelera_Scrap_Plugin::get_cartelera_url();
		$html = self::get_html_from_url( $url );
		if ( is_wp_error( $html ) ) {
			return $html;
		}

		// Start scrapping the HTML with DOM.
		$scraper = new Scraper_Cartelera( $html );
		$shows   = $scraper->get_texts_and_hrefs( "//div[@id='content-obras']//li/a[1]" );

		return $shows; // Returns array of [ text => 'El Rey Leon', href => 'http://cartelera.com/el-rey-leon' ].
	}

		/**
		 * Scrapes a single show from Cartelera.
		 *
		 * @param string $cartelera_url The URL of the show in Cartelera (https://carteleradeteatro.mx/2015/el-rey-leon/).
		 *  or the direct html to scrap.
		 * @return array
		 */
	public static function scrap_one_cartelera_show( string $cartelera_url ): array {

		// if the arg is a valid url we get the html from it.
		if ( ! filter_var( $cartelera_url, FILTER_VALIDATE_URL ) ) {
			$html             = $cartelera_url;
			$result_cartelera = [
				'url' => 'unknown',
			];
		} else {

			$result_cartelera = [
				'url' => $cartelera_url,
			];

			// now we retrieve the data from cartelera and compare it with the ticketmaster data.
			$html = self::get_html_from_url( $cartelera_url );
			if ( is_wp_error( $html ) ) {
				$result_cartelera['error'] = $html->get_error_message();
				$html = '';
			}
		}

		// start scrapping the html with DOM.
		$scraper = new Scraper_Cartelera( $html );

		/**
		 * SCRAP FIRST PART OF THE CONTENT (days of the month)
		 * ======================================================
		 */
		// Retrieve the text : `Del 6 abril al 8 de junio de 2025` or `2, 3 y 4 de mayo de 2025.`, or `En temporada 2025`.
		$nodes = $scraper->get_root()->query( '//div[@class="post-content-obras"]/p' );

		if ( $nodes->length > 0 ) {
			foreach ( $nodes as $node ) {
				// The first text with a date is the one we want. ( ie 25 de mayo de 2025 ).
				if ( Months_And_Days::text_contains_a_date( $node->textContent ) ) {
					// text 'En temporada 2025'.
					$result_cartelera['scraped_dates_text'] = Text_Sanization::sanitize_scraped_text(
						self::cleanup_node_text_content( $node )
					);
					break;
				}
			}
		}

		/**
		 * SCRAP SECOND PART OF THE CONTENT (timetable per days ...).
		 * ===============================================================
		 */
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase, WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		// Retrieve the text for the days, it's inside a <strong> :
		// 'Sábados de abril y mayo, 12:00 y 14:30 horas. Domingo 1 y 8 de junio, 12:00 y 14:30 horas'.
		$nodes = $scraper->get_root()->document->getElementsByTagName( 'strong' );
		foreach ( $nodes as $strong_node ) {

			if ( str_contains( $strong_node->textContent, 'Horario de' )
			|| str_contains( $strong_node->textContent, 'Horarios de' ) ) {

				// Get the text that is right after this <strong>, but before the next <br>.
				$time_text = '';
				if ( $strong_node && $strong_node->nextSibling ) {
					$next_node = $strong_node->nextSibling; // " Jueves y viernes 20:00 horas, sÃ¡bado 19:00 horas y domingo 18:00 horas.".
					if ( null === $next_node || ! isset( $next_node->nodeName ) ) {
						continue;
					}
					// we retrieve more nodes as long as it's not a <br>.
					while ( $next_node && $next_node->nodeName && 'br' !== $next_node->nodeName ) {
						if ( XML_TEXT_NODE === $next_node->nodeType || XML_ELEMENT_NODE === $next_node->nodeType ) {
							$time_text .= $next_node->textContent;
						}
						$next_node = $next_node->nextSibling; // move to the next brother until it's a br.
					}
					$time_text = trim( $time_text ); // ie 'Domingos 13:00 horas'.
				}

				$result_cartelera['scraped_time_text'] = Text_Sanization::sanitize_scraped_text( $time_text );

				break; // found the text with the times, we can exit.
			}
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase, WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		}

		return $result_cartelera;
	}
}
