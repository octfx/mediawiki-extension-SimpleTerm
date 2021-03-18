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

use MediaWiki\MediaWikiServices;

class ExtensionHooks {
	/**
	 * Extension Hooks
	 *
	 * Gets called in a callback after extension setup
	 */
	public static function register():void {
		/**
		 * Disable SimpleTerms for mathjax equations
		 */
		MediaWikiServices::getInstance()->getHookContainer()->register( 'SimpleMathJaxAttributes', function ( array &$attributes ) {
			$attributes['class'] = sprintf( '%s%s', ( $attributes['class'] ?? '' ), ' noglossary' );
		} );
	}
}
