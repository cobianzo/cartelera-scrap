<?php
/**
 * Registers the custom post type "Report" for the frontend.
 *
 * @package Cartelera_Scrap
 * @subpackage Front
 */

namespace Cartelera_Scrap\Front;
use \Cartelera_Scrap\Front\Block_Registration;
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
				array( Block_Registration::BLOCK_NAME, array('content' => 'probando a decir HOLA', 'locked' => true))
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
}

Report_CPT::init();
