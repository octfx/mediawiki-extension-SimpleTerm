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

use ApprovedRevs;
use BagOStuff;
use Content;
use ExtensionDependencyError;
use ExtensionRegistry;
use MediaWiki\Extension\SimpleTerms\DefinitionList;
use MediaWiki\Extension\SimpleTerms\Element;
use MediaWiki\Extension\SimpleTerms\SimpleTerms;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use ObjectCache;
use TextContent;
use Title;

/**
 * Basic Backend for On Wiki Glossary
 */
class BasicBackend extends Backend {
	/**
	 * @var DefinitionList
	 */
	private $definitionList;

	/**
	 * @var string The serialized definition list to reduce parsing
	 */
	private static $serializedListData;

	/**
	 * @var self
	 */
	private static $instance;

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * @return DefinitionList
	 *
	 * @throws ExtensionDependencyError
	 */
	public function getDefinitionList(): DefinitionList {
		if ( $this->definitionList !== null ) {
			return $this->definitionList;
		}

		if ( self::$serializedListData === null ) {
			self::$serializedListData = $this->parse();
		}

		$list = new DefinitionList();
		$list->unserialize( self::$serializedListData );

		return $list;
	}

	/**
	 * The basic backend safes the serialized list in a static member
	 *
	 * @return static
	 */
	public static function getInstance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns the serialized definition list or false on error
	 *
	 * @return string
	 * @throws ExtensionDependencyError
	 */
	private function parse(): string {
		$cacheValue = $this->getSerializedListFromCache();

		// Item is valid in cache
		if ( $cacheValue !== null ) {
			return $cacheValue;
		}

		// We'll need to re-parse
		$this->definitionList = new DefinitionList();

		$content = $this->getPageContent();

		$this->doParse( $content );
		$serializedList = $this->definitionList->serialize();

		if ( $this->definitionList->size() > 0 && $this->useCache() === true ) {
			$this->writeToCache( $serializedList );
		}

		return $serializedList;
	}

	/**
	 * Returns the string content of the set glossary page
	 * String is empty if the Title was not found or the content is not text
	 *
	 * @return string
	 * @throws ExtensionDependencyError If ApprovedRevs was set to true but not loaded
	 */
	private function getPageContent(): string {
		$title = Title::newFromText( SimpleTerms::getConfigValue( 'SimpleTermsPage', 'Empty' ) );

		if ( $title === null ) {
			return '';
		}

		$content = $this->getRevisionContentForTitle( $title );

		if ( $content === null || !$content instanceof TextContent ) {
			return '';
		}

		return $content->getText();
	}

	/**
	 * Returns the current Page revision
	 *
	 * @param Title $title
	 * @return Content|null
	 * @throws ExtensionDependencyError
	 */
	private function getRevisionContentForTitle( Title $title ): ?Content {
		if ( SimpleTerms::getConfigValue( 'SimpleTermsEnableApprovedRevs' ) === true ) {
			$revision = $this->getRevisionFromApprovedRevs( $title );
		} else {
			$revision = MediaWikiServices::getInstance()
				->getRevisionLookup()
				->getKnownCurrentRevision( $title );
		}

		if ( $revision === false ) {
			return null;
		}

		return $revision->getContent( 'main' );
	}

	/**
	 * Parse the configured SimpleTerms page into a Definitionlist
	 *
	 * @param string $text
	 */
	private function doParse( string $text ): void {
		// Regex to match ; Terms (1-n) \n: Definition
		preg_match_all( '/(;\s?(?:.+\n)+)(?::\s?(.+)\n)+/mu', $text, $matches );

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
		if ( !$this->useCache() ) {
			return true;
		}

		$success = $this->getCache()->set(
			$this->getCacheKey(),
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
	private function getCache(): BagOStuff {
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
	private function getCacheKey(): string {
		return ObjectCache::getLocalClusterInstance()->makeKey(
			'ext',
			'simple-terms',
			'definitionlist',
			DefinitionList::VERSION
		);
	}

	private function getSerializedListFromCache(): ?string {
		if ( $this->useCache() === true ) {
			$cacheData = $this->getCache()->get( $this->getCacheKey() );

			if ( $cacheData !== false ) {
				return $cacheData;
			}
		}

		return self::$serializedListData ?? null;
	}

	/**
	 * Purges the SimpleTerms tree from the cache.
	 */
	public static function purgeGlossaryFromCache(): void {
		$instance = self::getInstance();
		if ( !$instance->useCache() ) {
			return;
		}

		$instance->getCache()->delete( $instance->getCacheKey() );
	}
}
