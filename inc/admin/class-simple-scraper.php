<?php

/**
 * Static methods for easier scrapping.
 *
 * @package Cartelera_Scrap
 */

namespace Cartelera_Scrap;

use Cartelera_Scrap\Text_Parser;

use DOMDocument;
use DOMXPath;
use DOMElement;
use DOMNode;

/**
 * Class Simple_Scraper.
 *
 * A simple scraper class for easy access to DOM elements and values.
 * Usage: $scraper = new Simple_Scraper($html);
 * $titles = $scraper->get_texts('//h2[@class="post-title"]');
 */
class Simple_Scraper {


	/**
	 * @var DOMXPath $xpath The DOMXPath object for querying the DOM.
	 */
	protected DOMXPath $xpath;

	/**
	 * Constructor.
	 *
	 * @param string $html HTML content to parse.
	 */
	public function __construct( string $html ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		if ( ! empty( $html ) ) {
			$dom->loadHTML( $html );
		}
		libxml_clear_errors();
		$this->xpath = new DOMXPath( $dom );
	}

	/**
	 * Get the root DOMXPath object.
	 *
	 * @return DOMXPath
	 */
	public function get_root(): DOMXPath {
		return $this->xpath;
	}


	/**
	 * Helper: For debugging, show the content of the first node matching the XPath query.
	 *
	 * @param string      $query XPath query.
	 *
	 * @param DOMNodeList $nodes
	 * @param boolean     $asHTML
	 * @return void
	 */
	public static function debug_nodes( \DOMNodeList $nodes, $asHTML = false ): void {
		echo '<div style="background-color: #f0f0f0; padding: 10px; border-radius: 5px;">';
		echo '<h2>Debugging nodes</h2>';
		foreach ( $nodes as $node ) {
			if ( $asHTML && $node instanceof DOMElement ) {
				$doc      = new DOMDocument();
				$imported = $doc->importNode( $node, true );
				$doc->appendChild( $imported );
				echo "------------------------<br>\n";
				echo $doc->saveHTML() . PHP_EOL;
				echo "------------------------<br>\n";
			} else {
				echo $node->textContent . PHP_EOL;
			}
		}
		echo '</div>';
	}

	/**
	 * Get text content from nodes matching the XPath query.
	 * NOT in use: TODELETE.
	 *
	 * @param string $query XPath query.
	 * @return array<string> List of text content.
	 */
	public function get_texts( string $query ): array {
		$results = [];
		$nodes   = $this->xpath->query( $query );
		foreach ( $nodes as $node ) {
			$results[] = trim( $node->nodeValue );
		}
		return $results;
	}

	/**
	 * Get text content from a specific node.
	 *
	 * @param DOMNode $node The node to get text from.
	 * @return string
	 */
	public static function cleanup_node_text( DOMNode $node ): string {
		// Remove unwanted characters and trim whitespace.
		$text = str_replace( [ "\r", "\n" ], ' ', $node->textContent );

		return trim( $text );
	}

	/**
	 * Remove accents and special characters from a string.
	 *
	 * @param string $titulo The text to clean up.
	 * @return string
	 */
	public static function remove_accents( string $titulo ): string {
		// Normalize characters with accents and the ñ.
		$titulo = strtr( $titulo, [
			'á' => 'a',
			'é' => 'e',
			'í' => 'i',
			'ó' => 'o',
			'ú' => 'u',
			'Á' => 'A',
			'É' => 'E',
			'Í' => 'I',
			'Ó' => 'O',
			'Ú' => 'U',
			'ñ' => 'n',
			'Ñ' => 'N',
		] );

		// Remove question marks, exclamation marks, and dashes.
		$titulo = preg_replace( '/[¡!¿?\-\—]/u', '', $titulo );

		// Also remove unnecessary spaces.
		$titulo = trim( preg_replace( '/\s+/', ' ', $titulo ) );

		return $titulo;
	}

	/**
	 * Sanitize text for scraping.
	 *
	 * @param string $texto The text to sanitize.
	 * @return string The sanitized text.
	 */
	public static function sanitize_scraped_text( string $texto ): string {
		// Decode HTML entities like &nbsp;, &aacute;, etc.
		$texto        = str_replace( '&nbsp;', ' ', $texto ); // Just in case.
		$replacements = [
			'Ã¡'     => 'á',
			'Ã©'     => 'é',
			'Ã­'     => 'í',
			'Ã³'     => 'ó',
			'Ãº'     => 'ú',
			'Ã'     => 'Á',
			'Ã‰'     => 'É',
			'Ã'     => 'Í',
			'Ã“'     => 'Ó',
			'Ãš'     => 'Ú',
			'Ã±'     => 'ñ',
			'Ã‘'     => 'Ñ',
			'Â¡'     => '¡',
			'Â¿'     => '¿',
			'Â«'     => '«',
			'Â»'     => '»',
			'Â·'     => '·',
			'Â´'     => '´',
			'Â°'     => '°',
			'Â¬'     => '¬',
			'Â'      => '',  // Cases like Â¡ or Â¿.
			'â¦'    => '…',  // Ellipsis.
			'â'    => '–',  // En dash.
			'â'    => '—',  // Em dash.
			'â'    => '“',  // Left double quotation mark.
			'â'    => '”',  // Right double quotation mark.
			'â'    => '‘',  // Left single quotation mark.
			'â'    => '’',  // Right single quotation mark.
			'â¢'    => '•',  // Bullet.
			'â¨'    => '',  // Unicode line break.
			'â'     => '-',  // Dashes.
			'âª'    => '',  // LRM.
			'â«'    => '',  // LRE.
			'â¬'    => '',  // PDF.
			'â­'    => '',  // RLE.
			'â®'    => '',  // RLM.
			'&npsp;' => ' ', // Non-breaking space.
		];

		$texto = str_replace( array_keys( $replacements ), array_values( $replacements ), $texto );

		// Clean up multiple spaces and trim.
		return trim( $texto );
	}

	/**
	 * Get texts and hrefs from nodes matching the XPath query.
	 *
	 * @param string $query XPath query.
	 * @return array List of texts and hrefs.
	 */
	public function get_texts_and_hrefs( string $query ): array {
		$results = [];
		$nodes   = $this->xpath->query( $query );
		foreach ( $nodes as $node ) {
			$text = self::sanitize_scraped_text( $node->nodeValue );
			$text = self::remove_accents( $text );

			$href      = $node instanceof DOMElement ? $node->getAttribute( 'href' ) : '';
			$results[] = [
				'text' => $text,
				'href' => $href,
			];
		}
		return $results;
	}

	/**
	 * Scrapes all shows listed in the cartelera.
	 *
	 * This function retrieves the HTML content from the cartelera URL,
	 * parses it, and extracts the titles and links of all shows listed
	 * in the specified section of the page.
	 *
	 * @return array|WP_Error An array of shows with their titles and links, or a WP_Error object on failure.
	 */
	public static function scrap_all_shows_in_cartelera(): array|\WP_Error {
		$html = wp_remote_get( Cartelera_Scrap_Plugin::get_cartelera_url() );
		$html = wp_remote_retrieve_body( ( $html && ! is_wp_error( $html ) ) ? $html : '' );
		if ( is_wp_error( $html ) ) {
			return new \WP_Error( 'cartelera_url_error', 'Error retrieving cartelera URL.' );
		} elseif ( ! $html ) {
			return new \WP_Error( 'empty_response', 'Empty response from cartelera URL.' );
		}

		// Start scrapping the HTML with DOM.
		$scraper = new Simple_Scraper( $html );
		$shows   = $scraper->get_texts_and_hrefs( "//div[@id='content-obras']//li/a[1]" );

		return $shows; // Returns array of [ text => 'El Rey Leon', href => 'http://cartelera.com/el-rey-leon' ].
	}

	/**
	 * Scrapes a single show from Ticketmaster.
	 * ie https://www.ticketmaster.com.mx/search?q=el+rey+leon
	 *
	 * @param string $url the url in tickermaster to scrap. (https://www.ticketmaster.com.mx/search?q=el+rey+leon)
	 * @return array|\WP_Error
	 */
	public static function scrap_one_tickermaster_show( string $url ): array|\WP_Error {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$html = $url;
			$url  = 'unknown';
		} else {
			$html = wp_remote_get( $url );
			$html = wp_remote_retrieve_body( ( $html && ! is_wp_error( $html ) ) ? $html : '' );
			if ( is_wp_error( $html ) ) {
				return new \WP_Error( 'ticketmaster_url_error', 'Error retrieving ticketmaster URL.' );
			} elseif ( ! $html ) {
				return new \WP_Error( 'empty_response', 'Empty response from ticketmaster URL.' );
			}
		}

		// Start scrapping the HTML with DOM.
		$scraper  = new Simple_Scraper( $html );
		$li_nodes = $scraper->get_root()->query( '//ul[@data-testid="eventList"]/li' );

		$result_tickermaster = [
			'url'   => $url, // https://www.ticketmaster.com.mx/search?q=el+rey+leon
			'dates' => [],
		];
		foreach ( $li_nodes as $i => $li_item ) {

			$div           = $li_item->firstChild;
			$div           = $div->firstChild;
			$all_divs      = $div->getElementsByTagName( 'div' );
			$all_spans     = $div->getElementsByTagName( 'span' );
			$printed_date  = $all_divs ? $all_divs->item( 0 ) : null; // may25
			$complete_date = $all_spans ? $all_spans->item( 0 ) : null;
			$time_12h      = $all_spans ? $all_spans->item( 10 ) : null; // 8:30 p.m. (the span number 10th)

			$date_object_for_time = ! empty( $time_12h->textContent ) ?
				\DateTime::createFromFormat( 'g:i a', str_replace( '.', '', strtolower( $time_12h->textContent ) ) ) : false;

			$time_24h = $date_object_for_time ?
			$date_object_for_time->format( Text_Parser::TIME_COMPARE_FORMAT ) : '❌ Not found';

			$formatted_date = \DateTime::createFromFormat( 'd/m/y', $complete_date->textContent );
			$formatted_date = $formatted_date ? $formatted_date->format( 'Y-m-d' ) : null;

			$result_tickermaster['dates'][] = [
				'printed_date' => $printed_date->textContent, // may25
				'time_12h'     => $time_12h ? $time_12h->textContent : '❌ Not found', // 8:30 p.m
				// 'date'         => $complete_date->textContent, //
				'date'         => $formatted_date,
				'time'         => $time_24h, // 20:30
			];
		}

		return $result_tickermaster;
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
			$html = wp_remote_get( $cartelera_url );
			$html = wp_remote_retrieve_body( ( $html && ! is_wp_error( $html ) ) ? $html : '' );
			if ( is_wp_error( $html ) ) {
				$result_cartelera['error'] = 'Error retrieving cartelera URL: ' . $html->get_error_message();
			} elseif ( ! $html ) {
				$result_cartelera['error'] = 'Empty response from cartelera URL.';
			}
		}


		// start scrapping the html with DOM.;
		$scraper = new Simple_Scraper( $html );

		/**
		 * SCRAP FIRST PART OF THE CONTENT (days of the month)
		 * ======================================================
		 */
		// Retrieve the text : `Del 6 abril al 8 de junio de 2025` or `2, 3 y 4 de mayo de 2025.`, or `En temporada 2025.`
		$nodes = $scraper->get_root()->query( '//div[@class="post-content-obras"]/p' );

		if ( $nodes->length > 0 ) {
			foreach ( $nodes as $node ) {
				// The first text with a date is the one we want. ( ie 25 de mayo de 2025 )
				if ( Text_Parser::text_contains_a_date( $node->textContent ) ) {
					// text 'En temporada 2025.'
					$result_cartelera['scraped_dates_text'] = self::sanitize_scraped_text( self::cleanup_node_text( $node ) );
					break;
				}
			}
		}

		/**
		 * SCRAP SECOND PART OF THE CONTENT (timetable per days ...)
		 * ===============================================================
		 */
		// Retrieve the text for the days, it's inside a <strong> :
		// 'Sábados de abril y mayo, 12:00 y 14:30 horas. Domingo 1 y 8 de junio, 12:00 y 14:30 horas'
		$nodes = $scraper->get_root()->document->getElementsByTagName( 'strong' );
		foreach ( $nodes as $i => $strongNode ) {

			if ( str_contains( $strongNode->textContent, 'Horario de' )
			|| str_contains( $strongNode->textContent, 'Horarios de' ) ) {

				// Get the text that is right after this <strong>, but before the next <br>.
				$time_text = '';
				if ( $strongNode && $strongNode->nextSibling ) {
					$nextNode = $strongNode->nextSibling;
					while ( $nextNode && $nextNode->nodeName !== 'br' ) {
						if ( $nextNode->nodeType === XML_TEXT_NODE || $nextNode->nodeType === XML_ELEMENT_NODE ) {
							$time_text .= $nextNode->textContent;
						}
						$nextNode = $nextNode->nextSibling;
					}
					$time_text = trim( $time_text ); // ie 'Domingos 13:00 horas'
				}

				$result_cartelera['scraped_time_text'] = self::sanitize_scraped_text( $time_text );

				break; // found the text with the times, we can exit.
			}
		}

		return $result_cartelera;
	}
}
