( function( blocks, i18n, element ) {
    var el = element.createElement;
    var __ = i18n.__;

    blocks.registerBlockType('cartelera-scrap/report', {
        title: __('Cartelera/Ticketmaster report', 'cartelera-scrap'),
        icon: 'media-document',
        category: 'widgets',

        edit: function() {
            return el(
                'div',
                { className: 'cartelera-report-block-editor' },
                __('Contenido del Reporte - Vista previa en el frontend', 'cartelera-scrap')
            );
        },

        save: function() {
            return null; // Bloque dinámico, no guarda HTML
        }
    });
} )( window.wp.blocks, window.wp.i18n, window.wp.element );
