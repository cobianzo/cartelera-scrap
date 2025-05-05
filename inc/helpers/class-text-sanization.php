<?php
/**
 * Helper to sanitize texts
 *
 * @package Cartelera_Scrap
 * @subpackage Helpers
 */

namespace Cartelera_Scrap\Helpers;

/**
 * Just a couple of functions which take too many lines
 * and make a very simple task. Basically helpers.
 */
class Text_Sanization {

		/**
		 * Remove accents and special characters from a string.
		 *
		 * @param string $titulo The text to clean up.
		 * @return string
		 */
	public static function remove_accents( string $titulo ): string {
		// Normalize characters with accents and the ñ.
		$titulo = strtr( $titulo, [
			'á' => 'a',
			'é' => 'e',
			'í' => 'i',
			'ó' => 'o',
			'ú' => 'u',
			'Á' => 'A',
			'É' => 'E',
			'Í' => 'I',
			'Ó' => 'O',
			'Ú' => 'U',
			'ñ' => 'n',
			'Ñ' => 'N',
		] );

		// Remove question marks, exclamation marks, and dashes.
		$titulo = preg_replace( '/[¡!¿?\-\—]/u', '', $titulo );

		// Also remove unnecessary spaces.
		$titulo = trim( preg_replace( '/\s+/', ' ', $titulo ) );

		return $titulo;
	}


	/**
	 * Sanitize text for scraping. $node->textContent contains weird chars.
	 *
	 * @param string $texto The text to sanitize.
	 * @return string The sanitized text.
	 */
	public static function sanitize_scraped_text( string $texto ): string {
		// Decode HTML entities like &nbsp;, &aacute;, etc.
		$texto        = str_replace( '&nbsp;', ' ', $texto ); // Just in case.
		$replacements = [
			'Ã¡'     => 'á',
			'Ã©'     => 'é',
			'Ã­'     => 'í',
			'Ã³'     => 'ó',
			'Ãº'     => 'ú',
			'Ã'     => 'Á',
			'Ã‰'     => 'É',
			'Ã'     => 'Í',
			'Ã“'     => 'Ó',
			'Ãš'     => 'Ú',
			'Ã±'     => 'ñ',
			'Ã‘'     => 'Ñ',
			'Â¡'     => '¡',
			'Â¿'     => '¿',
			'Â«'     => '«',
			'Â»'     => '»',
			'Â·'     => '·',
			'Â´'     => '´',
			'Â°'     => '°',
			'Â¬'     => '¬',
			'Â'      => '',  // Cases like Â¡ or Â¿.
			'â¦'    => '…',  // Ellipsis.
			'â'    => '–',  // En dash.
			'â'    => '—',  // Em dash.
			'â'    => '“',  // Left double quotation mark.
			'â'    => '”',  // Right double quotation mark.
			'â'    => '‘',  // Left single quotation mark.
			'â'    => '’',  // Right single quotation mark.
			'â¢'    => '•',  // Bullet.
			'â¨'    => '',  // Unicode line break.
			'â'     => '-',  // Dashes.
			'âª'    => '',  // LRM.
			'â«'    => '',  // LRE.
			'â¬'    => '',  // PDF.
			'â­'    => '',  // RLE.
			'â®'    => '',  // RLM.
			'&npsp;' => ' ', // Non-breaking space.
		];

		$texto = str_replace( array_keys( $replacements ), array_values( $replacements ), $texto );

		// Clean up multiple spaces and trim.
		return trim( $texto );
	}

	/**
	 * Basically trimming, for sentences with times in it.
	 *
	 * @param array $sentences Array of sentences to be cleaned.
	 * @return array The cleaned array of sentences.
	 */
	public static function cleanup_sentences( array $sentences ): array {
		// Sustitución de "hrs" por "horas"
		$sentences = array_map( function ( $sentence ) {

			$input_text = str_ireplace( [ 'hrs.', 'hrs', 'Hrs', 'HRS' ], 'horas', $sentence );

			// Limpiar espacios y comillas
			$input_text = trim( trim( $input_text ), '"' );

			return $input_text;
		}, $sentences );
		return $sentences;
	}
}
