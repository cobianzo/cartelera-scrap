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
		if ( wp_next_scheduled( Settings_Hooks::CRONJOB_NAME ) ) {
			_e( '<h3>Scrapping is running as a cron job</h3>', 'cartelera-scrap' );
			printf( __( '<p>Shows in the processing queue waiting to be processed: %s<br />', 'cartelera-scrap' ), Scrap_Actions::get_queued_count() );
			printf( __( 'Already processed shows: %s</p>', 'cartelera-scrap' ), count( Scrap_Actions::get_show_results() ) );
			$queue = Scrap_Actions::get_first_queued_show();
			if ( $queue ) {
				echo '<p>Next show to Scrap:  ' . $queue['text'] . '</p>';
			} else {
				echo '<p>Nothing in the queue to scrap</p>';
			}
		} else {
			echo '<p>Scrapping ' . Settings_Hooks::CRONJOB_NAME . ' is not running as a cron job</p>';
		}
		?>
		<div class="wrap" style="display: flex; gap: 10px;">

			<?php
			Settings_Page::create_form_button_with_action( 'action_start_scrapping_shows', __( 'Start processing from scratch', 'cartelera-scrap' ) );

			$next_show = Scrap_Actions::get_first_queued_show();
			if ( $next_show ) :
				?>
				<form method="post" style="display: flex; align-items: center; gap: 10px;">
				<?php wp_nonce_field( 'nonce_action_field', 'nonce_action_scrapping' ); ?>
					<input type="hidden" name="action" value="action_process_next_scheduled_show">
					<div style="display:flex; align-items: center; gap: 10px;">
					<input type="submit" class="button button-primary" value="<?php echo esc_attr( sprintf( __( 'Process next batch of', 'cartelera-scrap' ), Cartelera_Scrap_Plugin::get_plugin_setting( Settings_Page::$number_processed_each_time ) ) ); ?>" />
						<strong><?php echo esc_html( $next_show['text'] ); ?></strong>
					</div>
				</form>
			<?php endif; ?>
		</div>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
				$results = Scrap_Actions::get_show_results();
			if ( $results ) :
				?>
					<?php // dd( $results ); todelete ?>
					<h2>Results</h2>
					<table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;"
					 class="equal-width-columns">
						<thead>
							<tr>
								<th>#</th>
								<th>Title and urls</th>
								<th>ticketmaster dates</th>
								<th style="width:200px">cartelera dates in text</th>
								<th>cartelera dates parsed</th>
								<th>coincidence check</th>
								<th>actions</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $results as $i => $result ) :
								?>
									<tr style="border: 1px solid #ccc;">

										<td> <!-- #i -->
											<?php echo esc_html( $i ); ?>
										</td>
										<td> <!-- Display the title and URL -->
											<strong><?php echo esc_html( $result['title'] ); ?></strong>
											<ul>
												<?php if ( empty( $result['ticketmaster']['dates'] ) || ! isset( $result['ticketmaster']['url'] ) ) : ?>
													<li style="color: red;">No information in ticketmaster</li>
												<?php else : ?>
													<li>
														<a href="<?php echo esc_url( $result['ticketmaster']['url'] ); ?>" target="_blank">
															<?php echo esc_html( str_replace( 'https://', '', $result['ticketmaster']['url'] ) ); ?>
														</a>
													</li>
												<?php endif; ?>
												<!-- // cartelera url -->
												<li>
													<a href="<?php echo esc_url( $result['cartelera']['url'] ); ?>" target="_blank">
													<?php echo esc_html( str_replace( 'https://', '', $result['cartelera']['url'] ) ); ?>
													</a>
												</li>
											</ul>
										</td>

										<td> <!-- Display the ticketmaster dates -->
										<?php
											if ( ! empty( $result['ticketmaster']['dates'] ) ) {
												echo "<p>TickH: 🎫🎫🎫🎫🎫 </p>";
												foreach ( $result['ticketmaster']['dates'] as $date ) {
													echo '<p>' . esc_html( $date['date'] . ' ' . $date['time'] ) . '</p>';
												}
											} else {
												echo 'No dates found';
											}
											?>
										</td>

										<td style="width:200px"> <!-- Display the cartelera dates in text -->
											<?php
											// Dates
											// To extract the dates from the text we:
											// - confirm that the text is a valid dates information,(first_acceptance_of_date_text)
											// 		- extracting more than one sentence needed, and sanitize a little
											// - converts that sanitized text into the dates that it represents
											// 		- (identify_dates_sencence_daterange_or_singledays)
											if ( ! empty( $result['cartelera']['scraped_dates_text'] ) ) {
												$dates_text = $result['cartelera']['scraped_dates_text'];
												echo '<p>';
												echo  '<b>Dates</b>==> ' . esc_html( $dates_text );
												$accepted_sentences_dates = Text_Parser::first_acceptance_of_date_text( $dates_text );
												$accepted_sentences_count = count( $accepted_sentences_dates );
												echo $accepted_sentences_count ? '✅ (' . $accepted_sentences_count . ')' : '❌ text not parseable <br/>';
												echo  '</p>';

												if ( $accepted_sentences_count ) {
													echo '<div class="dates-sentences">';
													echo '<em>📆📆📆' . implode( '</em><br/><em>📆📆📆', $accepted_sentences_dates ) . '</em>';
													echo '</div>';
												}

											} else {
												echo '❌ not valid! <br/>';
												dd($result['cartelera']);
											}

											// Weekday and times
											if ( ! empty( $result['cartelera']['scraped_time_text'] ) ) {
												$accepted_sentences_time = Text_Parser::first_acceptance_of_times_text($result['cartelera']['scraped_time_text']);
												echo '<p>';
												echo  '<b>Times</b>==> ' .esc_html( $result['cartelera']['scraped_time_text'] );
												$accepted_sentences_count = count( $accepted_sentences_time );
												echo $accepted_sentences_count ? '✅ (' . $accepted_sentences_count . ')' : '❌ times not parseable <br/>';
												echo  '</p>';
											}
											?>
										</td>

										<td> <!-- Display the cartelera dates parsed -->
											<?php
											// Day sof month
											// parse dates to get specific calendar dates.
											$all_dates = [];
											foreach ( $accepted_sentences_dates as $dates_in_text) {
												$all_dates = array_merge( $all_dates,
													Text_Parser::identify_dates_sencence_daterange_or_singledays($dates_in_text)
												);
											}
											foreach ( $all_dates as $show_date ) {
												echo esc_html( date( 'l, F j, Y', strtotime( $show_date ) ) ) . '<br />';
											}

											// Times
											if ( ! empty( $accepted_sentences_time ) ) {
												echo '<div class="times-sentences">';
												echo '<em>' . implode( '</em><br/><em>', $accepted_sentences_time ) . '</em>';
												echo '</div>';
												echo implode( ', ', Text_Parser::get_all_days_of_week_in_sentences( $accepted_sentences_time ) );
											}

											?>
										</td>

										<td> <!-- Display the coincidence check -->
											<?php
											// cross-compare the definitives dates and times, they must match with the ones from ticketmaster
											$datetimes_cartelera = Text_Parser::definitive_dates_and_times( $all_dates, $accepted_sentences_time );
											// echo implode( ', ', $dates );
											$datetimes_ticketmaster = [];
											foreach ( $result['ticketmaster']['dates'] as $date_info ) {
												$datetimes_ticketmaster[] = $date_info['date'] . ' ' . $date_info['time'];
											}

											// DEBUG TODELETE
											// echo '<br>TODELETE - comparing';
											// echo '<br>ticketmaster';
											// echo '<pre>';
											// print_r($datetimes_ticketmaster);
											// echo '</pre>';
											// echo '<br>cartela';
											// echo '<pre>';
											// print_r($datetimes_cartelera);
											// echo '</pre>';

											// we cleanup dates in both which are before today
											$datetimes_cartelera    = Text_Parser::remove_dates_previous_of_today( $datetimes_cartelera );
											$datetimes_ticketmaster = Text_Parser::remove_dates_previous_of_today( $datetimes_ticketmaster );
											$result_compare = Text_Parser::compare_arrays( $datetimes_cartelera, $datetimes_ticketmaster );
											if ( true === $result_compare ) {
												echo '👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻👍🏻 Yuhuuu all good';
											} elseif ( ! isset( $result_compare['only_in_a'] ) && ! isset( $result_compare['only_in_b'] ) ) {
												echo 'Error in comparing function (compare_arrays)';
											} else {
												echo '<br/>All baaad here: ❌❌❌❌❌❌❌❌❌❌❌❌<br/><br/><br/>';
												if ( ! empty( $result_compare['only_in_a'] ) ) {
													echo '<br>dates in cartelera not in tickermaster: <br/>';
													echo implode( '<br/>', $result_compare['only_in_a'] );
												}
												if ( ! empty( $result_compare['only_in_b'] ) ) {
													echo '<br>dates in tickermaster not in cartelera: <br/>';
													echo implode( '<br/>', $result_compare['only_in_b'] );
												}


											}


											?>
										</td>
										<td>
											<?php
											Settings_Page::create_form_button_with_action(
												'action_scrap_single_show', 'Re scrap',
												[
													'extra-data' => [
														'show-title'     => $result['title'],
														'cartelera-href' => $result['cartelera']['url'],
													]
												]
											);
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
				?>
		</div>
		<?php
	}
}

Scrap_Output::init();
