<?php

use Cartelera_Scrap\Simple_Scraper;

/**
 * Tests dedicated to check if we can scrap and retrieve the text that
 * we need.
 *
 * TODO:-
 * test the retrieval of all shows from the cartelera page.
 * test the retrieval of dates from ticketmaster page.
 */
class ScrapTest extends WP_UnitTestCase {

	public static function deb( $var ) {
		echo "=====================\n";
		echo "=====================\n";
		echo "=====================\n";
		print_r( $var );
		echo "----------------------\n";
		echo "----------------------\n";
		echo "----------------------\n";
		echo "----------------------\n";
	}

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
		$scrapped_data_extracted = \Cartelera_Scrap\Simple_Scraper::scrap_one_cartelera_show( $html_content );


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

	/**
	 * Test 1. Test the HTML content from a simple cartelera page.
	 */
	public function test_html_from_cartelera_page_parses_correctly_date_and_time() {
		echo "\n ======= TEST 2.1 Cartelera scrap START 🎬 🤯========";

		$result = $this->scrap_and_test_cartelera_file(
			'cartelera-single-show-page.html',
			'Del 24 de abril al 8 de junio de 2025 (Suspende 1, 10 y 15 de mayo)',
			'Jueves y viernes, 20:00 horas, sábados 19:00 horas y domingos 18:00 horas.'
		);
	}


	/**
	 * Test 2. @testdox Test the HTML content from a different cartelera page, more delicated. PD:
	 * testdox doesnt work when I call it with `phpunit --testdox`  .
	 *
	 * NOTE: I use this test to see the output more than confirming that everything is ok.
	 */
	public function test_DELICATED_html_from_cartelera_page_parses_correctly_date_and_time() {
		echo "\n ======= TEST 2.2 Cartelera scrap START 🎬 🤯========";

		$result = $this->scrap_and_test_cartelera_file(
			'cartelera-single-show-page-2.html',
			'En temporada 2025.',
			'Jueves 20:00 horas, viernes 19:00 y 21:00 horas, sábado 18:00 y 20:00 horas y domingo 17:00 y 19:00 horas.'
		);
	}

	public function test_html_from_TICKETMASTER_page_parses_correctly_date_and_time() {
		echo "\n ======= TEST 2.3 Ticketmaster START 🎬 🤯========";
		$filename             = 'ticketmaster-single-show-page-3.html';
		$tm_html_example_file = __DIR__ . '/data/' . $filename;
		$tm_html_page = self::get_file_contents_html_file( $this, $tm_html_example_file );

		$result_tickermaster = Simple_Scraper::scrap_one_tickermaster_show( $tm_html_page );
		echo '$result_tickermaster = ';
		print_r( $result_tickermaster );

		$this->assertCount( 2, $result_tickermaster['dates'], '❌ - Error. Expected two dates from scrapping ticketmaster file' . $filename . ': ' . count( $result_tickermaster['dates'] ) );
		$this->assertEquals( '2025-04-30', $result_tickermaster['dates'][0]['date'], '❌ - Error. date from tickermaster scrapped is not the expected' );
		$this->assertEquals( '2025-05-07', $result_tickermaster['dates'][1]['date'], '❌ - Error. date from tickermaster scrapped is not the expected' );
		$this->assertEquals( '20:00', $result_tickermaster['dates'][0]['time'], '❌ - Error. time from tickermaster scrapped is not the expected' );
		$this->assertEquals( '20:00', $result_tickermaster['dates'][1]['time'], '❌ - Error. time from tickermaster scrapped is not the expected' );

		echo '✅ test 2.3 completed';
	}

	public function test_html_from_TICKETMASTER_page_parses_correctly_number_of_results() {
		echo "\n ======= TEST 2.4 Ticketmaster START 🎬 🤯========";
		$filename             = 'ticketmaster-single-show-page-4.html';
		$tm_html_example_file = __DIR__ . '/data/' . $filename;
		$tm_html_page = self::get_file_contents_html_file( $this, $tm_html_example_file );

		$scrapper = new Simple_Scraper( $tm_html_page );
		$scrapper->ticketmaster_scrap_number_results();
		echo '$result_tickermaster = ';
		// print_r( $result_tickermaster );


		echo '✅ test 2.4 completed';

	}

}
