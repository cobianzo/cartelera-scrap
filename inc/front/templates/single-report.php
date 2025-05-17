<?php

if ( is_admin() ) {
	return;
}

use Cartelera_Scrap\Parse_Text_Into_Dates;
use Cartelera_Scrap\Scrap_Output;

// $res = \Cartelera_Scrap\Helpers\Results_To_Save::get_show_results();
// \Cartelera_Scrap\ddie($res);
// $json    = json_encode( $res, JSON_UNESCAPED_UNICODE );

// \Cartelera_Scrap\dd($json);

global $post;
$results = $post->post_content;
// \Cartelera_Scrap\ddie($results);
$results = json_decode( $results, true );

// 1. Evaluate all the results with all its info
$number_failed_results = 0;
foreach ( (array) $results as $i => $result ) :

	$result['computed']                 = empty( $result['computed'] ) ? [] : $result['computed'];
	$result['computed']['cartelera']    = Parse_Text_Into_Dates::computed_data_cartelera_result( $result );
	$result['computed']['ticketmaster'] = Parse_Text_Into_Dates::computed_data_ticketmaster_result( $result );
	$result['computed']['comparison']   = Parse_Text_Into_Dates::computed_dates_comparison_result( $result );

	// we update the values of the result based on today. ($result is passed by ref)
	$success         = Parse_Text_Into_Dates::computed_for_today_is_comparison_successful( $result );
	$no_tickermaster = ( empty( $result['ticketmaster']['dates'] ) || ! isset( $result['ticketmaster']['url'] ) );
	if ( ! $no_tickermaster ) {
		$number_failed_results = $number_failed_results + ( $success ? 0 : 1 ) ;
	}

	$results[ $i ] = $result;
endforeach;



// 1. retrieve the date of the post and show it.
$date = get_the_date( 'F j, Y' );
echo '<div class="sr-wrap">';
echo '<h1>Shows scrapped on ' . $date . '</h1>';
echo '<p>There are '. count( (array) $results ).' results.</p>';
if ( $number_failed_results > 0 ) {
	echo '<p>There are '. $number_failed_results. ' failed results.</p>';
}
echo '</div>';



$template_path = plugin_dir_path( __FILE__ ) . 'parts/partial-js.php';
include $template_path;

// 2. Loop through results again to display them
echo '<div id="cs_show-results">';
foreach ( (array) $results as $i => $result ) :
	// echo '<br/>';
	// echo $result['title'];
	$template_path = plugin_dir_path( __FILE__ ) . 'parts/partial-result.php';
	include $template_path;

endforeach;
echo '</div>';


// 1. Report about how many shows we have processed.



// TODELETE
// $r = ["Cenicienta, La Magia del Amor","La Cenicienta\" Balanz Danza"];
// $r = wp_json_encode( $r, JSON_UNESCAPED_UNICODE );
// $r = wp_slash( $json );
// // \Cartelera_Scrap\imhere( htmlentities( $r ) );
// // update this post with the results.
// $u = wp_update_post([
	// 'ID' => $post->ID,
	// 'post_content' => $r, // this is ["Cenicienta, La Magia del Amor","La Cenicienta\" Balanz Danza"]
// ]);

// $thepost = get_post( $u );
// $content = $thepost->post_content;
// $content = json_decode( $content, true );
// \Cartelera_Scrap\ddie($content);

// wp_die( $u );

// Scrap_Output::render_table_with_results( $results );


