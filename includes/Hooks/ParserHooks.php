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
use ExtensionDependencyError;
use MediaWiki\Content\Hook\ContentAlterParserOutputHook;
use MediaWiki\Extension\SimpleTerms\SimpleTerms;
use MediaWiki\Extension\SimpleTerms\SimpleTermsParser;
use MediaWiki\Hook\ParserFirstCallInitHook;
use Parser;
use ParserOutput;
use PPFrame;
use SectionProfiler;
use Title;

/**
 * Hooks to run relating to the parser
 */
class ParserHooks implements ParserFirstCallInitHook, ContentAlterParserOutputHook {

	/**
	 * Parses the SimpleTermsPage to cache
	 *
	 * @param Parser $parser
	 *
	 * @throws ExtensionDependencyError
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'noglossary', function ( $input, array $args, Parser $parser, PPFrame $frame ) {
			return sprintf(
				'<div class="noglossary">%s</div>',
				$parser->recursiveTagParse( $input, $frame )
			);
		} );

		$simpleTermsParser = new SimpleTermsParser();
		$simpleTermsParser->parse();
	}

	/**
	 * Replaces the text with definition tooltips
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 * @return bool|void
	 */
	public function onContentAlterParserOutput( $content, $title, $parserOutput ) {
		if ( SimpleTerms::getConfigValue( 'SimpleTermsWriteIntoOutput' ) === false ) {
			return;
		}

		if ( !in_array( $title->getNamespace(), SimpleTerms::getConfigValue( 'SimpleTermsNamespaces', [] ), true ) ) {
			return;
		}

		$profiler = new SectionProfiler();

		$profiler->scopedProfileIn( 'SimpleTerms' );
		$simpleTerms = new SimpleTerms();
		$simpleTerms->replaceText( $title, $parserOutput );

		wfDebug( 'SimpleTerms Call Time', 'all', $profiler->getFunctionStats() );
	}
}
