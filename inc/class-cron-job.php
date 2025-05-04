<?php

namespace Cartelera_Scrap;

use Cartelera_Scrap\Admin\Settings_Page;
use Cartelera_Scrap\Admin\Settings_Hooks;
use Cartelera_Scrap\Scrap_Actions;
use Cartelera_Scrap\Helpers\Queue_And_Results;


class Cron_Job {


	const CRONJOB_NAME = 'cartelera-scrap_automated_cron';

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public static function init() {
		// define what the cron job does
		add_action( self::CRONJOB_NAME, function () {
			Scrap_Actions::perform_scrap();
			// after prcessing the batch it will call the one-time-off
			// cron job recurrently until finished
		} );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function start_schedule_cron_job_at_midnight(): void {
		if ( ! wp_next_scheduled( self::CRONJOB_NAME ) ) {
			$frequency = Settings_Page::get_plugin_setting( Settings_Page::OPTION_CRON_FREQUENCY );
			$midnight  = strtotime( 'tomorrow midnight' );
			wp_schedule_event( $midnight, $frequency, self::CRONJOB_NAME );
		}
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function stop_schedule_cron_job(): void {
		$timestamp = wp_next_scheduled( self::CRONJOB_NAME );
		if ( $timestamp ) {
			wp_clear_scheduled_hook( self::CRONJOB_NAME );
		}
	}

	/**
	 * Just information about the cron jobs
	 *
	 * @return string
	 */
	public static function get_next_cronjob_execution_time(): string {


		$timestamp = wp_next_scheduled( self::CRONJOB_NAME );

		$text = '';
		if ( $timestamp ) {
			$text .= sprintf( 'The cron job is scheduled for: <b>%s</b> h.', date_i18n( 'l, F j, Y H:i', $timestamp ) );
		} else {
			$text .= 'The cron job is not scheduled.<br/>';
		}

		$next_onetimeoff_timestamp = wp_next_scheduled( Settings_Hooks::ONETIMEOFF_CRONJOB_NAME );
		if ( $next_onetimeoff_timestamp ) {
			$initial_difference_in_seconds = $next_onetimeoff_timestamp - time();
			$difference_in_minutes         = abs( floor( $initial_difference_in_seconds / 60 ) );
			$difference_in_seconds         = abs( $initial_difference_in_seconds % 60 );
			$readable_difference           = sprintf( '%d minutes and %d seconds', $difference_in_minutes, $difference_in_seconds );

			$text .= sprintf( 'There is current work in process being called recursivelly, with %s shows in the queue to be scraped<br/>', count( Queue_And_Results::get_queued_shows() ) );
			if ( $initial_difference_in_seconds > 0 ) {
				$text .= sprintf( 'The next batch will be executed in %s ', $readable_difference );
			} else {
				$text .= sprintf( 'The next batch will should have been executed in %s <br/>
				You can click the button "Process Next Batch" to trigger it manually', $readable_difference );
			}
		}

		return $text;
	}
}

Cron_Job::init();
