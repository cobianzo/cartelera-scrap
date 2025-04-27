<?php
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

	/**
	 * Helper function to load the HTML content from a file and extract data using the scraper.
	 *
	 * @param string $filepath
	 * @return array
	 */
	public static function get_file_contents_html_file( WP_UnitTestCase $instance, string $filepath ): array {

		if ( ! file_exists( $filepath ) ) {
			return [ 'error' => '❌File not found: ' . $filepath ];
		}

		$html_content = file_get_contents( $filepath );

		if ( $html_content === false ) {
			return [ 'error' => '❌Failed to read file contents: ' . $filepath ];
		}

		$instance->assertNotFalse( $html_content, '❌Failed to load HTML content from file ' . $filepath );

		// The action !!!
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

	public function scrap_and_test_cartelera_file( string $cartelera_filename, string $expected_dates_text, string $expected_time_text ) {
		$data_example_file       = __DIR__ . '/data/' . $cartelera_filename;
		$scrapped_data_extracted = self::get_file_contents_html_file( $this, $data_example_file );
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

		return true;
	}

	/**
	 * Test 1. Test the HTML content from a simple cartelera page.
	 */
	public function test_html_from_cartelera_page_parses_correctly_date_and_time() {
		echo "\n ======= TEST 2.1 START 🎬 🤯========";

		$completed = $this->scrap_and_test_cartelera_file(
			'cartelera-single-show-page.html',
			'Del 24 de abril al 8 de junio de 2025 (Suspende 1, 10 y 15 de mayo)',
			'Jueves y viernes, 20:00 horas, sábados 19:00 horas y domingos 18:00 horas.'
		);

		echo true === $completed ? "\n\n✅✅✅ Test passed 1. \n\n" : "\n\n❌❌❌ Test 1 didnt complete. \n $completed \n";
	}


	/**
	 * Test 2. @testdox Test the HTML content from a different cartelera page, more delicated. PD:
	 * testdox doesnt work when I call it with `phpunit --testdox`  .
	 */
	public function test_DELICATED_html_from_cartelera_page_parses_correctly_date_and_time() {
		echo "\n ======= TEST 2.2 START 🎬 🤯========";

		$completed = $this->scrap_and_test_cartelera_file(
			'cartelera-single-show-page-2.html',
			'En temporada 2025.',
			'Jueves 20:00 horas, viernes 19:00 y 21:00 horas, sábado 18:00 y 20:00 horas y domingo 17:00 y 19:00 horas.'
		);

		echo true === $completed ? "\n\n✅✅✅ Test passed 2. \n\n" : "\n\n❌❌❌ Test 2 didnt complete. \n $completed \n";
	}
}
