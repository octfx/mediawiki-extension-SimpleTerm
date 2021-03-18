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

use DOMDocument;
use DomNode;
use DOMXPath;
use Exception;
use ParserOutput;
use ValueError;

class SimpleTermsParser {

	/**
	 * Regex to replace terms
	 * This is _kind of_ hacky as we check ending spans in a negative lookahead to not replace terms found in other
	 * term definitions
	 *
	 * @var string
	 */
	private $regex = '/%s(?!.*?<\/span>)/';

	/**
	 * Replaces terms in the direct parser output
	 *
	 * @param ParserOutput $output
	 * @return int
	 */
	public function replaceText( ParserOutput $output ): int {
		if ( !$this->canReplace( $output ) ) {
			return 0;
		}

		$text = $output->getText();
		$replacementCount = $this->replaceHtml( $text );

		$output->setText( $text );

		return $replacementCount;
	}

	/**
	 * Replaces terms in the actual html
	 * This _can_ be resource intensive as the html is parsed into a Document and each text node is walked once
	 * Local tests have used ~8ms for a glossary with ~50 definitions
	 *
	 * @param string &$html
	 * @return int
	 */
	public function replaceHtml( string &$html ): int {
		if ( !$this->canReplace( $html ) ) {
			return 0;
		}

		$list = SimpleTerms::getBackend()->getDefinitionList();

		$doc = $this->parseXhtml( $html );

		$extraQuery = sprintf( ' %s', implode( ' ', array_map( static function ( $ignoredElement ) {
			return sprintf( 'or ancestor-or-self::%s', $ignoredElement );
		}, SimpleTerms::getConfigValue( 'SimpleTermsDisabledElements', [] ) ) ) );

		// Find all text in HTML.
		$xpath = new DOMXPath( $doc );
		$textElements = $xpath->query(
			sprintf(
				"//*[not(ancestor-or-self::*[@class='noglossary'] or ancestor-or-self::a or ancestor-or-self::script%s)][text()!=' ']/text()",
				$extraQuery
			)
		);

		$replacementCount = 0;

		$replacements = $list->getArrayForReplacement( $this->regex );

		foreach ( $textElements as /** @var DomNode|null */$textElement ) {
			if ( $textElement === null ) {
				continue;
			}

			if ( strlen( $textElement->nodeValue ) < $list->getMinTermLength() ) {
				continue;
			}

			$text = $textElement->nodeValue;

			$replaced = $this->doTextReplace( $text, $replacements );

			$tooltipHtml = $doc->createDocumentFragment();
			$tooltipHtml->appendXML( $text );

			$textElement->parentNode->replaceChild(
				$tooltipHtml,
				$textElement
			);

			$replacementCount += $replaced;
		}

		if ( $replacementCount > 0 ) {
			// U - Ungreedy, D - dollar matches only end of string, s - dot matches newlines
			$html = preg_replace( '%(^.*<body>)|(</body>.*$)%UDs', '', $doc->saveHTML() );
		}

		return $replacementCount;
	}

	/**
	 * Replaces the actual terms with html definitions
	 *
	 * @param string &$text The text do run the replacements on
	 * @param array &$replacements The replacement array as returned by DefinitionList::getArrayForReplacement
	 * @return int Number of replaced terms
	 */
	private function doTextReplace( string &$text, array &$replacements ): int {
		$displayOnce = SimpleTerms::getConfigValue( 'SimpleTermsDisplayOnce' );
		$limit = SimpleTerms::getConfigValue( 'SimpleTermsDisplayOnce', false ) === false ? -1 : 1;

		$replacementCount = 0;

		foreach ( $replacements as $replacement ) {
			$text = preg_replace(
				$replacement[0],
				$replacement[1],
				$text,
				$limit,
				$replaced
			);

			if ( $replaced > 0 && $displayOnce ) {
				$replacements = array_filter( $replacements, static function ( $entry ) use ( &$replacement ) {
					return $entry[0] !== $replacement[0];
				} );
			}

			$replacementCount += $replaced;

			if ( empty( $replacements ) ) {
				break;
			}
		}

		return $replacementCount;
	}

	/**
	 * A simple sanity check if replacements can be run
	 *
	 * @param ParserOutput|string $in
	 * @return bool
	 */
	private function canReplace( $in ): bool {
		if ( SimpleTerms::getConfigValue( 'SimpleTermsPage' ) === null ) {
			return false;
		}

		if ( $in instanceof ParserOutput ) {
			$text = $in->getRawText();
		} elseif ( is_string( $in ) ) {
			$text = $in;
		} else {
			return false;
		}

		return $text !== null && $text !== '';
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
}
