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

use Config;
use MediaWiki\Extension\SimpleTerms\SimpleTerms;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use Skin;

/**
 * Hooks to run relating to the resource loader
 */
class ResourceLoaderHooks implements ResourceLoaderGetConfigVarsHook {

	/**
	 * ResourceLoaderGetConfigVars hook handler for setting a config variable
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 * @param array &$vars
	 * @param Skin $skin
	 * @param Config $config
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		if ( SimpleTerms::getConfigValue( 'SimpleTermsTippyConfig' ) !== null ) {
			$vars['wgSimpleTermsTippyConfig'] = SimpleTerms::getConfigValue( 'SimpleTermsTippyConfig' );
		}
		if ( SimpleTerms::getConfigValue( 'SimpleTermsAllowHtml' ) !== null ) {
			$vars['wgSimpleTermsAllowHtml'] = SimpleTerms::getConfigValue( 'SimpleTermsAllowHtml' );
		}
	}
}
