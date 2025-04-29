<?php

namespace Cartelera_Scrap\Admin;

use Cartelera_Scrap\Cron_Job;
use Cartelera_Scrap\Scrap_Output;
use Cartelera_Scrap\Scrap_Actions;
use Cartelera_Scrap\Text_Parser;

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
	private string $textdomain;

	// Plugin version identifier.
	private string $version;

	private string $pageid;

	// Name of the options saved in the database.
	public static string $all_main_options_name = 'cartelera-scrap_main_options';

	// Key for scrapping cartelera (checked).
	public static string $option_cartelera_url = 'cartelera_obras_page';

	// Key for scrapping tickemaster (source).
	public static string $option_ticketmaster_url = 'ticketmaster_search_page';

	// how many shows to process each time, before calling the next cron job.
	public static string $number_processed_each_time = 'number_processed_each_time';

	// stop comparing dates after these amount of days.
	public static string $limit_days_forward_compare = 'limit_days_forward_compare';

	// Cron job fields
	public static string $option_cron_frequency = 'cron_frequency';

	// Submit button that when we click, we don't only save but run the cron job
	public static string $option_cron_save_and_run = 'cron_save_and_run';

	/**
	 * Constructor for the Settings_Page class.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of the plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name           = $plugin_name;
		$this->textdomain            = $plugin_name;
		$this->version               = $version;
		$this->pageid                = $plugin_name . '_page';


		// Hook to add the settings page to the admin menu.
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

		// Hook to initialize the settings.
		add_action( 'admin_init', [ $this, 'settings_init' ] );

		// Hook to enqueue scripts and styles for the settings page.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts_styles' ] );
	}

	/**
	 * Helper
	 *
	 * @param string $option_name
	 * @return string
	 */
	public static function get_plugin_setting( string $option_name ): string {
		$options = get_option( Settings_Page::$all_main_options_name );
		return $options[ $option_name ] ?? '';
	}

	/**
	 * Enqueue styles and scripts for the settings page.
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
			[ $this, 'render_options_page' ] // Callback function to render the page.
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_options_page(): void {
		?>
		<a id="top"></a>
		<a href="#top" class="scroll-to-top">⬆</a>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1> <!-- Display the page title. -->


				<form action="options.php" method="post"> <!-- Form to save settings. -->
					<?php
					// Output nonce, action, and option group.
					settings_fields( self::$all_main_options_name );
					?>

					<div class="cartelera-scrap-settings-columns-wrapper">
						<div class="cartelera-scrap-settings-column cartelera-scrap-settings-column__left">
						<?php
						// section left columns and right column
						do_settings_sections( $this->pageid );
						?>
						</div>
					</div>

					<?php
					// Save button
					submit_button( 'Save cartelera options' ); // Output the submit button.
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
		// Settings for section
		register_setting(
			self::$all_main_options_name,
			self::$all_main_options_name,
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




		// Section:
		// Left column section: urls, number process per batch.
		add_settings_section(
			$this->plugin_name . '_fields__section_leftcolumn', // Section ID.
			__( 'Settings', $this->textdomain ), // Section title.
			function (): void {
				echo '<p>' . __( 'Configure the settings for Cartelera Scrap.', $this->textdomain ) . '</p>'; // Section description.
			},
			$this->pageid // Page slug.
		);
		// Right column section: cron job related fields
		add_settings_section(
			$this->plugin_name . '_fields__section_rightcolumn', // Section ID.
			 __( 'Cron Job settings', $this->textdomain ), // Section title.
			function (): void {
				echo Cron_Job::get_next_cronjob_execution_time();
			},
			$this->pageid // Page slug.
		);

		// Register fields Left column
		// ===============================

		// Add a settings fields for the urls
		// cartelera-scrap_main_options[cartelera_obras_page]
		// cartelera-scrap_main_options[ticketmaster_search_page]
		// ...

		$this->register_input_field( self::$option_cartelera_url, $this->plugin_name . '_fields__section_leftcolumn' );
		$this->register_input_field( self::$option_ticketmaster_url, $this->plugin_name . '_fields__section_leftcolumn' );

		$this->register_input_field( self::$number_processed_each_time, $this->plugin_name . '_fields__section_leftcolumn', [
			'type'  => 'number',
			'label' => __( 'Number of shows to process each time', $this->textdomain ),
		] );
		$this->register_input_field( self::$limit_days_forward_compare, $this->plugin_name . '_fields__section_leftcolumn', [
			'type'  => 'number',
			'label'       => __( 'After these amounts of days from today, stop, comparing cartelera and ticketmaster dates.', $this->textdomain ),
			'description' => sprintf( __( 'Currently set to %s.', $this->textdomain ), date( 'Y-m-d H:i', Text_Parser::get_limit_datetime() ) ),
		] );

		// Register fields Right column
		// ===============================
		// ...
		$this->register_dropdown_field(
			self::$option_cron_frequency,
			$this->plugin_name . '_fields__section_rightcolumn',
			[ '' => 'Deactivate current cron', 'daily' => 'Daily', 'weekly' => 'Weekly', 'twicedaily' => 'Two times per day' ],
			[
				'append' =>  '

				'
			] );

		add_settings_field(
			self::$option_cron_save_and_run,
			'',
			function() {
				$new_value = (int) self::get_plugin_setting( self::$option_cron_save_and_run ) ? 0 : 1;
				echo '<button type="submit" name="'
				. esc_attr( self::$all_main_options_name ) . '[' . esc_attr( self::$option_cron_save_and_run ) . ']"
				value="' . esc_attr( $new_value ) . '" class="button button-secondary">Save and exectute now</button>
				'; },
			$this->pageid,
			$this->plugin_name . '_fields__section_rightcolumn'
		);

	}

	/**
	 * helper
	 *
	 * @param string $field_name
	 * @param string $section_id
	 * @param array $more_parems
	 * @return void
	 */
	public function register_input_field( string $field_name, string $section_id, $more_parems = [] ) {
		$more_parems = array_merge( [
			'label'       => ucwords( str_replace( '_', ' ', $field_name ) ), // Field title.
			'description' => __( 'Enter a numeric value for this setting.', $this->textdomain ),
			'type'        => 'text',
		], $more_parems );

		add_settings_field(
			$field_name, // Field ID.
			$more_parems['label'],
			function () use ( $field_name, $more_parems ): void {
				$options      = get_option( self::$all_main_options_name ); // Retrieve the saved options.
				$option_value = $options[ $field_name ] ?? '';
				?>
			<input <?php if ( "number" === $more_parems['type'] ) {
					echo 'type="number" step="1" min="1" placeholder="type a number"';
				} else {
					echo 'type="text" class="regular-text"';
				}
				?> name="<?php echo esc_attr( self::$all_main_options_name ); ?>[<?php echo esc_attr( $field_name ); ?>]"
				value="<?php echo esc_attr( $option_value ); ?>">
				<p class="description">
					<?php echo esc_html( $more_parems['description'] ); ?>
				</p>
				<?php
			},
			$this->pageid, // Page slug.
			$section_id,  // Section ID.
		);

	}

	/**
	 * Render a dropdown field for the settings page.
	 *
	 * @param string $field_name
	 * @param string $section_id
	 * @param array  $options_array Associative array of options (value => label).
	 * @param array  $more_params Additional parameters for customization.
	 * @return void
	 */
	public function register_dropdown_field( string $field_name, string $section_id, array $options_array, $more_params = [] ) {
		$more_params = array_merge( [
			'label'       => ucwords( str_replace( '_', ' ', $field_name ) ), // Field title.
			'description' => __( 'Select an option for this setting.', $this->textdomain ),
			'append'      => '',
		], $more_params );

		add_settings_field(
			$field_name, // Field ID.
			$more_params['label'],
			function () use ( $field_name, $options_array, $more_params ): void {
				$options      = get_option( self::$all_main_options_name ); // Retrieve the saved options.
				$option_value = $options[ $field_name ] ?? '';
				?>
				<select name="<?php echo esc_attr( self::$all_main_options_name ); ?>[<?php echo esc_attr( $field_name ); ?>]">
					<?php foreach ( $options_array as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $option_value, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php echo esc_html( $more_params['description'] ); ?>
				</p>
				<?php
				// Display additional information below the dropdown if provided.
				if ( ! empty( $more_params['append'] ) ) {
					echo '<div class="dropdown-append">' . $more_params['append'] . '</div>';
				}
			},
			$this->pageid, // Page slug.
			$section_id,  // Section ID.
		);
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
