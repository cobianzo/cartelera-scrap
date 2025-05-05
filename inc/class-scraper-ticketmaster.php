<?php
/**
 * Static methods for easier scrapping from ticketmaster webpages.
 *
 * @package Cartelera_Scrap
 */

namespace Cartelera_Scrap\Scraper;

use Cartelera_Scrap\Cartelera_Scrap_Plugin;
use Cartelera_Scrap\Scraper\Scraper;
use Cartelera_Scrap\Parse_Text_Into_Dates;
use Cartelera_Scrap\Helpers\Text_Sanization;

use DOMDocument;
use DOMXPath;
use DOMElement;
use DOMNode;

/**
 * Class Scraper_Ticketmaster
 *
 * A simple scraper class for easy access to DOM elements and values.
 * Usage: $scraper = new Scraper_Ticketmaster($html);
 */
class Scraper_Ticketmaster extends Scraper {

	/**
	 * According to a search criteria, and the scrapped text,
	 * we check if the search results are more than one.
	 *
	 * @param string $search_criteria the title of the show event ie 'La Cenicienta'.
	 * @return array array of title entries found.
	 */
	public function ticketmaster_scrap_number_results( string $search_criteria ): array {
		$root  = $this->xpath;
		$nodes = $root->query( '//main[@id="main-content"]//ul' );
		if ( ! $nodes->length ) {
			return [];
		}
		$node_with_thumbnails = $nodes->item( 0 );
		$thumbnails_on_header = $this->xpath->query( './/li/a', $node_with_thumbnails );

		// When we visit the results of a ticketmaster search normally we'll get only one occurrence.
		// but potentially there could be more.
		$search_criteria = strtolower( $search_criteria );
		$shows_results   = [];
		foreach ( $thumbnails_on_header as $i => $node ) {
			$href       = $node instanceof DOMElement ? $node->getAttribute( 'href' ) : $i;
			$paragraphs = $node instanceof DOMElement ? $node->getElementsByTagName( 'p' ) : null;
			foreach ( $paragraphs as $paragraph ) {
				$pararagraph_title_maybe = self::cleanup_node_text_content( $paragraph );
				if ( str_contains( strtolower( $pararagraph_title_maybe ), $search_criteria ) ) {
					if ( strtolower( $pararagraph_title_maybe ) === $search_criteria ) { // if the best result, we place it in 1st pos.
						$shows_results = array_merge( [ $href => $pararagraph_title_maybe ], $shows_results );
					} else {
						$shows_results[ $href ] = $pararagraph_title_maybe;
					}
				}
			}
		}

		// Now let's get the best result.
		return $shows_results;
	}

	/**
	 * Scraps a single show from Ticketmaster.
	 * $title ie : El Rey Leon (or directly the whole html of the page ... <!DOCTYPE html> <html lang="es">)...
	 *
	 * @param string $title to perform search in the url to scrap. (https://www.ticketmaster.com.mx/search?q=el+rey+leon).
	 * @return array|\WP_Error
	 */
	public static function scrap_one_tickermaster_show( string $title ): array|\WP_Error {
		if ( str_contains( $title, '!DOCTYPE' ) ) {
			// In case the title is the full html of the page.
			$html  = $title;
			$url   = 'unknown';
			$title = 'unknown';
		} else {
			$url = Cartelera_Scrap_Plugin::get_ticketmaster_url( $title ); // https://www.ticketmaster.com.mx/search?q=el+rey+leon.

			$html = self::get_html_from_url( $url );
			if ( is_wp_error( $html ) ) {
				return $html;
			}
		}

		// Start scrapping the HTML with DOM.
		$scraper = new Scraper_Ticketmaster( $html );

		// First, check the top of the page.
		// See if there is more than one show result with that name.
		$tm_found_results  = $scraper->ticketmaster_scrap_number_results( $title ); // [ href => title in tm ].
		$tm_show_page_link = array_keys( $tm_found_results )[0] ?? '';
		$tm_show_title     = $tm_found_results[ $tm_show_page_link ] ?? '';

		if ( count( $tm_found_results ) > 1 && $tm_show_page_link ) {
			// sometimes (rarely) the search results f tickermaster has more than one result, so it's not reliable.

			// converts /arte-boletos/artist/3512904 in https://www.ticketmaster.com.mx/arte-boletos/artist/3512904.
			$parts             = wp_parse_url( Cartelera_Scrap_Plugin::get_ticketmaster_url() );
			$port              = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
			$ticketmaster_url  = $parts['scheme'] . '://' . $parts['host'] . $port;
			$tm_show_page_link = $ticketmaster_url . $tm_show_page_link;

			$html = self::get_html_from_url( $tm_show_page_link );

			if ( is_wp_error( $html ) ) {
				return $html;
			}
			// update ticketmaster with the html of the single page, not the search results page.
			$scraper = new Scraper_Ticketmaster( $html );
		}

		$li_nodes = $scraper->get_root()->query( '//ul[@data-testid="eventList"]/li' );

		$result_tickermaster = [
			'url'             => $url, // https://www.ticketmaster.com.mx/search?q=el+rey+leon.
			'search_results'  => count( $tm_found_results ),
			'single_page_url' => $tm_show_page_link,
			'tm_title'        => $tm_show_title,   // @TODO: retrieve title from the ticketmaster scrapped text.
			'tm_titles_list'  => [], // different titles in the search results items in the tm page.
			'dates'           => [],

		];

		foreach ( $li_nodes as $i => $li_item ) {

			$div = $li_item->firstChild;
			$div = $div->firstChild;
			if ( $div instanceof DOMElement ) {
				$all_divs  = $div->getElementsByTagName( 'div' );
				$all_spans = $div->getElementsByTagName( 'span' );
			}
			if ( ! $all_divs || ! $all_spans ) {
				$result_tickermaster['dates'][] = [
					'printed_date' => 'unknown',
					'time_12h'     => 'unknown',
					'date'         => 'unknown',
					'time'         => 'unknown',
				];
				continue; // Skip this iteration if $div is not a DOMElement or $all_divs is null.
			}

			$printed_date  = $all_divs ? $all_divs->item( 0 ) : null; // may25.
			$complete_date = $all_spans ? $all_spans->item( 0 ) : null;
			$time_12h      = $all_spans ? $all_spans->item( 10 ) : null; // 8:30 p.m. (the span number 10th).
			$title_maybe_1 = $all_spans ? $all_spans->item( 13 ) : null;
			$title_maybe_2 = $all_spans ? $all_spans->item( 14 ) : null;

			if ( str_contains( strtolower( $title_maybe_1->textContent ), strtolower( $title ) ) &&
				! str_contains( strtolower( $title_maybe_2->textContent ), strtolower( $title ) ) ) {
					$title_maybe_2 = $title_maybe_1;
			}

			if ( ! in_array( $title_maybe_2->textContent, $result_tickermaster['tm_titles_list'], true ) ) {
				$result_tickermaster['tm_titles_list'][] = self::cleanup_node_text_content( $title_maybe_2 );
			}

			$date_object_for_time = ! empty( $time_12h->textContent ) ?
				\DateTime::createFromFormat( 'g:i a', str_replace( '.', '', strtolower( $time_12h->textContent ) ) ) : false;

			$time_24h = $date_object_for_time ?
			$date_object_for_time->format( Parse_Text_Into_Dates::TIME_COMPARE_FORMAT ) : '❌ Not found';

			$formatted_date = \DateTime::createFromFormat( 'd/m/y', $complete_date->textContent );
			$formatted_date = $formatted_date ? $formatted_date->format( 'Y-m-d' ) : null;

			$is_repeated_datetime = array_find( $result_tickermaster['dates'], function ( $result ) use ( $formatted_date, $time_24h ) {
				return ( $formatted_date === $result['date'] && $time_24h === $result['time'] );
			} );
			if ( ! $is_repeated_datetime ) {
				$result_tickermaster['dates'][] = [
					'printed_date' => $printed_date->textContent, // may25.
					'time_12h'     => $time_12h ? $time_12h->textContent : '❌ Not found', // 8:30 p.m  .
					'date'         => $formatted_date,
					'time'         => $time_24h, // 20:30   .
				];
			}
		}

		return $result_tickermaster;
	}
}
