<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Cartelera Scrap
 * Plugin URI:
 * Description: Plugin for scrapping ticketmaster vs cartelera.com.mx
 * Version: 1.0.0
 * Author: @cobianzo
 * Author URI: https://githuck.com/cobianzo
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cartelera-scrap
 * Domain Path: /languages
 *
 * @package CarteleraScrap
 */

namespace Cartelera_Scrap;

use Cartelera_Scrap\Admin\Settings_Page;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin Class starting point.
 */
class Cartelera_Scrap_Plugin {

	const VERSION = '1.0.0';

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
		define( 'CARTELERA_SCRAP_PLUGIN_SLUG', 'cartelera-scrap' );
		define( 'CARTELERA_SCRAP_VERSION', self::VERSION );
		define( 'CARTELERA_SCRAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'CARTELERA_SCRAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		if ( file_exists( CARTELERA_SCRAP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
			require_once CARTELERA_SCRAP_PLUGIN_DIR . 'vendor/autoload.php';
		}

		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/admin/class-simple-scraper.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/admin/class-settings-page.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/admin/class-scrap-output.php';
		require_once CARTELERA_SCRAP_PLUGIN_DIR . 'inc/admin/class-scrap-actions.php';
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
		// Plugin initialization logic here.
	}

	/**
	 * Basic function to get the url 1 to scrap (cartelera).
	 * It's editable in the settings page.
	 *
	 * @return string url
	 */
	public static function get_cartelera_url(): string {
		// first compare the option in the database.
		$plugin_options = get_option( ( new Settings_Page( 'cartelera-scrap', self::VERSION ) )->all_plugin_options_name );
		return $plugin_options[ Settings_Page::$option_cartelera_url ] ?? 'https://carteleradeteatro.mx/todas/';
	}

	/**
	 * Basic function to get the url 2 to scrap (ticketmaster).
	 *
	 * @param string $show_title The title of the show to search for.
	 * @return string url
	 */
	public static function get_ticketmaster_url( string $show_title ): string {
		// first compare the option in the database.
		$plugin_options = get_option( ( new Settings_Page( 'cartelera-scrap', self::VERSION ) )->all_plugin_options_name );
		$url            = $plugin_options[ Settings_Page::$option_ticketmaster_url ] ?? 'https://ticketmaster.com.mx/search';
		if ( $show_title ) {
			$url .= '?q=' . urlencode( $show_title );
		}
		return $url;
	}

	/**
	 * Basic function to get the value of a specific plugin setting.
	 *
	 * @param string $option_name the name of the option to retrieve.
	 * @return string
	 */
	public static function get_plugin_setting( string $option_name ): string {
		$options = get_option( ( new Settings_Page( 'cartelera-scrap', CARTELERA_SCRAP_VERSION ) )->all_plugin_options_name );
		return $options[ $option_name ] ?? '';
	}
}


/**
 * Debugging functions
 *
 * @param [type] $var
 * @return void
 */
function dd( $var ) {
	echo '<pre>';
	print_r( $var );
	echo '</pre>';
}
/**
 * Debugging function
 *
 * @param [type] $var
 * @return void
 */
function ddie( $var ) {
	dd( $var );
	wp_die();
}

// Initialize the plugin.
new Cartelera_Scrap_Plugin();
