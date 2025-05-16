<?php


if ( is_admin() ) {
	return;
}

use Cartelera_Scrap\Parse_Text_Into_Dates;
use Cartelera_Scrap\Scrap_Output;
use Symfony\Component\Console\Output\Output;

// $res = \Cartelera_Scrap\Helpers\Results_To_Save::get_show_results();
// \Cartelera_Scrap\ddie($res);
// $json    = json_encode( $res, JSON_UNESCAPED_UNICODE );

// \Cartelera_Scrap\dd($json);

global $post;
$results = $post->post_content;
// \Cartelera_Scrap\ddie($results);
$results = json_decode( $results, true );

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

Scrap_Output::render_table_with_results( $results );


