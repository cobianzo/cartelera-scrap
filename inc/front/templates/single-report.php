<?php
/**
 * Template Name: Plantilla Cartelera Report
 * Template Post Type: cartelera-report
 */

// Fuerza el modo bloque para el editor
add_filter('use_block_editor_for_post', '__return_true');

get_header(); ?>

<div class="wrap">
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <!-- Contenido bloqueado -->
            <div class="entry-content">
                <?php
                // Esto mostrará "HOLA" y no será editable
                echo '<h1>HOLA</h1>';

                // Opcional: Si quieres usar un bloque personalizado
                echo do_blocks('<!-- wp:my-custom-block /-->');
                ?>
            </div>
        </main>
    </div>
</div>

<?php get_footer(); ?>
