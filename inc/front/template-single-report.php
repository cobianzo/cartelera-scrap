<?php

/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since Twenty Twenty 1.0
 */
?>

<?php
// Detectar si es un FSE Theme
$is_fse = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
?>

<?php if ( ! $is_fse ) : ?>
		<?php get_header(); ?>
<?php endif; ?>

<!-- FSE Bloques -->
<?php if ( $is_fse ) : ?>

		<!-- wp:template-part {"slug":"header","theme":"twentytwentyfive"} /-->


		<div class="cartelera-report-container">
				<!-- wp:post-title /-->
				<!-- wp:post-content /-->
		</div>

		<!-- wp:template-part {"slug": "footer"} /-->

<?php else : ?>

		<!-- Classic Theme Output -->
		<div class="cartelera-report-container">
				<h1><?php the_title(); ?></h1>
				<div class="content"><?php the_content(); ?></div>
		</div>

<?php endif; ?>

<?php if ( ! $is_fse ) : ?>
		<?php get_footer(); ?>
<?php endif; ?>
