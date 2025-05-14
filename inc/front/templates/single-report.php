<?php

use Cartelera_Scrap\Parse_Text_Into_Dates;
use Cartelera_Scrap\Scrap_Output;

global $post;
$results = json_decode( $post->post_content, true );

// TODELETE
// Cartelera_Scrap\ddie($post->post_content);

// Cartelera_Scrap\ddie($results);
foreach ( $results as $i => $result ) :

	$result['computed']                 = empty( $result['computed'] ) ? [] : $result['computed'];
	$result['computed']['cartelera']    = Parse_Text_Into_Dates::computed_data_cartelera_result( $result );
	$result['computed']['ticketmaster'] = Parse_Text_Into_Dates::computed_data_ticketmaster_result( $result );
	$result['computed']['comparison']   = Parse_Text_Into_Dates::computed_dates_comparison_result( $result );

	// we update the values of the result based on today. ($result is passed by ref)
	$is_result_successful = Parse_Text_Into_Dates::computed_for_today_is_comparison_successful( $result );
	$no_tickermaster = ( empty( $result['ticketmaster']['dates'] ) || ! isset( $result['ticketmaster']['url'] ) );

	?>

	<div class="result">
		<?php Scrap_Output::render_col_title( $result ); ?>
		<?php Scrap_Output::render_col_cartelera_text_datetimes( $result ); ?>
		<?php Scrap_Output::render_col_ticketmaster_datetimes( $result ); ?>
		<?php Scrap_Output::render_col_cartelera_datetimes( $result ); ?>
		<?php Scrap_Output::render_col_comparison( $result ); ?>
	</div>


<?php



endforeach;
?>


