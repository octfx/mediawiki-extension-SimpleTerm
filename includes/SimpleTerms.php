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
use MediaWiki\Extension\SimpleTerms\Backend\Backend;
use MediaWiki\MediaWikiServices;
use Title;

class SimpleTerms {

	/**
	 * Check that a title belongs to a simple term activated namespace
	 *
	 * @param Title|null $title
	 * @return bool
	 */
	public static function titleInSimpleTermsNamespace( ?Title $title ): bool {
		return $title !== null && in_array( $title->getNamespace(), self::getConfigValue( 'SimpleTermsNamespaces', [] ), true );
	}

	/**
	 * Returns an instance of the configured backend
	 *
	 * @return Backend
	 *
	 * @throws ConfigException
	 */
	public static function getBackend(): Backend {
		$classPath = '\\MediaWiki\\Extension\\SimpleTerms\\Backend\\%s';
		$backendClass = sprintf( $classPath, self::getConfigValue( 'SimpleTermsBackend' ) );

		if ( !class_exists( $backendClass ) ) {
			throw new ConfigException( sprintf( '"%s" is not a valid backend.', $backendClass ) );
		}

		/** @var Backend $backend */
		return $backendClass::getInstance();
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
