<?php

namespace Cartelera_Scrap\Admin;

use Cartelera_Scrap\Cron_Job;
use Cartelera_Scrap\Scrap_Output;
use Cartelera_Scrap\Parse_Text_Into_Dates;
use Cartelera_Scrap\Helpers\Results_To_Save;
use Cartelera_Scrap\Helpers\Queue_To_Process;

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
	const ALL_MAIN_OPTIONS_NAME = 'cartelera-scrap_main_options';

	// Key for scrapping cartelera (checked).
	const OPTION_CARTELERA_URL = 'cartelera_obras_page';

	// Key for scrapping tickemaster (source).
	const OPTION_TICKETMASTER_URL = 'ticketmaster_search_page';

	// how many shows to process each time, before calling the next cron job.
	const NUMBER_PROCESSED_EACH_TIME = 'number_processed_each_time';

	// stop comparing dates after these amount of days.
	const LIMIT_DAYS_FORWARD_COMPARE = 'limit_days_forward_compare';

	// stop comparing dates after showing this amount of event dates.
	const LIMIT_NUMBER_DATES_COMPARE = 'limit_number_dates_compare';

	// Cron job fields
	const OPTION_CRON_FREQUENCY = 'cron_frequency';

	// Submit button that when we click, we don't only save but run the cron job
	const OPTION_CRON_SAVE_AND_RUN = 'cron_save_and_run';

	/**
	 * Constructor for the Settings_Page class.
	 *
	 */
	public function __construct() {
		$this->plugin_name = CARTELERA_SCRAP_PLUGIN_SLUG;
		$this->textdomain  = CARTELERA_SCRAP_PLUGIN_SLUG;
		$this->version     = \Cartelera_Scrap\Cartelera_Scrap_Plugin::VERSION;
		$this->pageid      = CARTELERA_SCRAP_PLUGIN_SLUG . '_page';


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
	 * @return string|null
	 */
	public static function get_plugin_setting( string $option_name ): ?string {
		$options = get_option( self::ALL_MAIN_OPTIONS_NAME );
		return $options[ $option_name ] ?? null;
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
				settings_fields( self::ALL_MAIN_OPTIONS_NAME );
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
			// Button to export
			$count_results = count( Results_To_Save::get_show_results() );
			if ( $count_results ) :
				$text = sprintf( __( 'Download json file for %s results', 'cartelera-scrap' ), $count_results );
				self::create_form_button_with_action( 'action_export_scraping_results', $text, [ 'button-class' => 'button button-secondary' ] );
			endif;


			if ( wp_next_scheduled( Settings_Hooks::ONETIMEOFF_CRONJOB_NAME ) ) :

				_e( '<h3>Scrapping is running as a cron job</h3>', 'cartelera-scrap' );
				$start_date = Queue_To_Process::get_timestamp_start_process( 'l, F j, Y \a\\t g:i a' );
				printf( __( '<p>Current queue started at: <b>%s</b><br />', 'cartelera-scrap' ), $start_date ? "$start_date GMT" : 'not set' );

				printf( __( '<p>Shows in the processing queue waiting to be processed: %s<br />', 'cartelera-scrap' ), Queue_To_Process::get_queued_count() );
				printf( __( 'Already processed shows: %s</p>', 'cartelera-scrap' ), count( Results_To_Save::get_show_results() ) );
				$queue = Queue_To_Process::get_first_queued_show();
				if ( $queue ) {
					echo '<p>Next show to Scrap:  ' . $queue['text'] . '</p>';
				} else {
					echo '<p>Nothing in the queue to scrap</p>';
				}

				// Button to stop current ONE TIME OFF cron job
				self::create_form_button_with_action(
					'action_stop_one_time_off_cron_job',
					__( 'Stop current processing queue cron job', 'cartelera-scrap' ),
					[ 'button-class' => 'button button-secondary' ]
				);

			else :
				echo '<p>Scrapping ' . Settings_Hooks::ONETIMEOFF_CRONJOB_NAME . ' is not running as a cron job</p>';
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
			self::ALL_MAIN_OPTIONS_NAME,
			self::ALL_MAIN_OPTIONS_NAME,
			[
				'sanitize_callback' => function ( array $options ) {
					// Sanitize the options before saving them. $options is an associative array ( optionname=>value).
					$options[ self::OPTION_CARTELERA_URL ]       = esc_url_raw( $options[ self::OPTION_CARTELERA_URL ] );
					$options[ self::OPTION_TICKETMASTER_URL ]    = esc_url_raw( $options[ self::OPTION_TICKETMASTER_URL ] );
					$options[ self::NUMBER_PROCESSED_EACH_TIME ] = intval( $options[ self::NUMBER_PROCESSED_EACH_TIME ] ) ? intval( $options[ self::NUMBER_PROCESSED_EACH_TIME ] ) : 1;
					$options[ self::LIMIT_DAYS_FORWARD_COMPARE ] = intval( $options[ self::LIMIT_DAYS_FORWARD_COMPARE ] ) ? intval( $options[ self::LIMIT_DAYS_FORWARD_COMPARE ] ) : 1;
					$options[ self::LIMIT_NUMBER_DATES_COMPARE ] = intval( $options[ self::LIMIT_NUMBER_DATES_COMPARE ] ) ? intval( $options[ self::LIMIT_NUMBER_DATES_COMPARE ] ) : 1;

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

		$this->register_input_field( self::OPTION_CARTELERA_URL, $this->plugin_name . '_fields__section_leftcolumn' );
		$this->register_input_field( self::OPTION_TICKETMASTER_URL, $this->plugin_name . '_fields__section_leftcolumn' );

		$this->register_input_field( self::NUMBER_PROCESSED_EACH_TIME, $this->plugin_name . '_fields__section_leftcolumn', [
			'type'  => 'number',
			'label' => __( 'Number of shows to process each time', $this->textdomain ),
		] );
		$this->register_input_field( self::LIMIT_DAYS_FORWARD_COMPARE, $this->plugin_name . '_fields__section_leftcolumn', [
			'type'        => 'number',
			'label'       => __( 'After these amounts of days from today, stop comparing cartelera and ticketmaster dates.', $this->textdomain ),
			'description' => sprintf( __( 'Currently set to %s.', $this->textdomain ), date( 'Y-m-d H:i', Parse_Text_Into_Dates::get_limit_datetime() ) ),
		] );
		$this->register_input_field( self::LIMIT_NUMBER_DATES_COMPARE, $this->plugin_name . '_fields__section_leftcolumn', [
			'type'  => 'number',
			'label' => __( 'After showing this amount of dates, stop comparing cartelera and ticketmaster dates.', $this->textdomain ),
		] );

		// Register fields Right column
		// ===============================
		// ...
		$this->register_dropdown_field(
			self::OPTION_CRON_FREQUENCY,
			$this->plugin_name . '_fields__section_rightcolumn',
			[
				''           => 'Deactivate current cron',
				'daily'      => 'Daily',
				'weekly'     => 'Weekly',
				'twicedaily' => 'Two times per day',
			],
			[
				'append' => '

				',
			]
		);

		add_settings_field(
			self::OPTION_CRON_SAVE_AND_RUN,
			'',
			function () {
				$new_value = self::get_plugin_setting( self::OPTION_CRON_SAVE_AND_RUN ) ? '0' : '1';
				echo '<button type="submit" name="'
					. esc_attr( self::ALL_MAIN_OPTIONS_NAME ) . '[' . esc_attr( self::OPTION_CRON_SAVE_AND_RUN ) . ']"
				value="' . esc_attr( $new_value ) . '" class="button button-secondary">Save and exectute now</button>
				';
			},
			$this->pageid,
			$this->plugin_name . '_fields__section_rightcolumn'
		);
	}

	/**
	 * helper
	 *
	 * @param string $field_name
	 * @param string $section_id
	 * @param array  $more_parems
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
				$options      = get_option( self::ALL_MAIN_OPTIONS_NAME ); // Retrieve the saved options.
				$option_value = $options[ $field_name ] ?? '';
				?>
			<input
				<?php
				if ( 'number' === $more_parems['type'] ) {
					echo 'type="number" step="1" min="1" placeholder="type a number"';
				} else {
					echo 'type="text" class="regular-text"';
				}
				?>
				name="<?php echo esc_attr( self::ALL_MAIN_OPTIONS_NAME ); ?>[<?php echo esc_attr( $field_name ); ?>]"
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
				$options      = get_option( self::ALL_MAIN_OPTIONS_NAME ); // Retrieve the saved options.
				$option_value = $options[ $field_name ] ?? '';
				?>
			<select name="<?php echo esc_attr( self::ALL_MAIN_OPTIONS_NAME ); ?>[<?php echo esc_attr( $field_name ); ?>]">
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
new Settings_Page();
