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

use CommentStoreComment;
use MediaWiki\Extension\SimpleTerms\SimpleTerms;
use MediaWiki\Extension\SimpleTerms\SimpleTermsParser;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Page\Hook\ArticlePurgeHook;
use MediaWiki\Revision\RenderedRevision;
use MediaWiki\Storage\Hook\MultiContentSaveHook;
use MediaWiki\User\UserIdentity;
use OutputPage;
use ParserOptions;
use Status;
use Title;
use WikiPage;

/**
 * Hooks to run relating the page
 */
class PageHooks implements OutputPageBeforeHTMLHook, ArticlePurgeHook, MultiContentSaveHook {

	/**
	 * Replace the terms in the page with tooltips
	 *
	 * @param OutputPage $out
	 * @param string &$text
	 */
	public function onOutputPageBeforeHTML( $out, &$text ): void {
		if ( SimpleTerms::getConfigValue( 'SimpleTermsRunOnPageView', false ) === false &&
			SimpleTerms::getConfigValue( 'SimpleTermsWriteIntoParserOutput', false ) === false ) {
			return;
		}

		if ( !SimpleTerms::titleInSimpleTermsNamespace( $out->getTitle() ) ) {
			return;
		}

		// This is so hacky, but there seems to be no other way to detect the noglossary property?
		// Let's hope that the parser cache works...
		// TODO: Fixup
		$parserOutput = $out->getWikiPage()->getParserOutput( ParserOptions::newCanonical() );
		if ( $parserOutput === false || isset( $parserOutput->getProperties()['noglossary'] ) ) {
			return;
		}

		$replacements = 0;
		if ( strpos( $text, 'simple-terms-tooltip' ) === false ) {
			$simpleTerms = new SimpleTermsParser();
			$replacements = $simpleTerms->replaceHtml( $text );
		}

		if ( $replacements > 0 || strpos( $text, 'simple-terms-tooltip' ) !== false ) {
			$out->addModules( 'ext.simple-terms' );
		}
	}

	/**
	 * @param WikiPage $wikiPage
	 * @return void
	 */
	public function onArticlePurge( $wikiPage ): void {
		if ( $wikiPage->getTitle() === null || !$wikiPage->getTitle()->equals( Title::newFromText( SimpleTerms::getConfigValue( 'SimpleTermsPage' ) ) ) ) {
			return;
		}

		SimpleTerms::getBackend()->purgeGlossaryFromCache();
	}

	/**
	 * @param RenderedRevision $renderedRevision
	 * @param UserIdentity $user
	 * @param CommentStoreComment $summary
	 * @param int $flags
	 * @param Status $status
	 * @return bool|void
	 */
	public function onMultiContentSave( $renderedRevision, $user, $summary, $flags, $status ) {
		if ( $renderedRevision->getRevisionParserOutput() === null ) {
			return;
		}

		$title = Title::newFromText( $renderedRevision->getRevisionParserOutput()->getTitleText() );

		if ( $title === null || !$title->equals( Title::newFromText( SimpleTerms::getConfigValue( 'SimpleTermsPage' ) ) ) ) {
			return;
		}

		SimpleTerms::getBackend()->purgeGlossaryFromCache();
	}
}
