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
	 * Initialize the class and register hooks.
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_blocks' ] );
	}

	/**
	 * Register custom blocks.
	 */
	public static function register_blocks(): void {
		register_block_type(
			plugin_dir_path( __FILE__ ) . 'blocks/dynamic-report.php'
		);
	}
}
