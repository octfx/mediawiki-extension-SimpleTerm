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

use ErrorException;
use MediaWiki\Extension\SimpleTerms\DefinitionList;
use MediaWiki\Extension\SimpleTerms\SimpleTermsParser;

/**
 * Basic Backend for On Wiki Glossary
 */
class BasicBackend extends Backend {

	/**
	 * @return DefinitionList
	 * @throws ErrorException
	 */
	public function getDefinitionList(): DefinitionList {
		$parser = new SimpleTermsParser();
		$listData = $parser->parse();

		if ( $listData === false ) {
			throw new ErrorException( 'List Data is empty' );
		}

		$list = new DefinitionList();

		$list->unserialize( $listData );

		return $list;
	}
}
