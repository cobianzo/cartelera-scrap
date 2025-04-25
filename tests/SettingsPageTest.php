<?php

class SettingsPageTest extends WP_UnitTestCase {

	/**
	 * Verifies that the plugin loads correctly.
	 */
	public function test_plugin_is_loaded() {
		echo "\n ======= TEST 1.1 START 🎬 🤯========";
		$this->assertTrue( method_exists( 'Cartelera_Scrap\Cartelera_Scrap_Plugin', 'get_ticketmaster_url' ) );
		echo "\n\n✅✅✅ Test passed 1. \n\n";
	}

	/**
	 * Verifies that the plugin function returns the expected result.
	 */
	public function test_valid_settings_values() {
		echo "\n ======= TEST 1.2 START 🎬 🤯========";
		// We call the plugin function.
		$ticketmaster_url = Cartelera_Scrap\Cartelera_Scrap_Plugin::get_ticketmaster_url( 'my-show-title' );
		$cartelera_url    = Cartelera_Scrap\Cartelera_Scrap_Plugin::get_cartelera_url();
		// $batch_number     = Cartelera_Scrap\Cartelera_Scrap_Plugin::get_ba();

		// We check that the URLs are valid.
		$this->assertTrue(
			filter_var( $ticketmaster_url, FILTER_VALIDATE_URL) && 'https' === parse_url( $ticketmaster_url, PHP_URL_SCHEME )
		);
		$this->assertTrue(
			filter_var( $cartelera_url, FILTER_VALIDATE_URL ) && 'https' === parse_url( $cartelera_url, PHP_URL_SCHEME )
		);
		echo "\n\n✅✅✅ Test passed 2. \n\n";
	}
}
