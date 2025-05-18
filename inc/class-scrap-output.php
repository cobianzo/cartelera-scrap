<?php

/**
 * This file contains the implementation of [describe the purpose of the file briefly].
 *
 * @package [Specify the package or module name, if applicable]
 * @author [Your Name]
 * @copyright [Year] [Your Organization or Name]
 * @license [Specify the license, e.g., MIT, GPL, etc.]
 * @version [Version of the file, if applicable]
 *
 * @description [Provide a brief description of the file's functionality or purpose.]
 */

namespace Cartelera_Scrap;

use Cartelera_Scrap\Admin\Settings_Page;
use Cartelera_Scrap\Helpers\Results_To_Save;
use Cartelera_Scrap\Helpers\Queue_To_Process;


class Scrap_Output {

	public static function init() {

		// The notices if we come back grom the cron job with message.
		add_action( 'admin_init', function () {
			if ( isset( $_GET['error'] ) ) {
				$error_msg = sanitize_text_field( $_GET['error'] );
				add_settings_error( 'scrap_output', 'scrap_output_error', $error_msg, 'error' );
			}
			if ( isset( $_GET['message'] ) ) {
				$msg = sanitize_text_field( $_GET['message'] );
				add_settings_error( 'scrap_output', 'cartelera_updated', $msg, 'updated' );
			}
		} );
	}


	/**
	 * Printing the button to start the scrapping process.
	 *
	 * @return void
	 */
	public static function render_scrap_status() {

		?>
		<div class="wrap" style="display: flex; gap: 10px;">

			<?php
			Settings_Page::create_form_button_with_action(
				'action_start_scrapping_shows',
				__( 'Cleanup results and start processing NOW', 'cartelera-scrap' )
			);

			$next_show = Queue_To_Process::get_first_queued_show();
			if ( $next_show ) :  // next show exists, so we suggest to process it.
				$extra_html_after_button = sprintf( '<input type="number" name="shows_per_batch" value="%s" min="1" max="100" style="width: 5em;" />', Settings_Page::get_plugin_setting( Settings_Page::NUMBER_PROCESSED_EACH_TIME ) ?? 10 );
				Settings_Page::create_form_button_with_action(
					'action_process_next_scheduled_show',
					sprintf( __( 'Process next batch of ...', 'cartelera-scrap' ) ),
					[ 'extra-html' => $extra_html_after_button ]
				);
				?>
				<strong><?php echo esc_html( $next_show['text'] ); ?></strong>
			<?php endif; ?>
		</div>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<!-- click haction handled with js -->
			<button type="button" id="filter-by-yes-tickermaster" class="button toggle-button">
				Filter by results only in tickermaster
			</button>
			<!-- click haction handled with js -->
			<button type="button" id="filter-by-fail-tickermaster" class="button toggle-button">
				Filter by only failing matches
			</button>
			<!-- click haction handled with js -->
			<button type="button" id="hide-full-url" class="button toggle-button">
				Hide full url
			</button>


			<?php
				// the html for the results <table>

				self::render_table_with_results();
			?>
		</div>
		<?php
	}

	/**
	 * Renders the table with the results.
	 *
	 * @param array|null $overwritten_results The results to render. If null, we use the results from the database.
	 * @return void
	 */
	public static function render_table_with_results( $overwritten_results = null ): void {
		$results = $overwritten_results ?? Results_To_Save::get_show_results();
		if ( $results ) :
			?>
			<h2>Results (<?php echo esc_html( count( $results ) ); ?>) </h2>
			<table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;"
				class="equal-width-columns">
				<thead>
					<tr>
						<th class="col-actions">actions</th>
						<th class="col-index">#</th>
						<th class="col-title">Title and urls</th>
						<th class="col-cartelera-text">cartelera dates in text (scraped)</th>
						<th class="col-ticketmaster-dates">ticketmaster dates (scraped)</th>
						<th class="col-cartelera-dates">cartelera dates parsed</th>
						<th class="col-comparison">coincidence check</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $results as $i => $result ) :

						$result['computed']                 = empty( $result['computed'] ) ? [] : $result['computed'];
						$result['computed']['cartelera']    = Parse_Text_Into_Dates::computed_data_cartelera_result( $result );
						$result['computed']['ticketmaster'] = Parse_Text_Into_Dates::computed_data_ticketmaster_result( $result );
						$result['computed']['comparison']   = Parse_Text_Into_Dates::computed_dates_comparison_result( $result );

						// we update the values of the result based on today. ($result is passed by ref)
						$is_result_successful = Parse_Text_Into_Dates::computed_for_today_is_comparison_successful( $result );
						$no_tickermaster = ( empty( $result['ticketmaster']['dates'] ) || ! isset( $result['ticketmaster']['url'] ) );

						// column title
						ob_start();
						self::render_col_title( $result );
						$col_title_html = ob_get_clean();

						ob_start();
						// column cartelera dates and tweekdays and times in text
						self::render_col_cartelera_text_datetimes( $result );
						self::render_times_parsed_sentences( $result );
						$col_cartelera_text_html   = ob_get_clean();

						ob_start();
						// column ticketmaster parsed dates
						self::render_col_ticketmaster_dates( $result );
						$col_ticketmaster_dates_html = ob_get_clean();
						// now that we have rendered them, remove also the dates outside the limit,
						// we don't want to compared them with ticketmasters.

						ob_start();
						self::render_col_cartelera_datetimes( $result );
						$col_cartelera_dates_html = ob_get_clean();

						ob_start();
						self::render_col_comparison( $result );
						$col_comparison_result_html = ob_get_clean();


						// Now we render the calculated values as HTML in the table cels:
						// ================================================================
						?>
						<tr id="result-<?php echo esc_attr( sanitize_title( $result['title'] ) ); ?>"
							class="result-row
							<?php
								echo esc_attr( $no_tickermaster ? 'no-tickermaster ' : 'yes-tickermaster ' );
								echo $is_result_successful ? 'comparison-success ' : '';
								echo false === $is_result_successful ? 'comparison-fail ' : '';
							?>
							">





							<td class="col-actions">
								<?php
								Settings_Page::create_form_button_with_action(
									'action_scrap_single_show',
									'Re scrap',
									[
										'extra-data' => [
											'show-title' => $result['title'],
											'cartelera-href' => $result['cartelera']['url'],
										],
									]
								);
								?>
							</td>

							<?php // cell 0: order ?>
							<td class="col-index"> <!-- #i -->
								<?php echo esc_html( $i ); ?>
							</td>



							<?php // cell 1: title and urls ?>
							<td class="col-title"> <!-- Display the title and URL -->
								<?php echo $col_title_html; ?>
							</td>

							<?php // Display the cartelera dates in text ?>
							<td class="col-cartelera-text">
								<?php echo $col_cartelera_text_html; ?>
							</td>

							<?php // <!-- Display the ticketmaster dates Y-m-d H:i --> ?>
							<td class="col-ticketmaster-dates">
								<p>TickH: 🎫🎫🎫🎫🎫 </p>
								<ul class="cartelera-dates">
									<?php
									echo $col_ticketmaster_dates_html;
									?>
								</ul>
							</td>

							<td class="col-cartelera-dates"> <!-- Display the cartelera dates parsed -->
								<p>CartH: 🎟️🎟️ </p>
								<ul class="cartelera-dates">
									<?php
								echo $col_cartelera_dates_html;
								?>
								</ul>

							</td>

							<td class="col-comparison"> <!-- Display the coincidence check -->
								<?php
								// cross-compare the definitives dates and times, they must match with the ones from ticketmaster
								// we cleanup dates in both which are before today
								echo $col_comparison_result_html;
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>

			</table>
			<?php
		else :
			?>
			<p>No results to display.</p>
			<?php
		endif;
	}

	/**
	 * $result has format: [ title => ..., cartelera => [...], ticketmaster => [...]]
	 *
	 * @param array $result
	 * @return string the html
	 */
	public static function render_col_title( array $result ): void {
		?>
		<strong><?php echo esc_html( $result['title'] ); ?></strong>
		<ul>
			<?php if ( empty( $result['ticketmaster']['dates'] ) || ! isset( $result['ticketmaster']['url'] ) ) : ?>
				<li style="color: red;">No information in ticketmaster</li>
			<?php else : ?>
				<li>
					<a href="<?php echo esc_url( $result['ticketmaster']['url'] ); ?>" target="_blank">
						<?php echo __( 'ticketmaster search link', 'cartelera-scrap' ); ?>
						<span class="full-url"><?php echo esc_url( $result['ticketmaster']['url'] ); ?></span>
					</a>
				</li>
			<?php endif; ?>
			<li>
				Title in tm:
				<?php
				$title_tm = $result['ticketmaster']['tm_title'] ?? '';
				printf( // show the title if there is a thumbnail with it on the top of the results page
					'<b>%s %s</b>',
					( strtolower( $title_tm ) === strtolower( $result['title'] ) ) ? '✅' : '⚠️',
					esc_html( $title_tm ? $title_tm : 'not found' )
				);
				if ( ! empty( $result['ticketmaster']['tm_titles_list'] ) ) {
					echo '<br/> - ';
					echo implode( '<br/> - ', $result['ticketmaster']['tm_titles_list'] );
				}
				?>
				<?php
				if ( isset( $result['ticketmaster']['search_results'] ) && (int) $result['ticketmaster']['search_results'] > 1 ) {
					printf( '<br/>⚠️ Attention. Ticketmaster found more than one show with similar title to %s', $result['title'] );
				}
				?>
				<br/>
				<?php if ( ! empty( $result['ticketmaster']['single_page_url'] ) ) : ?>
					<a href="<?php echo esc_url( $result['ticketmaster']['single_page_url'] ); ?>" target="_blank">
						<?php echo __( 'ticketmaster single page', 'cartelera-scrap' ); ?>
					</a>
				<?php endif ?>
			</li>
			<!-- // cartelera url -->
			<li>
				<a href="<?php echo esc_url( $result['cartelera']['url'] ); ?>" target="_blank">
					<?php echo __( 'cartelera link', 'cartelera-scrap' ); ?>
					<span class="full-url"><?php echo esc_url( $result['cartelera']['url'] ); ?></span>
				</a>
			</li>
			<li>
				<div class="accordion">
					<label for="toggle-<?php echo esc_attr( sanitize_title( $result['title'] ) ); ?>" class="accordion-label">
						<input type="checkbox" id="toggle-<?php echo esc_attr( sanitize_title( $result['title'] ) ); ?>"
									class="accordion-toggle"> Show debug info
						<div class="accordion-content">
								<?php
								$result['ticketmaster']['dates'] = count( $result['ticketmaster']['dates'] ) . ' elements';
								echo '<h2>debugging info</h2>';
								echo '<pre>';
								print_r( $result );
								echo '</pre>';
								?>
						</div>
					</label>
				</div>
			</li>
		</ul>
		<?php
	}

	/**
	 * Undocumented function
	 *
	 * @param array $result
	 * @return void
	 */
	public static function render_col_ticketmaster_dates( $result ): void {

		// add success or fail to the class of the date.
		// Sort comparison array by timestamp (key) in ascending order
		ksort($result['computed']['comparison']);

		foreach ( $result['computed']['comparison'] as $tm_timestamp => $date_with_full_info ) {

			// pre compute the date.
			if ( ! $date_with_full_info['ticketmaster'] ) {
				continue;
			}

			$color = $date_with_full_info['cartelera'] ? 'date-found color-success' : 'date-not-found color-danger';
			self::render_date_from_fulldate_indo( $tm_timestamp, $date_with_full_info, $color );

		} // end foreach date.

	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $result
	 * @return array
	 */
	public static function render_col_cartelera_text_datetimes( $result ): array {
		// Dates
		// To extract the dates from the text we:
		// - confirm that the text is a valid dates information,(first_ acceptance_of_ date_text)
		// - extracting more than one sentence needed, and sanitize a little
		// - converts that sanitized text into the dates that it represents
		// - (calling main function identify _ dates _ sentence _ daterange _ or _ singledays)
		$sentences = [];
		if ( ! empty( $result['cartelera']['scraped_dates_text'] ) ) {
			$dates_text = $result['cartelera']['scraped_dates_text'];
			echo '<p>';
			echo '<b>Dates</b>==> ' . esc_html( $dates_text );

			if ( ! isset( $result['computed']['cartelera']['first_acceptance_dates']['output'] ) ) {
				echo '❌ no sentences extracted <br/>';
			} else {
				$sentences = $result['computed']['cartelera']['first_acceptance_dates']['output'];
				$count     = count( $sentences );
				echo $count ? '✅ (' . $count . ')' : '❌ text not parseable <br/>';
			}
			echo '</p>';

			if ( ! empty( $count ) ) {
				echo '<div class="dates-sentences">';
				echo '   <small class="muted">Parsed text for dates:</small> <br/>';
				echo '   <em>' . implode( '</em><br/><em>📆📆📆', $sentences ) . '</em>';
				echo '</div>';
			}
		} else {
			echo '❌ not valid! <br/>';
		}

		return $sentences;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $result
	 * @return array
	 */
	public static function render_times_parsed_sentences( array $result ): array {
		// Weekday and times
		$sentences = [];
		if ( ! empty( $result['cartelera']['scraped_time_text'] ) ) {
			if ( isset( $result['computed']['cartelera']['first_acceptance_times']['output'] ) ) {
				$sentences = $result['computed']['cartelera']['first_acceptance_times']['output'] ?? '❌ no sentences extracted from times <br/>';
				$count = count( $sentences );
			}
			echo '<p>';
			echo '<b>Times</b>==> ' . esc_html( $result['cartelera']['scraped_time_text'] );
			echo ! empty( $count ) ? '✅ (' . $count . ')' : '❌ times not parseable <br/>';
			echo '</p>';
		}
		// Times
		if ( ! empty( $sentences ) ) {
			echo '<div class="times-sentences">';
			echo '   <small class="muted">Parsed text for weekday and time:</small> <br/>';
			echo '   <em>' . implode( '</em><br/><em>', $sentences ) . '</em>';
			// echo '   <br/><small class="muted">Show is played these days of the week:</small> <br/>';
			// echo implode( ', ', Parse_Text_Into_Dates::get_all_days_of_week_in_sentences( $sentences ) );
			echo '</div>';
		}
		return $sentences;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $result
	 * @return void
	 */
	public static function render_col_cartelera_datetimes( array $result ): void {

		// adds success or fail to the class of the date.
		foreach ( $result['computed']['comparison'] as $car_timestamp => $date_with_full_info ) {

			if ( ! $date_with_full_info['cartelera'] ) {
				continue;
			}

			$color = $date_with_full_info['ticketmaster'] ? 'date-found color-success' : 'date-not-found color-danger';
			self::render_date_from_fulldate_indo( $car_timestamp, $date_with_full_info, $color );
		}

	}

	/**
	 * Undocumented function
	 *
	 * @param array $result
	 * @return boolean|null
	 */
	public static function render_col_comparison( array $result ): bool|null {
		$dates_cart = $result['computed']['cartelera']['definitive_datetimes']['output'] ?? [];
		$dates_tick = $result['computed']['ticketmaster']['definitive_datetimes']['output'] ?? [];
		if ( empty( $dates_cart ) && empty( $dates_tick ) ) {
			echo 'Both dates are empty';
			return null;
		}
		if ( empty( $dates_tick ) ) {
			echo 'No information from ticketmaster, at least within the range of dates of your criteria.';
			echo '<br/>No comparison has been made. Check it manually.';
			return null;
		}

		$is_successful = $result['computed']['success'] ?? null;

		if ( true === $is_successful ) {
			echo '👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻 Yuhuuu all good';
			return true;
		} else {
				echo '<br/>All baaad here: ❌❌❌❌❌❌❌❌❌❌❌❌<br/><br/><br/>';
				return false;
		}
		return null;
	}

	public static function render_date_from_fulldate_indo( string $timestamp, array $date_with_full_info, string $color ) {
		$extra_info     = $date_with_full_info['extra'] ?? [];
		$is_valid       = empty( $extra_info['invalid-for-comparison'] );
		$invalid_reason = $extra_info['invalid-for-comparison'] ?? '';

		// found? not found in cartelera? we paint it gree/red.
		printf( '<li data-date="%s" class="%s %s"><b>%s</b> <small class="muted">%s</small></li>',
			esc_attr( $timestamp ),
			$color,
			$invalid_reason . ' ' . ( $is_valid ? 'valid' : 'invalid' ),
			$date_with_full_info['datetime'],
			strtolower( date( 'D', $timestamp ) )
		);
	}
}

Scrap_Output::init();
