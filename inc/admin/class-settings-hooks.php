<?php
/**
 * Class responsible for handling settings hooks for the actions in
 * the settings page for the plugin: wp-admin/options-general.php?page=cartelera-scrap
 * This means, when you click on a button in that page, it takes you here and do stuff.
 *
 * @package Cartelera_Scrap\Admin
 */

namespace Cartelera_Scrap\Admin;

use Cartelera_Scrap\Scrap_Actions;
use Cartelera_Scrap\Cron_Job;
use Cartelera_Scrap\Helpers\Results_To_Save;
use Cartelera_Scrap\Helpers\Queue_To_Process;

/**
 * Settings_Hooks class.
 */
class Settings_Hooks {

	const ONETIMEOFF_CRONJOB_NAME = 'cartelera-scrap_process_next_onetimeoff';

	/**
	 * Initializes the class by hooking into WordPress actions.
	 */
	public static function init(): void {
		// Hook the handle_scrap_action method to the 'admin_init' action
		// this function handles all actions in buttons in the settings page..
		add_action( 'admin_init', [ __CLASS__, 'handle_scrap_action' ] );

		add_action( self::ONETIMEOFF_CRONJOB_NAME, function() {
			update_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count', 0 ); // init the count of the shows being processed in this batch.
			Scrap_Actions::cartelera_process_one_batch();
		} );

		// When saving the settings, if they say we need to run the cron => we schecdule the cron job.
		add_action( 'update_option_' . Settings_Page::ALL_MAIN_OPTIONS_NAME, [ __CLASS__, 'start_or_stop_cron_job' ], 10, 2 );
	}

	/**
	 * Handles the custom scrap action triggered via a POST request.
	 */
	public static function handle_scrap_action(): void {

		// Actions sent when clicking on a button in the Settings page.
		$valid_actions = [
			'action_start_scrapping_shows',
			'action_process_next_scheduled_show',
			'action_scrap_single_show',
			'action_stop_one_time_off_cron_job',
			'action_export_scraping_results'
		];

		if ( ! isset( $_POST['action'] ) ) {
			return;
		}
		$action = sanitize_text_field( $_POST['action'] );
		if ( ! in_array( $action, $valid_actions ) ) {
			return;
		}

		$message = 'Updated';

		// Check if the custom action and nonce are set in the POST request.
		if ( isset( $_POST['nonce_action_scrapping'] ) ) {
			// Verify the nonce to ensure the request is valid.
			if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce_action_scrapping'] ), 'nonce_action_field' ) ) {
				wp_safe_redirect( add_query_arg(
					'error', 'Error: Nonce verification failed.',
					admin_url( 'options-general.php?page=cartelera-scrap' )
				) );
				exit;
			}

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- ignoring error_log usage for debugging purposes
			error_log( 'Acción ejecutada' );

			if ( 'action_start_scrapping_shows' === $action ) {

				// Perform the scrap action -> calls the recurring cron job to start processing the shows.
				$result_scrap = Scrap_Actions::perform_scrap();
				if ( is_wp_error( $result_scrap ) ) {
					wp_safe_redirect( add_query_arg(
						'error', 'Error: No shows found in cartelera.',
						admin_url( 'options-general.php?page=cartelera-scrap' )
					) );
					exit;
				}

				// Redirect back to the admin page after the action is executed.
				$message = 'Scrap action executed successfully.';
			} elseif ( 'action_process_next_scheduled_show' === $action ) {

				update_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count', 0 ); // init the count of the shows being processed in this batch.
				Scrap_Actions::cartelera_process_one_batch();
				$shows_per_batch = Settings_Page::get_plugin_setting( Settings_Page::NUMBER_PROCESSED_EACH_TIME ) ?? 10;
				$message         = sprintf( __( 'Processed %s theatre shows.', 'cartelera-scrap' ), $shows_per_batch );

			} elseif ( 'action_scrap_single_show' === $action ) {

				// Executes the scrapping for the given show title and href.
				$show_title     = isset( $_POST['show-title'] ) ? sanitize_text_field( $_POST['show-title'] ) : '';
				$cartelera_href = isset( $_POST['cartelera-href'] ) ? sanitize_text_field( $_POST['cartelera-href'] ) : '';
				if ( empty( $show_title ) || empty( $cartelera_href ) ) {
					$message = 'missed title or href ';
				} else {
					$show_data = [
						'text' => $show_title,
						'href' => $cartelera_href,
					];
					Queue_To_Process::add_first_queued_show( $show_data );
					Scrap_Actions::cartelera_process_one_single_show();
					$message   = sprintf( __( 'Processed theatre show: %1$s (%2$s).', 'cartelera-scrap' ), $show_title, $cartelera_href );
					$scroll_to = '#result-' . sanitize_title( $show_title );
				}
			} elseif ( 'action_stop_one_time_off_cron_job' === $action ) {
				// Stop the cron job.
				wp_clear_scheduled_hook( Settings_Hooks::ONETIMEOFF_CRONJOB_NAME );
				$message = 'Cron job stopped';
			} elseif ( 'action_export_scraping_results' === $action ) {
				// Export the results to a file.
				self::export_action();
			}

			// return to the settings page showing a notice.
			wp_safe_redirect(
				add_query_arg(
					'message', $message,
					admin_url( 'options-general.php?page=cartelera-scrap' . ( isset( $scroll_to ) ? $scroll_to : '' ) )
				)
			);
			exit;
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return string | \WP_Error
	 */
	public static function export_scrap_results_to_uploads_file(): string|\WP_Error {

		$results = Results_To_Save::get_show_results();
		// Convert the results array to JSON format.
		$json_data = json_encode( $results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		// Check if the JSON encoding was successful.
		if ( false === $json_data ) {
			return new \WP_Error( 'json_data_error', 'Error generating json data from results.' );
		}

		$upload_dir = wp_upload_dir();
		// Create a new folder in the uploads directory for 'cartelera-scrap'.
		$cartelera_scrap_dir = trailingslashit( $upload_dir['basedir'] ) . 'cartelera-scrap';
		if ( ! file_exists( $cartelera_scrap_dir ) ) {
			if ( ! mkdir( $cartelera_scrap_dir, 0755, true ) ) {
				return new \WP_Error( 'creating_dir_error', 'Error creatgin direactory ' . $cartelera_scrap_dir );
			}
		}
		$temp_file_path = trailingslashit( $cartelera_scrap_dir ) . 'cartelera-scrap-results.json';

		// Save the JSON data to the file.
		if ( false === file_put_contents( $temp_file_path, $json_data ) ) {
			return new \WP_Error( 'error_saving_to_file', 'Error saving data into file ' . $temp_file_path );
		}

		return $temp_file_path;
	}

	public static function export_action(): void {

		$export_filepath = self::export_scrap_results_to_uploads_file();

		if ( is_wp_error( $export_filepath ) ) {
			wp_safe_redirect( add_query_arg(
				'error', 'Error: ' . $export_filepath->get_error_message(),
				admin_url( 'options-general.php?page=cartelera-scrap' )
			) );
			exit;
		}

		// Force download of the file.
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $export_filepath ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $export_filepath ) );
		readfile( $export_filepath );
		exit;
	}


	/**
	 * Executed when any of the registered fields changes value.
	 *
	 * @param array $old_value
	 * @param array $new_value
	 * @return void
	 */
	public static function start_or_stop_cron_job( array $old_value, array $new_value ) {

		// case 1. to schedule or stop the cron job for tonite
		$frequency = $new_value[ Settings_Page::OPTION_CRON_FREQUENCY ];
		if ( empty( $frequency ) ) {
			Cron_Job::stop_schedule_cron_job();
		} else {
			Cron_Job::start_schedule_cron_job_at_midnight();
		}

		// case 2. start the cron job right now. (clicked button 'Save and execute now')
		$trigger_cron = $new_value[ Settings_Page::OPTION_CRON_SAVE_AND_RUN ] ?? null;
		if ( ! is_null( $trigger_cron )
			&& ( empty( $old_value[ Settings_Page::OPTION_CRON_SAVE_AND_RUN ] ) || $trigger_cron !== $old_value[ Settings_Page::OPTION_CRON_SAVE_AND_RUN ] )
		) {
			do_action( Cron_Job::CRONJOB_NAME );

			wp_safe_redirect(
				add_query_arg(
					'message', 'Cron job executed now',
					admin_url( 'options-general.php?page=cartelera-scrap' )
				)
			);
			exit;
		}
	}
}

Settings_Hooks::init();
