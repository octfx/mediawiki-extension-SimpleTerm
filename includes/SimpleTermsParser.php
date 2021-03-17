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

use ApprovedRevs;
use BagOStuff;
use ExtensionDependencyError;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use ObjectCache;
use TextContent;
use Title;

class SimpleTermsParser {
	/**
	 * @var DefinitionList
	 */
	private $definitionList;

	/**
	 * Returns the serialized definition list or false on error
	 *
	 * @return mixed
	 * @throws ExtensionDependencyError
	 */
	public function parse() {
		$cacheValue = self::getCache()->get( self::getCacheKey() );

		// Item is valid in cache
		if ( $cacheValue !== false ) {
			return $cacheValue;
		}

		$this->definitionList = new DefinitionList();

		$content = $this->loadPageContent();

		if ( $content === null ) {
			return false;
		}

		$this->doParse( $content );
		$serializedList = $this->definitionList->serialize();

		if ( $this->definitionList->size() === 0 ) {
			return false;
		}

		if ( SimpleTerms::getConfigValue( 'SimpleTermsUseCache' ) === true ) {

			$this->writeToCache( $serializedList );
		}

		return $serializedList;
	}

	/**
	 * @return string
	 * @throws ExtensionDependencyError
	 */
	private function loadPageContent(): ?string {
		$title = Title::newFromText( SimpleTerms::getConfigValue( 'SimpleTermsPage', 'Empty' ) );

		if ( $title === null ) {
			return null;
		}

		if ( SimpleTerms::getConfigValue( 'SimpleTermsEnableApprovedRevs' ) === true ) {
			$revision = $this->getRevisionFromApprovedRevs( $title );
		} else {
			$revision = MediaWikiServices::getInstance()
				->getRevisionLookup()
				->getKnownCurrentRevision( $title ?? Title::newMainPage() );
		}

		if ( $revision === false ) {
			return '';
		}

		$content = $revision->getContent( 'main' );

		if ( !$content instanceof TextContent ) {
			return '';
		}

		return $content->getText();
	}

	/**
	 * Parse the configured SimpleTerms page into a Definitionlist
	 *
	 * @param string $text
	 */
	private function doParse( string $text ): void {
		preg_match_all( '/(;\s?(?:\w+\n)+)(?::\s?(.+)\n)+/mu', $text, $matches );

		if ( empty( $matches[1] ) || empty( $matches[2] ) || count( $matches[1] ) !== count( $matches[2] ) ) {
			return;
		}

		$this->buildDataStructure( [
			'terms' => $matches[1],
			'definitions' => $matches[2],
		] );
	}

	/**
	 * Builds the data structure from elements
	 *
	 * @param array $found
	 */
	private function buildDataStructure( array $found ): void {
		for ( $i = 0, $iMax = count( $found['terms'] ); $i < $iMax; $i++ ) {
			$this->definitionList->addElement(
				new Element(
					$this->filterMapTerms( $found['terms'][$i] ),
					trim( $found['definitions'][$i] )
				)
			);
		}
	}

	/**
	 * Extract the terms for each definition
	 *
	 * @param string $term
	 * @return array
	 */
	private function filterMapTerms( string $term ): array {
		return array_filter(
		// Trim and remove line breaks
			array_map(
				static function ( $entry ) {
					return preg_replace( '/\r?\n|\r/', '', trim( $entry ) );
				},
				// Explode terms by ;
				explode( ';', $term )
			),
			// Only use non empty terms
			static function ( $entry ) {
				return $entry !== '';
			}
		);
	}

	/**
	 * Write the created DefinitionList to cache
	 *
	 * @param string $serializedList
	 * @return bool
	 */
	private function writeToCache( string $serializedList ): bool {
		$success = self::getCache()->set(
			self::getCacheKey(),
			$serializedList,
			(int)SimpleTerms::getConfigValue( 'SimpleTermsCacheExpiry', 60 * 60 * 24 * 30 )
		);

		wfDebug( sprintf( 'Definition Cache settings was %ssuccessful.', ( $success === true ?: 'not ' ) ) );

		return $success;
	}

	/**
	 * @param Title $title
	 * @return RevisionRecord|null
	 * @throws ExtensionDependencyError
	 */
	private function getRevisionFromApprovedRevs( Title $title ): ?RevisionRecord {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'ApprovedRevs' ) ) {
			throw new ExtensionDependencyError( [
				[
					'msg' => 'Approved Revs is not loaded',
					'type' => 'missing-extensions',
				]
			] );
		}

		return MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getRevisionById( ApprovedRevs::getApprovedRevID( $title ?? Title::newMainPage() ) );
	}

	/**
	 * Return the cache to use
	 *
	 * @return BagOStuff
	 */
	public static function getCache(): BagOStuff {
		$cacheType = SimpleTerms::getConfigValue( 'SimpleTermsCacheType' );

		if ( $cacheType !== null ) {
			$cache = ObjectCache::getInstance( $cacheType );
		} else {
			$cache = ObjectCache::getLocalClusterInstance();
		}

		return $cache;
	}

	/**
	 * @return string
	 */
	public static function getCacheKey(): string {
		return ObjectCache::getLocalClusterInstance()->makeKey(
			'ext',
			'simple-terms',
			'definitionlist',
			DefinitionList::VERSION
		);
	}

	/**
	 * Purges the SimpleTerms tree from the cache.
	 */
	public static function purgeGlossaryFromCache(): void {
		self::getCache()->delete( self::getCacheKey() );
	}
}
