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

use MediaWiki\Extension\SimpleTerms\SimpleTerms;
use MediaWiki\Extension\SimpleTerms\SimpleTermsParser;
use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use OutputPage;

/**
 * Hooks to run relating the page
 */
class PageHooks implements OutputPageBeforeHTMLHook {

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

		$replacements = 0;
		if ( strpos( $text, 'simple-terms-tooltip' ) === false ) {
			$simpleTerms = new SimpleTermsParser();
			$replacements = $simpleTerms->replaceHtml( $text );
		}

		if ( $replacements > 0 || strpos( $text, 'simple-terms-tooltip' ) !== false ) {
			$out->addModules( 'ext.simple-terms' );
		}
	}
}
