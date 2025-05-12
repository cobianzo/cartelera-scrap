<?php

namespace Cartelera_Scrap\Front;

/**
 * Class Block_Registration
 * Handles the registration of custom blocks.
 *
 * @package Cartelera_Scrap\Front\Blocks
 */
class Block_Registration {

	/**
	 * The name of the custom block.
	 */
	const BLOCK_NAME = 'cartelera-scrap/report';

	/**
	 * Initialize the class and register hooks.
	 */
	public static function init(): void {
		// registration in php.
		add_action( 'init', [ __CLASS__, 'register_blocks' ] );
		// registration in js and css..
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue_cartelera_block_editor_assets' ] );
	}

	/**
	 * Register custom blocks.
	 */
	public static function register_blocks(): void {
		register_block_type( self::BLOCK_NAME, [
			'api_version'     => 2,
			'title'           => 'Report Cartelera',
			'description'     => 'Block that shows the report of comparison between Ticketmaster and Cartelera',
			'category'        => 'widgets',
			'icon'            => 'media-document',
			'attributes'      => [
					'post_report_ID' => [
						'type'    => 'number',
						'default' => 0
					]
				],
			],

			'render_callback' => [ __CLASS__, 'render_callback' ],
			'editor_script'   => 'cartelera-report-block-script', // Opcional si quieres añadir JS más tarde
			'editor_style'    => 'cartelera-report-block-style', // Include CSS only for the editor

		] );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @since 1.0.0
	 */
	public static function enqueue_cartelera_block_editor_assets() {

		// Js.
		$block_js_relative_path = 'blocks/report.js';
		wp_register_script(
			'cartelera-report-block-script',
			plugins_url( $block_js_relative_path, __FILE__ ),
			[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ],
			filemtime( plugin_dir_path( __FILE__ ) . $block_js_relative_path )
		);

		// CSS.
		$block_css_relative_path = 'blocks/report.css';
		wp_register_style(
			'cartelera-report-block-style',
			plugins_url( $block_css_relative_path, __FILE__ ),
			[],
			filemtime( plugin_dir_path( __FILE__ ) . $block_css_relative_path )
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content Block content.
	 * @param array  $block Block data.
	 * @return string Rendered block.
	 */
	public static function render_callback( $attributes, $content, $block ): string {


		$template_path = plugin_dir_path( __FILE__ ) . 'blocks/report.php';
		if ( file_exists( $template_path ) ) {
			ob_start();
			// extract($template_data);
			$wrapper_attributes = get_block_wrapper_attributes();
			?>
			<div <?php echo $wrapper_attributes; ?>>
				<?php
				include $template_path;
				?>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}

Block_Registration::init();
