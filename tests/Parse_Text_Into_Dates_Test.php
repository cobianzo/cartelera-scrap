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

	/** helper 1/3
	 * Reusable for cartelera scrap and ticketmaster scrap
	 */
	public static function get_file_contents_html_file( WP_UnitTestCase $instance, string $filepath ): string {
		if ( ! file_exists( $filepath ) ) {
			return [ 'error' => '❌File not found: ' . $filepath ];
		}

		$html_content = file_get_contents( $filepath );

		if ( $html_content === false ) {
			return [ 'error' => '❌Failed to read file contents: ' . $filepath ];
		}

		$instance->assertNotFalse( $html_content, '❌Failed to load HTML content from file ' . $filepath );

		return $html_content;
	}
	/**
	 * Helper 2/3 function to load the HTML content from a file and extract data using the scraper.
	 *
	 * @param string $filepath
	 * @return array
	 */
	public static function get_file_contents_html_file_and_scrap_cartelera( WP_UnitTestCase $instance, string $filepath ): array {

		$html_content = $instance->get_file_contents_html_file( $instance, $filepath );

		// The action !!!
		// ===============
		$scrapped_data_extracted = Scraper_Cartelera::scrap_one_cartelera_show( $html_content );


		$instance->assertNotEmpty( $scrapped_data_extracted, 'Failed to extract data from HTML content' );
		$instance->assertArrayHasKey(
			'scraped_dates_text', $scrapped_data_extracted,
			'❌dates text not found in extracted data. Check `scrap_one_cartelera_show` function' . PHP_EOL
			. print_r( $scrapped_data_extracted, 1 )
		);
		$instance->assertArrayHasKey( 'scraped_time_text', $scrapped_data_extracted, '❌time text not found in extracted data' . print_r( $scrapped_data_extracted, 1 ) );

		return $scrapped_data_extracted;
	}

	/**
	 * Helper 3/3
	 *
	 * @param string $cartelera_filename
	 * @param string $expected_dates_text
	 * @param string $expected_time_text
	 * @return array
	 */
	public function scrap_and_test_cartelera_file( string $cartelera_filename, string $expected_dates_text, string $expected_time_text ): array {
		$data_example_file       = __DIR__ . '/data/' . $cartelera_filename;
		$scrapped_data_extracted = self::get_file_contents_html_file_and_scrap_cartelera( $this, $data_example_file );
		if ( ! empty( $scrapped_data_extracted['error'] ) ) {
			return $scrapped_data_extracted['error'];
		}

		$this->assertArrayHasKey( 'scraped_dates_text', $scrapped_data_extracted );
		$this->assertEquals(
			$expected_dates_text,
			$scrapped_data_extracted['scraped_dates_text'],
			'❌The scraped dates text does not match the expected value: ' . $scrapped_data_extracted['scraped_dates_text']
		);
		$this->assertArrayHasKey( 'scraped_time_text', $scrapped_data_extracted );
		$this->assertEquals(
			$expected_time_text,
			$scrapped_data_extracted['scraped_time_text'],
			'❌The scraped time text does not match the expected value:' . $scrapped_data_extracted['scraped_time_text']
		);

		return $scrapped_data_extracted;
	}


	public function test_one_result_analysis() {
		echo "\n ======= TEST XXXXX Ticketmaster START 🎬 🤯========" . PHP_EOL;
		$filename             = 'ticketmaster-single-show-page-3.html';
		$tm_html_example_file = __DIR__ . '/data/' . $filename;
		$tm_html_page         = self::get_file_contents_html_file( $this, $tm_html_example_file );

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
		$ca_html_page         = self::get_file_contents_html_file( $this, $ca_html_example_file );

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
