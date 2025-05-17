<?php

use Cartelera_Scrap\Scrap_Output;
use Cartelera_Scrap\Parse_Text_Into_Dates;

$success = Parse_Text_Into_Dates::computed_for_today_is_comparison_successful( $result );

// Preparar las clases CSS
$classes = [ 'cs_result' ];


// Añadir clase por número de fechas de ticketmaster

$classes[] = 'cs_tm_dates_' . count( $result['ticketmaster']['dates'] );


// Añadir clase de éxito/fallo
if ( ! empty( $result['ticketmaster']['dates'] ) ) {
	$classes[] = $success ? 'cs_success' : 'cs_fail';
}

?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<div class="cs_row cs_title_row">
			<h3><?php echo esc_html( $result['title'] ); ?></h3>
			<a href="<?php echo esc_url( $result['cartelera']['url'] ); ?>" class="cs_cartelera_link" target="_blank">
				Ver en Cartelera
			</a>

			<?php if ( empty( $result['ticketmaster']['dates'] ) ) : ?>
				<div class="cs_danger">No existe en Ticketmaster</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $result['ticketmaster']['dates'] ) ) : ?>
			<div class="cs_row cs_dates_row">
				<div class="cs_col cs_cartelera_col">
					<h4>Cartelera</h4>
					<div><?php echo esc_html( $result['cartelera']['scraped_dates_text'] ); ?></div>
					<div><?php echo esc_html( $result['cartelera']['scraped_time_text'] ); ?></div>
				</div>
				<div class="cs_col cs_ticketmaster_col">
					<h4>Ticketmaster</h4>
					<div>Número de fechas: <?php echo count( $result['ticketmaster']['dates'] ); ?></div>

					<?php if ( ! empty( $result['ticketmaster']['tm_title'] ) ) : ?>
						<div class="cs_tm_title"><?php echo esc_html( $result['ticketmaster']['tm_title'] ); ?></div>
					<?php endif; ?>

					<?php if ( ! empty( $result['ticketmaster']['tm_titles_list'] ) ) : ?>
						<?php foreach ( $result['ticketmaster']['tm_titles_list'] as $alt_title ) : ?>
							<div class="cs_muted"><?php echo esc_html( $alt_title ); ?></div>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php if ( ! empty( $result['ticketmaster']['dates'] ) ) : ?>
						<a href="<?php echo esc_url( $result['ticketmaster']['url'] ); ?>" class="cs_tm_link" target="_blank">
							Ver en Ticketmaster
						</a>
					<?php endif; ?>
				</div>
			</div>
			<div class="cs_row cs_dates_detail_row">
				<div class="cs_col cs_cartelera_col">
					<?php Scrap_Output::render_col_cartelera_datetimes( $result ); ?>
				</div>
				<div class="cs_col cs_ticketmaster_col">
					<?php Scrap_Output::render_col_ticketmaster_dates( $result ); ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
