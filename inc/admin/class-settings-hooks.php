<?php
/**
 * Class responsible for handling settings hooks for the actions in
 * the settings page for the plugin: wp-admin/options-general.php?page=cartelera-scrap
 * This means, when you click on a button in that page, it takes you here and do stuff.
 *
 * @package CarteleraScrap\Admin
 */

namespace Cartelera_Scrap\Admin;

use Cartelera_Scrap\Scrap_Actions;
use Cartelera_Scrap\Cartelera_Scrap_Plugin;

class Settings_Hooks {

	const CRONJOB_NAME = 'cartelera_process_next_show';

	/**
	 * Initializes the class by hooking into WordPress actions.
	 */
	public static function init(): void {
		// Hook the handle_scrap_action method to the 'admin_init' action.
		add_action( 'admin_init', [ __CLASS__, 'handle_scrap_action' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_export_action' ] );
		add_action( self::CRONJOB_NAME, [ __CLASS__, 'cartelera_process_one_batch' ] );
	}

	/**
	 * Handles the custom scrap action triggered via a POST request.
	 */
	public static function handle_scrap_action(): void {

		$valid_actions = [ 'action_start_scrapping_shows', 'action_process_next_scheduled_show', 'action_scrap_single_show' ];
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

				// Perform the scrap action -> calls the cron job to start processing the shows.
				Scrap_Actions::perform_scrap();

				// Redirect back to the admin page after the action is executed.
				$message = 'Scrap action executed successfully.';
			} elseif ( 'action_process_next_scheduled_show' === $action ) {

				update_option( CARTELERA_SCRAP_PLUGIN_SLUG . '_batch_shows_count', 0 ); // init the count of the shows being processed in this batch.
				Scrap_Actions::cartelera_process_one_batch();
				$shows_per_batch = Cartelera_Scrap_Plugin::get_plugin_setting( Settings_Page::$number_processed_each_time ) ?? 10;
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
					Scrap_Actions::add_first_queued_show( $show_data );
					Scrap_Actions::cartelera_process_one_single_show();
					$message   = sprintf( __( 'Processed theatre show: %1$s (%2$s).', 'cartelera-scrap' ), $show_title, $cartelera_href );
					$scroll_to = '#result-' . sanitize_title( $show_title );
				}
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

		$results = Scrap_Actions::get_show_results();
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

	public static function handle_export_action(): void {
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}
		$action = sanitize_text_field( $_POST['action'] );
		if ( 'action_export_scraping_results' !== $action ) {
			return;
		}

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
			error_log( 'Export Acción ejecutada' );

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
	}
}

Settings_Hooks::init();
