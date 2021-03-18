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

namespace MediaWiki\Extension\SimpleTerms\Hooks;

use Content;
use MediaWiki\Content\Hook\ContentAlterParserOutputHook;
use MediaWiki\Extension\SimpleTerms\SimpleTerms;
use MediaWiki\Extension\SimpleTerms\SimpleTermsParser;
use MediaWiki\Hook\GetDoubleUnderscoreIDsHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MWException;
use Parser;
use ParserOutput;
use PPFrame;
use SectionProfiler;
use Title;

/**
 * Hooks to run relating to the parser
 */
class ParserHooks implements ParserFirstCallInitHook, ContentAlterParserOutputHook, GetDoubleUnderscoreIDsHook {

	/**
	 * Parses the SimpleTermsPage to cache
	 *
	 * @param Parser $parser
	 *
	 * @throws MWException
	 */
	public function onParserFirstCallInit( $parser ): void {
		$parser->setHook( 'noglossary', function ( $input, array $args, Parser $parser, PPFrame $frame ) {
			return sprintf(
				'<div class="noglossary">%s</div>',
				$parser->recursiveTagParse( $input, $frame )
			);
		} );

		// Warm the cache
		SimpleTerms::getBackend()->getDefinitionList();
	}

	/**
	 * Replaces the text with definition tooltips
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 * @return void
	 */
	public function onContentAlterParserOutput( $content, $title, $parserOutput ): void {
		if ( SimpleTerms::getConfigValue( 'SimpleTermsWriteIntoParserOutput', false ) === false ) {
			return;
		}

		if ( !SimpleTerms::titleInSimpleTermsNamespace( $title ) || isset( $parserOutput->getProperties()['noglossary'] ) ) {
			return;
		}

		$profiler = new SectionProfiler();

		$profiler->scopedProfileIn( 'SimpleTerms' );
		$simpleTerms = new SimpleTermsParser();
		$simpleTerms->replaceText( $parserOutput );

		wfDebug( 'SimpleTerms Call Time', 'all', $profiler->getFunctionStats() );
	}

	/**
	 * @param string[] &$doubleUnderscoreIDs
	 * @return void
	 */
	public function onGetDoubleUnderscoreIDs( &$doubleUnderscoreIDs ): void {
		$doubleUnderscoreIDs[] = 'noglossary';
	}
}
