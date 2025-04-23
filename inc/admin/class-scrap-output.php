<?php

class Scrap_Output {
	public static function init() {
		add_action( 'admin_init', function () {
			if ( isset( $_GET['error'] ) ) {
				$error_msg = ( 'nonce' === $_GET['error'] ) ? 'Error: Nonce verification failed.' : sanitize_text_field( $_GET['error'] );
				add_settings_error( 'scrap_output', 'scrap_output_error', $error_msg, 'error' );
			}
		} );
	}

	public static function render_scrap_status() {
		print_r( $_POST );
		?>
		<form method="post">
			<?php wp_nonce_field( 'mi_accion_custom', 'mi_accion_nonce' ); ?>
			<input type="hidden" name="mi_accion_trigger" value="1">
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
