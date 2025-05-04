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

use Cartelera_Scrap\Admin\Settings_Hooks;
use Cartelera_Scrap\Admin\Settings_Page;
use Cartelera_Scrap\Helpers\Queue_And_Results;

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

		// check if the cron job is running
		if ( wp_next_scheduled( Settings_Hooks::ONETIMEOFF_CRONJOB_NAME ) ) {
			_e( '<h3>Scrapping is running as a cron job</h3>', 'cartelera-scrap' );
			printf( __( '<p>Shows in the processing queue waiting to be processed: %s<br />', 'cartelera-scrap' ), Queue_And_Results::get_queued_count() );
			printf( __( 'Already processed shows: %s</p>', 'cartelera-scrap' ), count( Queue_And_Results::get_show_results() ) );
			$queue = Queue_And_Results::get_first_queued_show();
			if ( $queue ) {
				echo '<p>Next show to Scrap:  ' . $queue['text'] . '</p>';
			} else {
				echo '<p>Nothing in the queue to scrap</p>';
			}
		} else {
			echo '<p>Scrapping ' . Settings_Hooks::ONETIMEOFF_CRONJOB_NAME . ' is not running as a cron job</p>';
		}
		?>
		<div class="wrap" style="display: flex; gap: 10px;">

			<?php
			Settings_Page::create_form_button_with_action(
				'action_start_scrapping_shows',
				__( 'Cleanup results and start processing NOW', 'cartelera-scrap' )
			);

			$next_show = Queue_And_Results::get_first_queued_show();
			if ( $next_show ) :
				?>
				<form method="post" style="display: flex; align-items: center; gap: 10px;">
					<?php wp_nonce_field( 'nonce_action_field', 'nonce_action_scrapping' ); ?>
					<input type="hidden" name="action" value="action_process_next_scheduled_show">
					<div style="display:flex; align-items: center; gap: 10px;">
						<input type="submit" class="button button-primary" value="<?php echo esc_attr( sprintf( __( 'Process next batch of %s', 'cartelera-scrap' ), Settings_Page::get_plugin_setting( Settings_Page::NUMBER_PROCESSED_EACH_TIME ) ) ); ?>" />
						<strong><?php echo esc_html( $next_show['text'] ); ?></strong>
					</div>
				</form>
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

	public static function render_table_with_results() {
		$results = Queue_And_Results::get_show_results();
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
					// WIP: calculate all computed, and show utput based on these calculations
					// if I dont finish this, @TOELETE.
					// calculate all the computed values based on what we scrapped.
					foreach ( $results as $i => $result ) :
						$dates_text = $result['cartelera']['scraped_dates_text'] ?? '';
						$times_text = $result['cartelera']['scraped_time_text'] ?? '';
						$computed   = [];
						// cartelera computed: sentences cartelera dates and times
						$computed['sentences_cartelera_dates'] = Text_Parser::first_acceptance_of_date_text( $dates_text );
						$computed['sentences_cartelera_times'] = Text_Parser::first_acceptance_of_times_text( $times_text );


					endforeach;

					foreach ( $results as $i => $result ) :
						$no_tickermaster = ( empty( $result['ticketmaster']['dates'] ) || ! isset( $result['ticketmaster']['url'] ) );

						// retrieve the info for every col beforehand, the use the html as placeholders
						// we need to do this so we can know the final comparison result befoe we render the
						// <tr>, and assing the success or fail class

						// column title
						ob_start();
						self::render_col_title( $result );
						$col_title_html = ob_get_clean();

						ob_start();
						// column cartelera dates and tweekdays adn times in text
						$sentences_cartelera_dates = self::render_col_cartelera_text_datetimes( $result );
						$sentences_cartelera_times = self::render_times_parsed_sentences( $result );
						$col_cartelera_text_html   = ob_get_clean();

						ob_start();
						// column ticketmaster parsed dates
						$datetimes_tickermaster      = self::render_col_ticketmaster_dates( $result );
						$col_ticketmaster_dates_html = ob_get_clean();
						// now that we have rendered them, remove also the dates outside the limit,
						// we don't want to compared them with ticketmasters.
						$datetimes_ticketmaster = Text_Parser::remove_dates_after_limit( $datetimes_tickermaster );

						ob_start();
						$datetimes_cartelera      = self::render_col_cartelera_datetimes( $sentences_cartelera_dates, $sentences_cartelera_times );
						$col_cartelera_dates_html = ob_get_clean();
						// now that we have rendered them, remove also the dates outside the limit,
						// we don't want to compared them with ticketmasters.
						$datetimes_cartelera = Text_Parser::remove_dates_after_limit( $datetimes_cartelera );
						ob_start();
						$comparison_success         = self::render_col_comparison( $datetimes_cartelera, $datetimes_ticketmaster );
						$col_comparison_result_html = ob_get_clean();


						// Now we render the calculated values as HTML in the table cels:
						// ================================================================
						?>
						<tr id="result-<?php echo esc_attr( sanitize_title( $result['title'] ) ); ?>"
							class="result-row
							<?php
								echo esc_attr( $no_tickermaster ? 'no-tickermaster ' : 'yes-tickermaster ' );
								echo $comparison_success ? 'comparison-success ' : '';
								echo false === $comparison_success ? 'comparison-fail ' : '';
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
								<?php
								// add success or fail to the class of the date.
								foreach ( $datetimes_tickermaster as $tm_date ) {

									$tm_timestamp = strtotime( $tm_date );

									// not founf in cartelera? we paint it red.
									$class = ( false !== strpos(
										$col_cartelera_dates_html,
										'data-date="' . esc_attr( $tm_timestamp ) . '"'
									) ) ? 'date-found color-success' : 'date-not-found color-danger';

									$col_ticketmaster_dates_html = str_replace(
										'data-date="' . esc_attr( $tm_timestamp ) . '" class="',
										'data-date="' . esc_attr( $tm_timestamp ) . '" class="' . $class . ' ',
										$col_ticketmaster_dates_html
									);
								}

								echo $col_ticketmaster_dates_html;
								?>
							</td>

							<td class="col-cartelera-dates"> <!-- Display the cartelera dates parsed -->
								<p>CartH: 🎟️🎟️ </p>
								<?php
								// adds success or fail to the class of the date.
								foreach ( $datetimes_cartelera as $car_date ) {

									$car_timestamp = strtotime( $car_date );

									// not founf in cartelera? we paint it red.
									$class = ( false !== strpos(
										$col_ticketmaster_dates_html,
										'data-date="' . esc_attr( $car_timestamp ) . '"'
									) ) ? 'date-found color-success' : 'date-not-found color-danger';

									$col_cartelera_dates_html = str_replace(
										'data-date="' . esc_attr( $car_timestamp ) . '" class="',
										'data-date="' . esc_attr( $car_timestamp ) . '" class="' . $class . ' ',
										$col_cartelera_dates_html
									);
								}
								?>
								<?php echo $col_cartelera_dates_html; ?>
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
	 * @return array
	 */
	public static function render_col_ticketmaster_dates( $result ): array {

		$datetimes = [];
		if ( ! empty( $result['ticketmaster']['dates'] ) ) :
			?>
			<ul>
				<?php
				// loop every date we will show as html
				foreach ( $result['ticketmaster']['dates'] as $k => $date ) {
					// add the time to the date, we'll return as result of this function
					$datetime    = $date['date'] . ' ' . $date['time']; // YYYY-mm-dd H:i
					$datetimes[] = $datetime;

					// check if date is later than our limit for muted output, and show it muted
					$number_events_limit  = (int) Settings_Page::get_plugin_setting( Settings_Page::LIMIT_NUMBER_DATES_COMPARE ) ?? 20;
					$date_timestamp_limit = Text_Parser::get_limit_datetime();
					$not_analyzed         = $date_timestamp_limit ? \strtotime( $datetime ) > $date_timestamp_limit : false;
					$not_analyzed         = $not_analyzed || $k >= $number_events_limit;
					echo '<li
						data-date="' . esc_attr( \strtotime( $datetime ) ) . '" class="'
						. ( $not_analyzed ? esc_attr( ( ( $k >= $number_events_limit ) ? 'dark-' : '' ) . 'muted' ) : '' ) . '">'
						. esc_html( $datetime ) . sprintf( ' <small class="dark-muted">%s</small>', strtolower( date( 'D', strtotime( $datetime ) ) ) )
						. '</li>';

					if ( $not_analyzed ) {
						$not_analyzed_left = ( count( $result['ticketmaster']['dates'] ) - ( $k + 1 ) );
						echo $not_analyzed_left ? '<li>... and ' . $not_analyzed_left . ' more </li>' : '';
						break;
					}
				}
				?>
			</ul>

			<?php
		else :
			?>
			No dates found
			<?php
		endif;

		return $datetimes;
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
		// - confirm that the text is a valid dates information,(first_acceptance_of_date_text)
		// - extracting more than one sentence needed, and sanitize a little
		// - converts that sanitized text into the dates that it represents
		// - (calling main function identify _ dates _ sentence _ daterange _ or _ singledays)
		$sentences = [];
		if ( ! empty( $result['cartelera']['scraped_dates_text'] ) ) {
			$dates_text = $result['cartelera']['scraped_dates_text'];
			echo '<p>';
			echo '<b>Dates</b>==> ' . esc_html( $dates_text );
			$sentences = Text_Parser::first_acceptance_of_date_text( $dates_text );
			$count     = count( $sentences );
			echo $count ? '✅ (' . $count . ')' : '❌ text not parseable <br/>';
			echo '</p>';

			if ( $count ) {
				echo '<div class="dates-sentences">';
				echo '   <small class="muted">Parsed text for dates:</small> <br/>';
				echo '   <em>' . implode( '</em><br/><em>📆📆📆', $sentences ) . '</em>';
				echo '</div>';
			}
		} else {
			echo '❌ not valid! <br/>';
			dd( $result['cartelera'] );
		}

		return $sentences;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $result
	 * @return void
	 */
	public static function render_times_parsed_sentences( array $result ) {
		// Weekday and times
		$sentences = [];
		if ( ! empty( $result['cartelera']['scraped_time_text'] ) ) {
			$sentences = Text_Parser::first_acceptance_of_times_text( $result['cartelera']['scraped_time_text'] );
			echo '<p>';
			echo '<b>Times</b>==> ' . esc_html( $result['cartelera']['scraped_time_text'] );
			$count = count( $sentences );
			echo $count ? '✅ (' . $count . ')' : '❌ times not parseable <br/>';
			echo '</p>';
		}
		// Times
		if ( ! empty( $sentences ) ) {
			echo '<div class="times-sentences">';
			echo '   <small class="muted">Parsed text for weekday and time:</small> <br/>';
			echo '   <em>' . implode( '</em><br/><em>', $sentences ) . '</em>';
			// echo '   <br/><small class="muted">Show is played these days of the week:</small> <br/>';
			// echo implode( ', ', Text_Parser::get_all_days_of_week_in_sentences( $sentences ) );
			echo '</div>';
		}
		return $sentences;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $sentences_dates
	 * @param array $sentences_times
	 * @return array
	 */
	public static function render_col_cartelera_datetimes( array $sentences_dates, array $sentences_times ): array {
		// Day sof month
		// parse dates to get specific calendar dates.
		$all_dates = [];
		foreach ( $sentences_dates as $dates_in_text ) {
			if ( 0 === strpos( $dates_in_text, 'suspende' ) ) {
				$removing_dates = Text_Parser::identify_dates_sentence_daterange_or_singledays( $dates_in_text );
				$all_dates      = array_diff( $all_dates, $removing_dates );
			} else {
				$all_dates = array_merge(
					$all_dates,
					Text_Parser::identify_dates_sentence_daterange_or_singledays( $dates_in_text )
				);
			}
		}
		$datetimes_cartelera             = Text_Parser::definitive_dates_and_times( $all_dates, $sentences_times, $sentences_dates );
		$datetimes_cartelera_after_today = Text_Parser::remove_dates_previous_of_today( $datetimes_cartelera );
		if ( empty( $datetimes_cartelera_after_today ) && ! empty( $datetimes_cartelera ) ) {
			printf( __( '<p>All dates are previous of today. Nothing to compare</p>', 'cartelera-scrap' ) );
		}
		// dont show dates previous of today, but show dates outside of analysis for being to far ahread in time
		echo '<ul>';
		$count = 0;
		foreach ( $datetimes_cartelera_after_today as $i => $show_date ) {
			$count++;
			// check if date is later than our limit
			$date_timestamp_limit = Text_Parser::get_limit_datetime();
			$number_events_limit  = (int) Settings_Page::get_plugin_setting( Settings_Page::LIMIT_NUMBER_DATES_COMPARE ) ?? 20;
			$not_analyzed         = $date_timestamp_limit ? \strtotime( $show_date ) >= $date_timestamp_limit : false;
			$not_analyzed         = $not_analyzed || $count > $number_events_limit;
			echo '<li data-date="' . esc_attr( \strtotime( $show_date ) ) . '" class="'
				. ( $not_analyzed ? esc_attr( ( ( $count >= $number_events_limit ) ? 'dark-' : '' ) . 'muted' ) : '' ) . '">'
					. esc_html( $show_date )
					. sprintf( ' <small class="dark-muted">%s</small>', strtolower( date( 'D', strtotime( $show_date ) ) ) )
				. '</li>';

			if ( $not_analyzed ) { // don't show more dates out of the range, simply mention how many left there are.
				$not_analyzed_left = ( count( $datetimes_cartelera_after_today ) - ( $i + 1 ) );
				echo $not_analyzed_left ? '<li>... and ' . $not_analyzed_left . ' more </li>' : '';
				break;
			}
		}
		echo '</ul>';

		return $datetimes_cartelera_after_today;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $dates_cart
	 * @param array $dates_tick
	 * @return boolean|null
	 */
	public static function render_col_comparison( array $dates_cart, array $dates_tick ): bool|null {
		if ( empty( $dates_cart ) && empty( $dates_tick ) ) {
			echo 'Both dates are empty';
			return null;
		}
		if ( empty( $dates_tick ) ) {
			echo 'No information from ticketmaster, at least within the range of dates of your criteria.';
			echo '<br/>No comparison has been made. Check it manually.';
			return null;
		}

		$result_compare = Text_Parser::compare_arrays( $dates_cart, $dates_tick );
		if ( true === $result_compare ) {
			echo '👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻 Yuhuuu all good';
			return true;
		} else {
				echo '<br/>All baaad here: ❌❌❌❌❌❌❌❌❌❌❌❌<br/><br/><br/>';
				return false;
			if ( ! empty( $result_compare['only_in_a'] ) ) {
				echo '<br>dates in cartelera not in tickermaster: <br/>';
				echo implode( '<br/>', $result_compare['only_in_a'] );
				return false;
			}
			if ( ! empty( $result_compare['only_in_b'] ) ) {
				echo '<br>dates in tickermaster not in cartelera: <br/>';
				echo implode( '<br/>', $result_compare['only_in_b'] );
				return false;
			}
		}
		return null;
	}
}

Scrap_Output::init();
