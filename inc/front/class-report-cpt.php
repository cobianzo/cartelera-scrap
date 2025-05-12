<?php
/**
 * Registers the custom post type "Report" for the frontend.
 *
 * @package Cartelera_Scrap
 * @subpackage Front
 */

namespace Cartelera_Scrap\Front;

/**
 * Class Report_CPT
 * Registers the custom post type "Report" for the frontend.
 */
class Report_CPT {

	/**
	 * Initialize the class.
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_report_cpt' ] );

		add_filter( 'template_include', [ __CLASS__, 'cartelera_report_template' ] );
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
			'query_var'          => true,
			'rewrite'            => [
				'slug'       => 'report',
				'with_front' => false,
			],
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
		];

		register_post_type( 'cartelera_report', $args );
	}

	/**
	 * Changes the template for a single report post type.
	 *
	 * @param string $template The path to the template file.
	 * @return string The path to the template file to use.
	 */
	public static function cartelera_report_template( $template ) {
		if ( is_singular( 'cartelera_report' ) ) {
				$custom_template = plugin_dir_path( __FILE__ ) . 'template-single-report.php';
			if ( file_exists( $custom_template ) ) {
					return $custom_template;
			}
		}
		return $template;
	}

	public static function is_fse_theme() {
    return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

}

Report_CPT::init();
