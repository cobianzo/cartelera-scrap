<?php
/**
 * Tests class-text-parser.php
 * we need.
 */
class TextAnalyzeTest extends WP_UnitTestCase {

	public static function deb( mixed $var ): void {
		echo "🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍\n";
		print_r( $var );
		echo "🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍🍆😍\n";
	}

	public function use_first_acceptance_of_date_text( array $array_of_valid_sencences, array $array_not_valid_sencences ) {
		foreach ( $array_of_valid_sencences as $text_example ) {
			$sencences = Cartelera_Scrap\Text_Parser::first_acceptance_of_date_text( $text_example );
			$this->assertIsArray( $sencences, "Failed asserting that the result is an array for text '$text_example'." );
			$this->assertNotEmpty( $sencences, "Failed asserting that the sentence is accepted as valid '$text_example'." );
			// self::deb( $sencences );
			// $this->assertTrue( $accepted, "Failed asserting that the text '$text_example' is accepted." );
		}

		foreach ( $array_not_valid_sencences as $text_example ) {
			$sencences = Cartelera_Scrap\Text_Parser::first_acceptance_of_date_text( $text_example );
			$this->assertIsArray( $sencences, "Failed asserting that the result is an array for text '$text_example'." );
			$this->assertEmpty( $sencences, "Failed asserting that the the sencene is refused: '$text_example'." );
			// self::deb( $sencences );
			// $this->assertTrue( $accepted, "Failed asserting that the text '$text_example' is accepted." );
		}
	}

	/**
	 * Test 3.1. Specific dates
	 */
	public function test_indentify_valid_text_patterns_pattern_1() {
		echo "\n ======= TEST 3.1 START 🎬 🤯========";

		// type 1 of sencences
		$text_examples           = [
			'2 de mayo de 2025',
			'2 y 9 de mayo de 2025',
			'2 y 9 de mayo, 5 y 6 de abril de 2025',
			'27 de abril, 4 y 11 de mayo',
			'21, 22 y 23 de abril de 2025',
			'21, 22 y 23 de abril de 2025 - Aida Pierce, Marina Vera, Alejandra Haydee y Oddy Espinosa',
			'de 2 a 9 de mayo de 2025 (Suspendido el 8 de mayo)',
			'23 y 30 de marzo y 6 abril de 2025.',
			' 23, 25 y 30 de marzo y 6 abril de 2025.',
			' 23, 25 y 30 de marzo y 6 de abril de 2025.',
			' 23, 25 y 30 de marzo y 6, 8 y 9 de abril de 2025.',
			' 23, 25 y 30 de marzo y 6 y 9 de abril de 2025.',
		];
		$text_not_valid_examples = [
			'Aida Pierce, Marina Vera, Alejandra Haydee y Oddy Espinosa',
			'Sábados 18:00 horas',
			'Mayores de 15 años.',
		];

		$this->use_first_acceptance_of_date_text( $text_examples, $text_not_valid_examples );
	}

	public function test_indentify_valid_text_patterns_pattern_2() {
		echo "\n ======= TEST 3.2 START 🎬 🤯========";

		// type 1 of sencences
		$text_examples           = [
			'Del 24 de abril al 8 de junio de 2025',
			'Del 24 de abril al 8 de junio de 2025 (Suspende 1, 10 y 15 de mayo)',
			'Del 24 de abril al 8 de junio de 2025, del 10 de junio asl 12 de junio de 2025',
			'Del 24 de abril al 8 de junio, del 10 de junio asl 12 de junio de 2025',
			'Del 16 abril al 18 mayo de 2025',
			'Del 1 al 29 de Abril de 2025',
		];
		$text_not_valid_examples = [
			'Del 342 de nada a la mierda de 2020',
		];

		$this->use_first_acceptance_of_date_text( $text_examples, $text_not_valid_examples );
	}
}
