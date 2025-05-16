<?php // phpcs:disable Generic.Commenting.DocComment.MissingShort
/**
 * Performs a specific operation or functionality.
 *
 * Parent class with generic scraping tasks.
 *
 * @package Cartelera_Scrap
 * @subpackage Scraper
 */

namespace Cartelera_Scrap\Scraper;

use Cartelera_Scrap\Parse_Text_Into_Dates;
use Cartelera_Scrap\Helpers\Text_Sanization;

use DOMDocument;
use DOMXPath;
use DOMElement;
use DOMNode;

/**
 * Class Scraper.
 *  Usage: $scraper = new Scraper($html); , but we use the children classes instead.
 */
class Scraper {

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
	 * Reusable fn. from a url, retrieves its html source code
	 *
	 * @param string $url any url to retrieve the flat html.
	 * @return string|\WP_Error
	 */
	public static function get_html_from_url( string $url ): string|\WP_Error {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$html = wp_remote_get( $url );
		$html = wp_remote_retrieve_body( ( $html && ! is_wp_error( $html ) ) ? $html : '' );
		if ( is_wp_error( $html ) ) {
			return new \WP_Error( 'ticketmaster_url_error', 'Error retrieving ticketmaster URL.' . ( $html instanceof \WP_Error ? $html->get_error_message() : '' ) );
		} elseif ( empty( $html ) ) {
			return new \WP_Error( 'empty_response', 'Empty response from URL: ' . $url );
		}
		return $html;
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
	 * @param \DOMNodeList $nodes the list of nodes.
	 * @param boolean      $as_html if we print in the frontend. false for console output.
	 * @return void
	 */
	public static function debug_nodes( \DOMNodeList $nodes, $as_html = false ): void {
		echo '<div style="background-color: #f0f0f0; padding: 10px; border-radius: 5px;">' . PHP_EOL;
		echo '<h2>Debugging nodes</h2>' . PHP_EOL;
		foreach ( $nodes as $node ) {
			if ( $as_html && $node instanceof DOMElement ) {
				$doc      = new DOMDocument();
				$imported = $doc->importNode( $node, true );
				$doc->appendChild( $imported );
				echo esc_html( "------------------------<br>\n" );
				echo esc_html( $doc->saveHTML() ) . PHP_EOL;
				echo esc_html( "------------------------<br>\n" );
			} else {
				echo esc_html( $node->textContent ) . PHP_EOL;
			}
		}
		echo '</div>';
	}

	/**
	 * Get text content from a specific node, sanitized.
	 * Remove unwanted characters and trim whitespace.
	 *
	 * @param DOMNode $node The node to get text from.
	 * @return string
	 */
	public static function cleanup_node_text_content( DOMNode $node ): string {
		return trim( str_replace( [ "\r", "\n" ], ' ', $node->textContent ) );
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
			$text = Text_Sanization::sanitize_scraped_text( $node->nodeValue );
			$text = Text_Sanization::remove_accents( $text );

			$href      = $node instanceof DOMElement ? $node->getAttribute( 'href' ) : '';
			$results[] = [
				'text' => $text,
				'href' => $href,
			];
		}
		return $results;
	}
}
