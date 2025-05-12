<?php

use Cartelera_Scrap\Helpers\Results_To_Save;

global $post;


$r = Results_To_Save::get_show_results();
$results = json_decode( $post->post_content );

// print_r( $post->post_content );
// foreach ( (array) $results as $key => $result ) {
foreach ( (array) $r as $key => $result ) {
	\Cartelera_Scrap\dd( $result );
}
print_r( $results );

?>

Aqui el content;
