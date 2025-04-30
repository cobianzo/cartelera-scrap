<?php
use Cartelera_Scrap\Text_Parser;

class TextConvertTest extends WP_UnitTestCase {

	private function date_range( $start, $end ): array {
		$dates    = [];
		$start_ts = strtotime( $start );
		$end_ts   = strtotime( $end );
		while ( $start_ts <= $end_ts ) {
			$dates[]  = date( 'Y-m-d', $start_ts );
			$start_ts = strtotime( '+1 day', $start_ts );
		}
		return $dates;
	}

	private function evaluate_case( string $label, array $expected ): void {
		$result = Text_Parser::identify_dates_sentence_daterange_or_singledays( $label );
		echo "\n--- [$label] ---\n";
		$this->assertEquals( $expected, $result, 'Error evaluating ' . $label . '  ' );
	}

	public function test_all_date_formats() {
		$path = __DIR__ . '/data/sentences-to-dates.json';

		if ( ! file_exists( $path ) ) {
			die( "Archivo no encontrado: $path" );
		}

		$json = file_get_contents( $path );
		$data = json_decode( $json, true ); // true => devuelve array asociativo

		if ( json_last_error() !== JSON_ERROR_NONE ) {
				die( 'Error al decodificar JSON: ' . json_last_error_msg() );
		}

		foreach ( $data as $sentence => $all_expected_dates ) {
			echo PHP_EOL . "----- Test $sentence " . PHP_EOL . PHP_EOL;
			echo '----- Expecting ' . print_r( $all_expected_dates, 1 ) . PHP_EOL;
			$this->evaluate_case( $sentence, $all_expected_dates );
		}
	}
}
