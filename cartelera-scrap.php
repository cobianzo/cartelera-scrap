<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Cartelera Scrap
 * Plugin URI:
 * Description: Plugin for scrapping ticketmaster vs cartelera.com.mx
 * Version: 2.0.3
 * Author: @cobianzo
 * Author URI: https://githuck.com/cobianzo
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cartelera-scrap
 * Domain Path: /languages
 *
 * @package Cartelera_Scrap
 */

namespace Cartelera_Scrap;

use Cartelera_Scrap\Admin\Settings_Page;
use Cartelera_Scrap\Front\Report_CPT;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin Class starting point.
 */
class Cartelera_Scrap_Plugin {

	const VERSION = '2.0.0';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load constants and dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies() {

		// definition of constants.

		define( 'CARTELERA_SCRAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'CARTELERA_SCRAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		require CARTELERA_SCRAP_PLUGIN_DIR . 'phpstan-bootstrap.php';

		if ( file_exists( CARTELERA_SCRAP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
			require_once CARTELERA_SCRAP_PLUGIN_DIR . 'vendor/autoload.php';
		}

		// admin area.
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/admin/class-settings-page.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/admin/class-settings-hooks.php';

		// helpers.
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/helpers/class-results-to-save.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/helpers/class-queue-to-process.php';

		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/helpers/class-months-and-days.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/helpers/class-text-sanization.php';

		// static functions.
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/class-scraper.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/class-scraper-cartelera.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/class-scraper-ticketmaster.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/class-scrap-actions.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/class-parse-text-into-dates.php';

		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/class-scrap-output.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/class-cron-job.php';

		// The frontend
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/front/class-report-cpt.php';
		// require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/front/class-block-registration.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
	}

	/**
	 * Plugin initialization code.
	 *
	 * @return void
	 */
	public function init_plugin() {
		// Whenever there is something to ini by the plugin, include it here.
	}

	/**
	 * Basic function to get the url 1 to scrap (cartelera).
	 * It's editable in the settings page.
	 *
	 * @return string url
	 */
	public static function get_cartelera_url(): string {
		// first compare the option in the database.
		$plugin_options = get_option( Settings_Page::ALL_MAIN_OPTIONS_NAME );
		return $plugin_options[ Settings_Page::OPTION_CARTELERA_URL ] ?? 'https://carteleradeteatro.mx/todas/';
	}

	/**
	 * Basic function to get the url 2 to scrap (ticketmaster).
	 *
	 * @param string $show_title The title of the show to search for.
	 * @return string url
	 */
	public static function get_ticketmaster_url( string $show_title = '' ): string {
		// first compare the option in the database.
		$plugin_options = get_option( Settings_Page::ALL_MAIN_OPTIONS_NAME );
		$url            = $plugin_options[ Settings_Page::OPTION_TICKETMASTER_URL ] ?? 'https://ticketmaster.com.mx/search';
		if ( ! empty( $show_title ) ) {
			$url .= '?q=' . rawurlencode( $show_title );
		}
		return $url;
	}
}

// phpcs:disable
/**
 * Debugging functions @TODELETE:
 *
 * @param mixed $var Any var.
 * @return void
 */
function dd( mixed $var ) {
	echo '<pre>';
	print_r( $var );
	echo '</pre>';
}
/**
 * Debugging function
 *
 * @param mixed $var
 * @return void
 */
function ddie( mixed $var = null ): void {
	if ( $var ) {
		dd( $var );
	}
	wp_die();
}

function imhere( mixed $var = null ): void {
	echo "<h1>😀🥹😎🥶imhere😀🥹😎🥶</h1>";
	if ( $var ) {
		dd($var);
	}
}
// phpcs:enable

// Initialize the plugin.
new Cartelera_Scrap_Plugin();


		// $args = array(
		// 'post_type' => Report_CPT::POST_TYPE,
		// 'posts_per_page' => 1,
		// );
		// $posts = get_posts( $args );
		// $post = $posts[0];

// ddie($html);
// add_action( 'init', function() {
// 	Report_CPT::save_results_as_post();
// });
