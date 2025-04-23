<?php

namespace Cartelera_Scrap;

class Scrap_Output {
	public static function init() {
		add_action( 'admin_init', function () {
			if ( isset( $_GET['error'] ) ) {
				$error_msg = sanitize_text_field( $_GET['error'] );
				add_settings_error( 'scrap_output', 'scrap_output_error', $error_msg, 'error' );
			}
		} );
	}

	public static function render_scrap_status() {
		print_r( $_POST ); // todelete

		// check if the cron job is running
		if ( wp_next_scheduled( Scrap_Actions::CRONJOB_NAME ) ) {
			echo '<p>Scrapping is running</p>';
		} else {
			echo '<p>Scrapping ' . Scrap_Actions::CRONJOB_NAME . ' is not running</p>';
		}
		?>
		<form method="post">
			<?php wp_nonce_field( 'nonce_action_field', 'nonce_action_scrapping' ); ?>
			<input type="hidden" name="start_scrapping_shows" value="1">
			<input type="submit" class="button button-primary" value="Ejecutar acción">
		</form>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			Nothing has been scrapped.
			<div id="scrap-output"></div>
		<?php
	}
}

Scrap_Output::init();
