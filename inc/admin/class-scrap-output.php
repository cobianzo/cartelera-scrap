<?php

namespace Cartelera_Scrap;

class Scrap_Output {
	public static function init() {
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
		if ( wp_next_scheduled( Scrap_Actions::CRONJOB_NAME ) ) {
			echo '<p>Scrapping is running</p>';
			$queue = Scrap_Actions::get_first_queued_show();
			if ( $queue ) {
				echo '<p>Next show to Scrap:  ' . $queue['text'] . '</p>';
			} else {
				echo '<p>Nothing in the queue to scrap</p>';
			}
		} else {
			echo '<p>Scrapping ' . Scrap_Actions::CRONJOB_NAME . ' is not running</p>';
		}
		?>
		<div class="wrap" style="display: flex; gap: 10px;">

			<form method="post">
				<?php wp_nonce_field( 'nonce_action_field', 'nonce_action_scrapping' ); ?>
				<input type="hidden" name="start_scrapping_shows" value="1">
				<input type="submit" class="button button-primary" value="Ejecutar acción">
			</form>
			<?php
				$next_show = Scrap_Actions::get_first_queued_show();
			if ( $next_show ) :
				?>
				<form method="post" style="display: flex; align-items: center; gap: 10px;">
				<?php wp_nonce_field( 'nonce_action_field', 'nonce_action_scrapping' ); ?>
					<input type="hidden" name="process_next_scheduled_show" value="1">
					<div style="display:flex; align-items: center; gap: 10px;">
					<input type="submit" class="button button-primary" value="Process next show:"> <strong><?php echo esc_html( $next_show['text'] ); ?> </strong>
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
					<table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
						<thead>
							<tr>
								<th>Title and urls</th>
								<th>ticketmaster dates</th>
								<th>cartelera dates in text</th>
								<th>cartelera dates parsed</th>
								<th>coincidence check</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $results as $result ) :
								?>
									<tr style="border: 1px solid #ccc;">
										<td> <!-- Display the title and URL -->
											<strong><?php echo esc_html( $result['title'] ); ?></strong><br>
										<?php if ( empty( $result['ticketmaster']['dates'] ) || ! isset( $result['ticketmaster']['url'] ) ) : ?>
												<p style="color: red;">No information in ticketmaster</p>
											<?php else : ?>
												<a href="<?php echo esc_url( $result['ticketmaster']['url'] ); ?>" target="_blank">
													<?php echo esc_html( str_replace( 'https://', '', $result['ticketmaster']['url'] ) ); ?>
												</a> <br/>
											<?php endif; ?>
											<!-- // cartelera url -->
											<a href="<?php echo esc_url( $result['cartelera']['url'] ); ?>" target="_blank">
											<?php echo esc_html( str_replace( 'https://', '', $result['cartelera']['url'] ) ); ?>
											</a>
										</td>
										<td> <!-- Display the ticketmaster dates -->
										<?php
										if ( ! empty( $result['ticketmaster']['dates'] ) ) {
											dd($result['ticketmaster']['dates']);
										} else {
											echo 'No dates found';
										}
										?>
										</td>
										<td> <!-- Display the cartelera dates in text -->
											<?php
											if ( !empty( $result['cartelera']['scraped_dates_text'] ) ) {
												echo '<p>' . esc_html( $result['cartelera']['scraped_dates_text'] ) . '</p>';
											} else {
												dd($result['cartelera']);
											}
											if ( ! empty( $result['cartelera']['scraped_time_text'] ) ) {
												echo '<p>' . esc_html( $result['cartelera']['scraped_time_text'] ) . '</p>';
											}
											?>
										</td>
										<td> <!-- Display the cartelera dates parsed -->
											<?php
											// parse dates to get specific calendar dates.
											?>
										</td>
										<td> <!-- Display the coincidence check -->
											<?php
											// execute a function to compare both values.
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
