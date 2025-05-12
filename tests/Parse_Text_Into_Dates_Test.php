<?php

use Cartelera_Scrap\Scraper\Scraper;
use Cartelera_Scrap\Scraper\Scraper_Ticketmaster;
use Cartelera_Scrap\Scraper\Scraper_Cartelera;
use Cartelera_Scrap\Helpers\Text_Sanization;

/**
 * Tests dedicated to check if we can scrap and retrieve the text that
 * we need.
 *
 * TODO:-
 * test the retrieval of all shows from the cartelera page.
 * test the retrieval of dates from ticketmaster page.
 */
class Parse_Text_Into_Dates_Test extends WP_UnitTestCase {

	public function test_one_result_analysis() {
		echo "\n ======= TEST XXXXX Ticketmaster START 🎬 🤯========" . PHP_EOL;
		$filename             = 'ticketmaster-single-show-page-3.html';
		$tm_html_example_file = __DIR__ . '/data/' . $filename;
		$tm_html_page         = ScrapTest::get_file_contents_html_file( $this, $tm_html_example_file );

		$result_tickermaster = Scraper_Ticketmaster::scrap_one_tickermaster_show( $tm_html_page );

		echo '----------------------' .PHP_EOL;
		echo '----------------------' .PHP_EOL;
		echo '----------------------' .PHP_EOL;
		echo '$result_tickermaster = ';
		print_r( $result_tickermaster );
		echo '----------------------' .PHP_EOL;
		echo '----------------------' .PHP_EOL;
		echo '----------------------' .PHP_EOL;

		$filename             = 'cartelera-single-show-page-3.html';
		$ca_html_example_file = __DIR__ . '/data/' . $filename;
		$ca_html_page         = ScrapTest::get_file_contents_html_file( $this, $ca_html_example_file );

		$result_cartelera = Scraper_Cartelera::scrap_one_cartelera_show( $ca_html_page );


		echo '----------------------' .PHP_EOL;
		echo '----------------------' .PHP_EOL;
		echo '----------------------' .PHP_EOL;
		echo '$result_cartelera = ';
		print_r( $result_cartelera );
		echo '----------------------' .PHP_EOL;
		echo '----------------------' .PHP_EOL;
		echo '----------------------' .PHP_EOL;


		$result = [
			'title'        => Text_Sanization::sanitize_scraped_text( 'No es relevante' ),
			'cartelera'    => $result_cartelera,
			'ticketmaster' => $result_tickermaster,
		];
		print_r($result);




		echo '✅ test XXXX completed';
	}

	public function test_many_many_result_analysis() {
		echo "\n ======= TEST 2 : XXXXX Ticketmaster START 🎬 🤯========" . PHP_EOL;

		$json_file = __DIR__ . '/data/results-db.json';
		$json_text = file_get_contents( $json_file );
		$results   = json_decode( $json_text, true );

		// we will tet out unit functions for every result, checking that the result is the expected


		// 1. Test 'separate_dates_sentences' using intro $result['cartelera']['scraped_dates_text']

		// 2. Test 'sanitize_dates_sentence' using intro the result of the previous.

		// 3. Test both: first_acceptance_of_dates_text using intro param $result['cartelera']['scraped_dates_text']

		// 4. Test 'first_acceptance_of_times_text' using intro param $result['cartelera']['scraped_time_text']

		// 5 Test







		print_r($results);

		echo '✅ test 2: XXXX completed';
	}


	public static function full_analysys_computed_data_of_result( array $result ): array {
		// 1. Test 'separate_dates_sentences' using intro $result['cartelera']['scraped_dates_text']
		// 2. Test 'sanitize_dates_sentence' using intro the result of the previous.
		// 3. Test both: first_acceptance_of_times_text using intro param $result['cartelera']['scraped_dates_text']
		// 4. Test 'first_acceptance_of_times_text' using intro param $result['cartelera']['scraped_time_text']
		// 5 Test

		// 1. Cartelera analysis

		// Del 16 abril al 18 mayo de 2025.
		$dates_text = $result['cartelera']['scraped_dates_text'];


	}

}
