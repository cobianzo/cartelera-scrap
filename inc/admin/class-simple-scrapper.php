<?php
/**
 * Static methods for easier scrapping
 *
 * @package Cartelera_Scrap
 */

namespace Cartelera_Scrap;

use DOMDocument;
use DOMXPath;
use DOMElement;

/**
 * Class Simple_Scrapper
 *
 * A simple scrapper class fr easy access to DOM elements and values.
 * Usage $scraper = new Simple_Scrapper($html);
 * $titles = $scraper->getTexts('//h2[@class="post-title"]');
 */
class Simple_Scrapper {

	protected DOMXPath $xpath;

	/**
	 * Constructor.
	 *
	 * @param string $html HTML content to parse.
	 */
	public function __construct( string $html ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html );
		libxml_clear_errors();
		$this->xpath = new DOMXPath( $dom );
	}

	public function get_root(): DOMXPath {
		return $this->xpath;
	}

	/**
	 * Get text content from nodes matching the XPath query.
	 *
	 * @param string $query XPath query.
	 * @return array<string> List of text content.
	 */
	public function getTexts( string $query ): array {
		$results = [];
		$nodes   = $this->xpath->query( $query );
		foreach ( $nodes as $node ) {
			$results[] = trim( $node->nodeValue );
		}
		return $results;
	}

	public static function simplificar_titulo( string $titulo ): string {
		// Normalizar caracteres con tilde y la ñ
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

		// Eliminar signos de interrogación, exclamación y guiones
		$titulo = preg_replace( '/[¡!¿?\-\—]/u', '', $titulo );

		// También eliminar espacios innecesarios
		$titulo = trim( preg_replace( '/\s+/', ' ', $titulo ) );

		return $titulo;
	}

	public static function sanear_texto_scrap( string $texto ): string {
		// 1. Primero decodifica entidades HTML como &nbsp;, &aacute;, etc.
		// $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$texto        = str_replace( '&nbsp;', ' ', $texto ); // por si acaso
		$replacements = [
			'Ã¡'  => 'á',
			'Ã©'  => 'é',
			'Ã­'  => 'í',
			'Ã³'  => 'ó',
			'Ãº'  => 'ú',
			'Ã'  => 'Á',
			'Ã‰'  => 'É',
			'Ã'  => 'Í',
			'Ã“'  => 'Ó',
			'Ãš'  => 'Ú',
			'Ã±'  => 'ñ',
			'Ã‘'  => 'Ñ',
			'Â¡'  => '¡',
			'Â¿'  => '¿',
			'Â«'  => '«',
			'Â»'  => '»',
			'Â·'  => '·',
			'Â´'  => '´',
			'Â°'  => '°',
			'Â¬'  => '¬',
			'Â'   => '',  // casos como Â¡ o Â¿
			'â¦' => '…',  // puntos suspensivos
			'â' => '–',  // guion largo
			'â' => '—',  // raya
			'â' => '“',  // comillas dobles izquierda
			'â' => '”',  // comillas dobles derecha
			'â' => '‘',  // comilla simple izquierda
			'â' => '’',  // comilla simple derecha
			'â¢' => '•',  // viñeta
			'â¢' => '•',
			'â¨' => '',  // salto de línea Unicode
			'â'  => '-',  // guiones
			'âª' => '',  // LRM
			'â«' => '',  // LRE
			'â¬' => '',  // PDF
			'â­' => '',  // RLE
			'â®' => '',  // RLM
		];

		$texto = str_replace( array_keys( $replacements ), array_values( $replacements ), $texto );

		// 4. Limpia espacios múltiples y trima
		// $texto = preg_replace('/\s+/', ' ', $texto);
		return trim( $texto );
	}
	public function getTextsAndHrefs( string $query ): array {
		$results = [];
		$nodes   = $this->xpath->query( $query );
		foreach ( $nodes as $node ) {
			$text = self::sanear_texto_scrap( $node->nodeValue );
			$text = self::simplificar_titulo( $text );

			$href      = $node instanceof DOMElement ? $node->getAttribute( 'href' ) : '';
			$results[] = [
				'text' => $text,
				'href' => $href,
			];
		}
		return $results;
	}

	/**
	 * Get attributes from nodes matching the XPath query.
	 *
	 * @param string $query XPath query.
	 * @param string $attr Attribute name.
	 * @return array<string> List of attribute values.
	 */
	public function getAttributes( string $query, string $attr ): array {
		$results = [];
		$nodes   = $this->xpath->query( $query );
		foreach ( $nodes as $node ) {
			if ( $node instanceof DOMElement ) {
				$results[] = $node->getAttribute( $attr );
			}
		}
		return $results;
	}
}
