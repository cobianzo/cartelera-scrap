<?php

use Cartelera_Scrap\Parse_Text_Into_Dates;
use Cartelera_Scrap\Helpers\Results_To_Save;
use Cartelera_Scrap\Helpers\Text_Sanization;
use Cartelera_Scrap\Scraper\Scraper_Ticketmaster;
use LDAP\Result;

/**
 * usage
 * npm run test:php tests/E2ETest.php
 * or
 * npm run test:php:single
 */
class E2ETest extends WP_UnitTestCase {


	/**
	 * Test e2e. This is a good example to understand what the plugin does.
	 */
	public function test_full_scrap_and_analize() {
		echo "\n ======= TEST E2E 4.1 START 🎬 🤯========";

		if ( ! class_exists( 'ScrapTest' ) ) {
			require __DIR__ . '/ScrapTest.php';
		}

		// mocked html files to scrap (instead of urls)
		$tm_mocked_page_html   = 'ticketmaster-single-show-page-3.html';
		$cart_mocked_page_html = 'cartelera-single-show-page-3.html';

		/**
		 * List o fthe expected results for the scrapping
		 */
		$show_title = 'Las cuatro estaciones de Vivaldi';

		$result_text_date = '22 de junio de 2025.';
		$result_text_time = 'Domingo 12:00 horas.';

		/**
		 * Expected results for Text processing
		 */
		$first_accpted_sentence_date = '22-junio-2025';
		$first_accpted_sentence_time = 'sunday-12:00';

		$first_extracted_date     = '2025-06-22';
		$first_extracted_datetime = '2025-06-22 12:00';

		echo "\n ======= Step 1. retrieve texts from cartelera (Scraper_Cartelera::scrap_one_cartelera_show)🎬 🤯========";
		$result_cartelera = ( new ScrapTest() )->scrap_and_test_cartelera_file(
			$cart_mocked_page_html,
			$result_text_date,
			$result_text_time
		);
		echo '$result_cartelera =';
		print_r( $result_cartelera );
		/*
		Output
		(
			[url] => unknown
			[scraped_dates_text] => 22 de junio de 2025.
			[scraped_time_text] => Domingo 12:00 horas.
		)
		*/

		echo "\n ======= Step 2. retrieve texts from ticketmaster (Scraper_Ticketmaster::scrap_ one_ tickermaster_ show)🎬 🤯========";
		$filepath            = __DIR__ . "/data/$tm_mocked_page_html";
		$html_content        = file_get_contents( $filepath );
		$result_tickermaster = Scraper_Ticketmaster::scrap_one_tickermaster_show( $html_content );
		echo '$result_tickermaster =';
		print_r( $result_tickermaster );
		/*
		Output
		(
		[url] => unknown
		[dates] => Array
		(
			[0] => Array
				(
					[printed_date] => jun22
					[time_12h] => 12:00 p.m.
					[date] => 2025-06-22
					[time] => 12:00pl
				)

		)
		*/


		$this->assertEquals( '2025-06-22', $result_tickermaster['dates'][0]['date'], '❌ - Error. date from tickermaster scrapped is not the expected' );
		$this->assertEquals( '12:00', $result_tickermaster['dates'][0]['time'], '❌ - Error. time from tickermaster scrapped is not the expected' );

		echo "\n ======= Step3. Save the result int results option in DB🎬 🤯========";
		$saved_result = [
			'title'        => Text_Sanization::sanitize_scraped_text( $show_title ),
			'cartelera'    => $result_cartelera,
			'ticketmaster' => $result_tickermaster,
		];
		Results_To_Save::save_show_result( $saved_result );

		$show_results                 = Results_To_Save::get_show_results();
		$first_show_result_to_process = $show_results[0];
		$this->assertEquals( $saved_result, $first_show_result_to_process, '❌ - Error. The saved result does not match the first element of show results.' );
		echo PHP_EOL . 'Saved on DB';
		print_r( $first_show_result_to_process );
		// Output
		/*
		(
		[title] => Las cuatro estaciones de Vivaldi
		[cartelera] => Array
		(
			[url] => unknown
			[scraped_dates_text] => 22 de junio de 2025.
			[scraped_time_text] => Domingo 12:00 horas.
		)

		[ticketmaster] => Array
		(
			[url] => unknown
			[dates] => Array
				(
					[0] => Array
						(
							[printed_date] => jun22
							[time_12h] => 12:00 p.m.
							[date] => 2025-06-22
							[time] => 12:00
						)
				)
		)
		)
		*/

		// NOW, with the result in the DB, we perform the transformations and checks which are shown
		// in class-scrap-output.php
		echo "\n ======= Step4. Get the simplified senteces for day dates, from cartelera🎬 🤯========";
		echo "\n ======= shown in settings page with Scrap_Output::render_col_cartelera_text_datetimes ========";
		$cartelera_dates_text = $first_show_result_to_process['cartelera']['scraped_dates_text'];
		$date_sentences       = Parse_Text_Into_Dates::first_acceptance_of_date_text( $cartelera_dates_text );

		$this->assertIsArray( $date_sentences, '❌ - Error. $date_sentences is not an array.' . print_r( $date_sentences, 1 ) );
		$this->assertEquals(
			$first_accpted_sentence_date,
			$date_sentences[0],
			'❌ - Error. The first accepted sentence date does not match the expected value.' . print_r( $date_sentences, 1 )
		);

		print_r( $date_sentences );
		// Output
		/*
		(
		[0] => 22-junio-2025
		)
		*/



		echo "\n ======= Step5. Get the simplified senteces for day of the week and time, from cartelera🎬 🤯========";
		echo "\n ======= shown in settings page with Scrap_Output::render_times_parsed_sentences ========";
		$cartelera_time_text = $first_show_result_to_process['cartelera']['scraped_time_text'];
		$time_sentences      = Parse_Text_Into_Dates::first_acceptance_of_times_text( $cartelera_time_text );
		// these two things do more or less the same thing (render_... is wrapper of the first_acceptance...)
		// $sentences_cartelera_dates = Scrap_Output::render_col_cartelera_text_datetimes( $first_show_result_to_process );
		print_r( $time_sentences );
		// Output
		/*
		(
		[0] => sunday-12:00
		)
		 */
		$this->assertIsArray( $time_sentences, '❌ - Error. $time_sentences is not an array.' . print_r( $time_sentences, 1 ) );
		$this->assertEquals(
			$first_accpted_sentence_time,
			$time_sentences[0],
			'❌ - Error. The first accepted sentence time does not match the expected value.' . print_r( $time_sentences, 1 )
		);



		echo "\n ======= Step6. Get the simplified senteces for day of the week and time, from cartelera🎬 🤯========";
		// TODO: test dates in cartelera with
		// converting 4-11-18-mayo-2025 into array of dates [2025-5-11, ...]
		$datesYYYYMMDD = Parse_Text_Into_Dates::identify_dates_sentence_daterange_or_singledays( $first_accpted_sentence_date );
		print_r( $datesYYYYMMDD );
		// Output
		/*
		(
		[0] => 2025-06-22
		[1] => 2025-06-29
		[2] => 2025-06-01
		)
		 */
		$this->assertCount( 1, $datesYYYYMMDD, '❌ - Error. The count of the result of identify_dates_sentence_daterange_or_singledays is not 1, but ' . count( $datesYYYYMMDD ) );
		$this->assertEquals( $datesYYYYMMDD[0], $first_extracted_date, '❌ - Error. The extracted date is not the expected. From ' . $first_accpted_sentence_date . ' we got ' . print_r( $datesYYYYMMDD, 1 ) . PHP_EOL . 'You need to refine "identify_dates_sentence_daterange_or_singledays" ' );


		// Test the creation of the definitive datetimes of cartelera
		echo "\n ======= Step7. Create the definitive dates and times, from cartelera🎬 🤯========";
		$datetimes = Parse_Text_Into_Dates::definitive_dates_and_times( [ $datesYYYYMMDD ], $time_sentences );
		print_r( $datetimes );
		$this->assertCount( 1, $datetimes, '❌ - Error. The count of the result of definitive_dates_and_times is not 1, but ' . count( $datetimes ) );
		$this->assertEquals( $datetimes, [ $first_extracted_datetime ], '❌ - Error. The extracted datetime is not the expected' . print_r( $datetimes, 1 ) );

		// I can't test in this example Parse_Text_Into_Dates::remove_dates_previous_of_today

		// Last test:
		echo "\n ======= Step8. Compare ticketmaster dates and cartelera datesa (Parse_Text_Into_Dates::compare_arrays)🎬 🤯========";
		$tm_dates       = array_map( fn( $tm_record ) => $tm_record['date'] . ' ' . $tm_record['time'], $result_tickermaster['dates'] );
		$result_compare = Parse_Text_Into_Dates::compare_arrays( $datetimes, $tm_dates );
		print_r( $result_compare );
	}
}
