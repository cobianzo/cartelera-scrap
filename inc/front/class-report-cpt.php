<?php
/**
 * Registers the custom post type "Report" for the frontend.
 *
 * @package Cartelera_Scrap
 * @subpackage Front
 */

namespace Cartelera_Scrap\Front;
// use \Cartelera_Scrap\Front\Block_Registration;

use Cartelera_Scrap\Cartelera_Scrap_Plugin;
use \Cartelera_Scrap\Helpers\Results_To_Save;
/**
 * Class Report_CPT
 * Registers the custom post type "Report" for the frontend.
 */
class Report_CPT {

	const POST_TYPE = 'cartelera_report';

	/**
	 * Initialize the class.
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_report_cpt' ] );
		add_action( 'cartelera_scrap_all_shows_processed', [ __CLASS__, 'save_results_as_post' ] );

		add_filter( 'the_content', [ __CLASS__, 'filter_report_content' ] );
	}

	/**
	 * Register the custom post type.
	 */
	public static function register_report_cpt(): void {
		$labels = [
			'name'               => __( 'Report', 'cartelera-scrap' ),
			'singular_name'      => __( 'Report', 'cartelera-scrap' ),
			'add_new'            => __( 'Add New', 'cartelera-scrap' ),
			'add_new_item'       => __( 'Add New Report', 'cartelera-scrap' ),
			'edit_item'          => __( 'Edit Report', 'cartelera-scrap' ),
			'new_item'           => __( 'New Report', 'cartelera-scrap' ),
			'view_item'          => __( 'View Report', 'cartelera-scrap' ),
			'search_items'       => __( 'Search Reports', 'cartelera-scrap' ),
			'not_found'          => __( 'No reports found', 'cartelera-scrap' ),
			'not_found_in_trash' => __( 'No reports found in Trash', 'cartelera-scrap' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'template_lock'      => 'all',
			'menu_icon'          => 'dashicons-analytics',
			// important, we set the content of the single.php with a single block.
			'template'           => [
				// array( Block_Registration::BLOCK_NAME, array('content' => 'probando a decir HOLA', 'locked' => true))
			],
			'query_var'          => true,
			'rewrite'            => [
				'slug'       => 'report',
				'with_front' => false,
			],
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => [ 'title', 'editor' ],
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Determines if the current theme is a Full Site Editing (FSE) theme.
	 *
	 * @return bool True if the current theme is an FSE theme, false otherwise.
	 */

	public static function is_fse_theme() {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}


	/**
	 * Takes the data from the results options, and saves it as json string
	 * as the content of a post of type cartelera-report.
	 *
	 * @return integer	Post ID of the 'cartelera-result' already created.
	 */
	public static function save_results_as_post(): int {

		// retrieve all results
		$results = Results_To_Save::get_show_results();
		$json    = json_encode( $results, JSON_UNESCAPED_UNICODE );

		$post_title = 'Cartelera Scrap Report ' . date( 'Y-m-d H:i' );
		// confirm that there is not a post with the title $post_title
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_title'     => $post_title,
			'post_status'    => 'any',
			'posts_per_page' => 1,
		);
		$query = new \WP_Query( $args );

		$existing_post = count( $query->posts ) > 0;
		if ( $existing_post ) {
			return 0;
		}

		// create the post
		$post = array(
			'post_title'   => $post_title,
			'post_content' => $json,
			'post_status'  => 'publish',
			'post_type'    => self::POST_TYPE,
		);

		$post_id = wp_insert_post( $post );

		return $post_id;
	}


	/**
	 * Filters the content of the Report CPT to use a template.
	 *
	 * @param string $content The content of the post.
	 * @return string The filtered content.
	 */
	public static function filter_report_content( $content ) {

		// only for the report CPT
		if ( self::POST_TYPE !== get_post_type() ) {
			return $content;
		}

		// only if is a single post
		if ( ! is_singular( self::POST_TYPE ) ) {
			return $content;
		}

		// Use a template for the content
		ob_start();
		include plugin_dir_path( __FILE__ ) . 'templates/single-report.php';
		return ob_get_clean();
	}
}

Report_CPT::init();
