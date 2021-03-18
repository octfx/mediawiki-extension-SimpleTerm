<?php

declare( strict_types=1 );

/**
 * File holding the SimpleTerms\Backend class
 *
 * This file is part of the MediaWiki extension SimpleTerms.
 *
 * @copyright 2011 - 2018, Stephan Gambke
 * @license GPL-2.0-or-later
 *
 * The SimpleTerms extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * The SimpleTerms extension is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup SimpleTerms
 */

namespace MediaWiki\Extension\SimpleTerms\Backend;

use MediaWiki\Extension\SimpleTerms\DefinitionList;

/**
 * The Base Backend class.
 *
 * @ingroup SimpleTerms
 */
abstract class Backend {

	/**
	 * Returns the definition list filled with terms and definitions
	 *
	 * @return DefinitionList
	 */
	abstract public function getDefinitionList(): DefinitionList;

	/**
	 * The Backend instance
	 *
	 * @return mixed
	 */
	abstract public static function getInstance();

	/**
	 * Purge the glossary from cache
	 *
	 * @return mixed
	 */
	abstract public function purgeGlossaryFromCache();

	/**
	 * A flag which determines if the serialized definition list is written to cache
	 *
	 * @return bool
	 */
	public function useCache(): bool {
		return true;
	}
}
