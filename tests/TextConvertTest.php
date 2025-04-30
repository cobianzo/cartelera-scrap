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

	private function evaluate_case( string $label, string $input, array $expected ): void {
		$result = Text_Parser::identify_dates_sentence_daterange_or_singledays( $input );
		echo "\n--- [$label] INPUT: $input ---\n";
		print_r( $result );
		$this->assertEquals( $expected, $result );
	}

	public function test_all_date_formats() {
		$cases = [
			'singledays'             => [
				'input'    => '17-18-24-25-mayo-2025',
				'expected' => [ '2025-05-17', '2025-05-18', '2025-05-24', '2025-05-25' ],
			],
			'range_simple'           => [
				'input'    => 'del-1-al-30-marzo-2025',
				'expected' => $this->date_range( '2025-03-01', '2025-03-30' ),
			],
			'range_full'             => [
				'input'    => 'del-3-mayo-al-28-junio-2025',
				'expected' => $this->date_range( '2025-05-03', '2025-06-28' ),
			],
			'temporada'              => [
				'input'    => 'temporada-2025',
				'expected' => $this->date_range( '2025-01-01', '2025-12-31' ),
			],
			'finalizo'               => [
				'input'    => 'finalizo-28-abril-2025',
				'expected' => [ '2025-04-28' ],
			],
			'multi_month_singledays' => [
				'input'    => '13-27-abril-4-mayo-2025',
				'expected' => [ '2025-04-13', '2025-04-27', '2025-05-04' ],
			],
		];

		foreach ( $cases as $label => $case ) {
						echo "----- Test $label => $case" . PHP_EOL . PHP_EOL;
			$this->evaluate_case( $label, $case['input'], $case['expected'] );
		}
	}
}
