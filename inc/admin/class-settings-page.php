<?php

namespace Cartelera_Scrap\Admin;

use Cartelera_Scrap\Scrap_Output;
use Cartelera_Scrap\Scrap_Actions;

/**
 * Class Settings_Page
 *
 * This class is responsible for managing the settings page in the admin panel.
 * - One settings page `wp-admin/options-general.php?page=cartelera-scrap`.
 * - One section.
 * - Several fields (all fields saved as an associative array in the options table).
 * Saves all the settings under one single option in the database.
 *
 * @package Cartelera_Scrap\Admin
 */
class Settings_Page {

	// Plugin name identifier.
	private string $plugin_name;

	// Plugin version identifier.
	private string $version;

	// Name of the options saved in the database.
	public string $all_plugin_options_name;

	// Key for scrapping cartelera (checked).
	public static string $option_cartelera_url = 'cartelera_obras_page';

	// Key for scrapping tickemaster (source).
	public static string $option_ticketmaster_url = 'ticketmaster_search_page';

	// how many shows to process each time, before calling the next cron job.
	public static string $number_processed_each_time = 'number_processed_each_time';

	// stop comparing dates after these amount of days.
	public static string $limit_days_forward_compare = 'limit_days_forward_compare';

	/**
	 * Constructor for the Settings_Page class.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of the plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name             = $plugin_name;
		$this->version                 = $version;
		$this->all_plugin_options_name = $plugin_name . '_options'; // Save the options serialized.

		// Hook to add the settings page to the admin menu.
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

		// Hook to initialize the settings.
		add_action( 'admin_init', [ $this, 'settings_init' ] );

		// Hook to enqueue scripts and styles for the settings page.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts_styles' ] );
	}

	/**
	 * Enqueue styles and scripts for the settings page.
	 * NOT IN USE YET: todelete.
	 */
	public function enqueue_scripts_styles(): void {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/cartelera-settings-page.css',
			[],
			$this->version,
			'all'
		); // Enqueue the CSS file.

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/cartelera-settings-page.js',
			[ 'jquery' ],
			$this->version,
			false
		); // Enqueue the JavaScript file.
	}

	/**
	 * Add the settings page to the WordPress admin menu.
	 */
	public function add_admin_menu(): void {
		add_options_page(
			'Cartelera Scrap', // Page title.
			'Cartelera Scrap', // Menu title.
			'manage_options', // Capability required to access the page.
			$this->plugin_name, // Menu slug.
			[ $this, 'options_page' ] // Callback function to render the page.
		);
	}

	/**
	 * Render the settings page.
	 */
	public function options_page(): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1> <!-- Display the page title. -->
			<form action="options.php" method="post"> <!-- Form to save settings. -->
				<?php
				settings_fields( $this->all_plugin_options_name ); // Output nonce, action, and option group.
				do_settings_sections( $this->plugin_name ); // Output settings sections and fields.
				submit_button(); // Output the submit button.
				?>
			</form>

			<!-- Button to export -->
			<?php
			$count_results = count( Scrap_Actions::get_show_results() );
			if ( $count_results ) :
				$text = sprintf( __( 'Download json file for %s results', 'cartelera-scrap' ), $count_results );
				self::create_form_button_with_action( 'action_export_scraping_results', $text, [ 'button-class' => 'button button-secondary' ] );
			endif;

			// the table with all the results printed.
			Scrap_Output::render_scrap_status(); // Render the scrap status output.
			?>
		</div>
		<?php
	}

	/**
	 * Initialize the settings for the plugin.
	 */
	public function settings_init(): void {
		// Register the settings option in the database.
		register_setting(
			$this->all_plugin_options_name,
			$this->all_plugin_options_name,
			[
				'sanitize_callback' => function ( array $options ) {
					// Sanitize the options before saving them. $options is an associative array ( optionname=>value).
					$options[ self::$option_cartelera_url ]       = esc_url_raw( $options[ self::$option_cartelera_url ] );
					$options[ self::$option_ticketmaster_url ]    = esc_url_raw( $options[ self::$option_ticketmaster_url ] );
					$options[ self::$number_processed_each_time ] = intval( $options[ self::$number_processed_each_time ] ) ? intval( $options[ self::$number_processed_each_time ] ) : 1;
					$options[ self::$limit_days_forward_compare ] = intval( $options[ self::$limit_days_forward_compare ] ) ? intval( $options[ self::$limit_days_forward_compare ] ) : 1;

					return $options;
				},
			]
		);

		// Add a settings section to the settings page.
		add_settings_section(
			$this->plugin_name . '_fields__section', // Section ID.
			__( 'Settings', $this->plugin_name ), // Section title.
			function (): void {
				echo '<p>' . __( 'Configure the settings for Cartelera Scrap.', $this->plugin_name ) . '</p>'; // Section description.
			},
			$this->plugin_name // Page slug.
		);

		foreach ( [ self::$option_cartelera_url, self::$option_ticketmaster_url ] as $option_name ) {

			// Add a settings field for the token key.
			add_settings_field(
				$option_name, // Field ID.
				ucwords( str_replace( '_', ' ', $option_name ) ), // Field title.
				function () use ( $option_name ): void {
					$options      = get_option( $this->all_plugin_options_name ); // Retrieve the saved options.
					$option_value = $options[ $option_name ] ?? '';
					?>
				<input type="text" class="regular-text"
					name="<?php echo esc_attr( $this->all_plugin_options_name ); ?>[<?php echo esc_attr( $option_name ); ?>]"
					value="<?php echo esc_attr( $option_value ); ?>">
					<?php
				},
				$this->plugin_name, // Page slug.
				$this->plugin_name . '_fields__section' // Section ID.
			);
		} // end for both urls fields

		$numberic_fields = [
			self::$number_processed_each_time => __( 'Number of shows to process each time', $this->plugin_name ),
			self::$limit_days_forward_compare => __( 'After these amounts of days from today, stop, comparing cartelera and ticketmaster dates.', $this->plugin_name ),
		];
		foreach ( $numberic_fields as $field_name => $field_label ) {
			add_settings_field(
				$field_name, // Field ID.
				$field_label, // Field title.
				function () use ( $field_name ): void {
					$options      = get_option( $this->all_plugin_options_name ); // Retrieve the saved options.
					$option_value = $options[ $field_name ] ?? '';
					?>
				<input type="number" step="1" min="1" placeholder="type a number"
					name="<?php echo esc_attr( $this->all_plugin_options_name ); ?>[<?php echo esc_attr( $field_name ); ?>]"
					value="<?php echo esc_attr( $option_value ); ?>">
					<?php
				},
				$this->plugin_name, // Page slug.
				$this->plugin_name . '_fields__section' // Section ID.
			);
		}
	}

	/**
	 * Creates a form with an action which is evaluated in settings - hooks.php
	 *
	 * @param string $action_name
	 * @param string $button_text
	 * @param array  $options [button-class, extra-data]
	 * @return void
	 */
	public static function create_form_button_with_action( string $action_name, string $button_text, array $options = [] ) {
		$options = array_merge( [ 'button-class' => 'button button-primary' ], $options );
		?>
		<form action="options.php" method="post" style="display: flex; align-items: center; gap: 10px;">
			<?php wp_nonce_field( 'nonce_action_field', 'nonce_action_scrapping' ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( $action_name ); ?>">
			<div style="display:flex; align-items: center; gap: 10px;">
				<input type="submit" class="<?php echo esc_attr( $options['button-class'] ); ?>"
					value="<?php echo esc_attr( $button_text ); ?>" />
			</div>
			<?php
			if ( ! empty( $options['extra-data'] ) && is_array( $options['extra-data'] ) ) :
				?>
				<?php foreach ( $options['extra-data'] as $name => $value ) : ?>
					<input type="hidden"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>" />
				<?php endforeach; ?>
			<?php endif; ?>
		</form>
		<?php
	}
}

// Instantiate the Settings_Page class with the plugin name and version.
new Settings_Page( CARTELERA_SCRAP_PLUGIN_SLUG, CARTELERA_SCRAP_VERSION );
