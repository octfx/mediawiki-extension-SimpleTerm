<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

declare( strict_types=1 );

namespace MediaWiki\Extension\SimpleTerms;

use ConfigException;
use DOMDocument;
use DOMXPath;
use Exception;
use MediaWiki\Extension\SimpleTerms\Backend\Backend;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Title;

class SimpleTerms {

	private $format = '<span class="simple-terms-tooltip" role="tooltip" data-tippy-content="%s">%s</span>';
	private $regex = '/(\s?)(?<!role="tooltip">)%s(?!\<\/span\>)(\ )?/';

	/**
	 * Text so save in Cache
	 *
	 * @param Title $title
	 * @param ParserOutput $output
	 * @return int
	 */
	public function replaceText( Title $title, ParserOutput $output ): int {
		if ( !in_array( $title->getNamespace(), self::getConfigValue( 'SimpleTermsNamespaces', [] ), true ) ) {
			return 0;
		}

		try {
			$backend = $this->getBackend();
			$list = $backend->getDefinitionList();
		} catch ( \ErrorException $e ) {
			wfLogWarning( 'Could not load DefinitionList.' );

			return 0;
		}

		$terms = $list->getTerms();
		$text = $output->getText();
		$replacements = 0;

		foreach ( $terms as $term ) {
			$text = preg_replace(
				sprintf( $this->regex, preg_quote( $term, '\\' ) ),
				sprintf( $this->format, strip_tags( $list[$term] ?? '' ), $term ),
				$text,
				self::getConfigValue( 'SimpleTermsDisplayOnce', false ) === false ? -1 : 1,
				$replacements
			);
		}

		$output->setText( $text );

		return $replacements;
	}

	/**
	 * Parses the given text and enriches applicable terms
	 *
	 * @param string &$html
	 * @return int
	 */
	public function replaceHtml( string &$html ): int {
		if ( $html === null || $html === '' || SimpleTerms::getConfigValue('SimpleTermsPage') === null) {
			return 0;
		}

		try {
			$backend = $this->getBackend();
			$list = $backend->getDefinitionList();
		} catch ( \ErrorException $e ) {
			return 0;
		}

		$doc = $this->parseXhtml( $html );

		// Find all text in HTML.
		$xpath = new DOMXPath( $doc );
		$textElements = $xpath->query(
			"//*[not(ancestor-or-self::*[@class='noglossary'] or ancestor-or-self::a)][text()!=' ']/text()"
		);

		$definitions = 0;

		$repls = [];

		foreach ( $list->getTerms() as $term ) {
			$repls[] = [
				sprintf( '/%s(?!.*?<\/span>)/', $term ),
				sprintf( $this->format, strip_tags( $list[$term] ?? '', 'sup' ), $term )
			];
		}

		$displayOnce = SimpleTerms::getConfigValue('SimpleTermsDisplayOnce');

		foreach ( $textElements as /** @var \DomNode|null */$textElement ) {
			if ( $textElement === null ) {
				continue;
			}

			if ( strlen( $textElement->nodeValue ) < $list->getMinTermLength() ) {
				continue;
			}

			$text = $textElement->nodeValue;

			foreach ( $repls as $repl ) {
				$text = preg_replace( $repl[0], $repl[1], $text, -1, $replaced );
				if ($replaced > 0 && $displayOnce) {
				    $repls = array_filter($repl, static function ($entry) use ($repl) {
				        return $entry[0] !== $repl[0];
                    });
                }
			}

			$tooltipHtml = $doc->createDocumentFragment();
			$tooltipHtml->appendXML( $text );

			$textElement->parentNode->replaceChild(
				$tooltipHtml,
				$textElement
			);

			++$definitions;
		}

		if ( $definitions > 0 ) {
			// U - Ungreedy, D - dollar matches only end of string, s - dot matches newlines
			$html = preg_replace( '%(^.*<body>)|(</body>.*$)%UDs', '', $doc->saveHTML() );
		}

		return $definitions;
	}

	/**
	 * Instantiates the configured backend
	 *
	 * @return Backend
	 */
	private function getBackend(): Backend {
		$classPath = '\\MediaWiki\\Extension\\SimpleTerms\\Backend\\%s';
		$backendClass = self::getConfigValue( 'SimpleTermsBackend' );

		try {
			$class = new ReflectionClass( sprintf( $classPath, $backendClass ) );
			/** @var Backend $backend */
			$backend = $class->newInstance();
		} catch ( ReflectionException $e ) {
			throw new RuntimeException( sprintf( '"%s" is not a valid backend.', $e->getMessage() ) );
		}

		return $backend;
	}

	/**
	 * Parses a html string into a DOMDocument
	 *
	 * @param string $htmlContent
	 * @param string $charset
	 *
	 * @return DOMDocument
	 */
	private function parseXhtml( string $htmlContent, string $charset = 'UTF-8' ): DOMDocument {
		$htmlContent = $this->convertToHtmlEntities( $htmlContent, $charset );

		$internalErrors = libxml_use_internal_errors( true );
		$disableEntities = libxml_disable_entity_loader( true );

		$dom = new DOMDocument( '1.0', $charset );
		$dom->validateOnParse = true;

		if ( trim( $htmlContent ) !== '' ) {
            // @phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			@$dom->loadHTML( $htmlContent );
		}

		libxml_use_internal_errors( $internalErrors );
		libxml_disable_entity_loader( $disableEntities );

		return $dom;
	}

	/**
	 * Converts charset to HTML-entities to ensure valid parsing.
	 *
	 * @param string $htmlContent
	 * @param string $charset
	 * @return string
	 */
	private function convertToHtmlEntities( string $htmlContent, string $charset = 'UTF-8' ): string {
		set_error_handler( static function () {
			// Null
		} );

		try {
			return mb_convert_encoding( $htmlContent, 'HTML-ENTITIES', $charset );
		} catch ( Exception | ValueError $e ) {
			try {
				$htmlContent = iconv( $charset, 'UTF-8', $htmlContent );
				$htmlContent = mb_convert_encoding( $htmlContent, 'HTML-ENTITIES', 'UTF-8' );
			} catch ( Exception | ValueError $e ) {
				//
			}

			return $htmlContent;
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * Loads a config value for a given key from the main config
	 * Returns null on if an ConfigException was thrown
	 *
	 * @param string $key The config key
	 * @param null $default
	 * @return mixed|null
	 */
	public static function getConfigValue( string $key, $default = null ) {
		try {
			$value = MediaWikiServices::getInstance()->getMainConfig()->get( $key );
		} catch ( ConfigException $e ) {
			wfLogWarning(
				sprintf(
					'Could not get config for "$wg%s". %s', $key,
					$e->getMessage()
				)
			);

			return $default;
		}

		return $value;
	}
}
