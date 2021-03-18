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

use ArrayAccess;
use BadFunctionCallException;
use InvalidArgumentException;
use Serializable;

class DefinitionList implements Serializable, ArrayAccess {
	/**
	 * Class Version used in caching
	 */
	public const VERSION = 1;

	/**
	 * Map Term => Index of Element
	 *
	 * @var array
	 */
	private $terms = [];

	/**
	 * Count number of terms that map to a definition
	 *
	 * @var array
	 */
	private $elementTermCounts = [];

	/**
	 * @var Element[]
	 */
	private $elements = [];

	/**
	 * @var int Current index
	 */
	private $idx = 0;

	/**
	 * @var int Min length of all added terms
	 */
	private $minLength = 1000;

	public function getMinTermLength(): int {
		return $this->minLength;
	}

	/**
	 * Adds or replaces an element to the data structure
	 *
	 * @param Element $element
	 */
	public function addElement( Element $element ): void {
		if ( !empty( array_intersect_key( $this->terms, array_flip( $element->getTerms() ) ) ) ) {

			$this->replaceElement( $element );

			return;
		}

		$this->elementTermCounts[$this->idx] = 0;

		foreach ( $element->getTerms() as $term ) {
			$this->terms[$term] = $this->idx;
			++$this->elementTermCounts[$this->idx];
			if ( strlen( $term ) < $this->minLength ) {
				$this->minLength = strlen( $term );
			}
		}

		$this->elements[$this->idx] = $element;
		$this->idx++;
	}

	/**
	 * Replaces an element by term name
	 *
	 * @param Element $element
	 */
	public function replaceElement( Element $element ): void {
		$x = array_intersect_key( $this->terms, array_flip( $element->getTerms() ) );

		$this->terms = array_diff_key( $this->terms, array_flip( $element->getTerms() ) );
		$replaceIndex = array_values( $x )[0];

		foreach ( $element->getTerms() as $term ) {
			$this->terms[$term] = $replaceIndex;
		}

		$this->elements[$replaceIndex] = $element;
	}

	public function getArrayForReplacement( string $regex ): array {
		$replacements = [];

		foreach ( $this->getTerms() as $term ) {
			$replacements[] = [
				sprintf( $regex, preg_quote( $term, '\\' ) ),
				$this[$term]->getFormattedDefinition(
					$term,
					SimpleTerms::getConfigValue( 'SimpleTermsAllowHtml', false ) !== true
				)
			];
		}

		return $replacements;
	}

	/**
	 * @param string $term
	 * @return bool True if a term is defined in this structure
	 */
	public function canDefine( string $term ): bool {
		return array_key_exists( trim( $term ), $this->terms );
	}

	/**
	 * @return int The overall count of added elements
	 */
	public function size(): int {
		return count( $this->elements );
	}

	/**
	 * @return Element[]
	 */
	public function getElements(): array {
		return array_values( $this->elements );
	}

	/**
	 * @return array All added terms
	 */
	public function getTerms(): array {
		return array_keys( $this->terms );
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function __toString(): string {
		return $this->serialize();
	}

	/**
	 * @return false|string|null
	 */
	public function serialize() {
		return json_encode( [
			'terms' => $this->terms,
			'elements' => array_map( function ( Element $element ) {
				return $element->__serialize();
			}, $this->elements ),
		] );
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$data = json_decode( $serialized, true );

		foreach ( $data['elements'] as $key => $element ) {
			$el = new Element( [], '' );
			$el->unserialize( $element );
			$this->addElement( $el );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->terms[$offset] );
	}

	/**
	 * @inheritDoc
	 * @return Element
	 */
	public function offsetGet( $offset ): ?Element {
		if ( !$this->offsetExists( $offset ) ) {
			return null;
		}

		return $this->elements[$this->terms[$offset]];
	}

	/**
	 * @inheritDoc
	 */
	public function offsetSet( $offset, $value ): void {
		throw new BadFunctionCallException( 'Set an Element using addElement().' );
	}

	/**
	 * @inheritDoc
	 */
	public function offsetUnset( $offset ): void {
		if ( !$this->offsetExists( $offset ) ) {
			throw new InvalidArgumentException( sprintf( '"%s" does not exist.', $offset ) );
		}

		$index = $this->terms[$offset];
		unset( $this->terms[$index] );

		if ( $this->elementTermCounts[$index] === 1 ) {
			unset( $this->elements[$index], $this->elementTermCounts[$index] );
		} elseif ( $this->elementTermCounts[$index] > 1 ) {
			--$this->elementTermCounts[$index];
		}
	}
}
