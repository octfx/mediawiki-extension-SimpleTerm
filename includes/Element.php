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

use Serializable;

class Element implements Serializable {
	/**
	 * @var string The HTML format
	 */
	private $format = '$1<span class="simple-terms-tooltip" role="tooltip" data-tippy-content="%s">%s</span>$2';
	private $simpleFormat = '$1<span class="simple-terms-tooltip" title="%s">%s</span>$2';

	/**
	 * @var array The terms
	 */
	private $terms;

	/**
	 * @var string The definition
	 */
	private $definition;

	/**
	 * Element constructor.
	 * @param array $terms
	 * @param string $definition
	 */
	public function __construct( array $terms, string $definition ) {
		$this->terms = $terms;
		$this->definition = $definition;
	}

	/**
	 * @param string $term
	 * @return bool True if this element defines the term
	 */
	public function defines( string $term ): bool {
		return in_array( trim( $term ), $this->terms, true );
	}

	/**
	 * @return int The terms
	 */
	public function termCount(): int {
		return count( $this->terms );
	}

	/**
	 * @return array
	 */
	public function getTerms(): array {
		return $this->terms;
	}

	/**
	 * @return string
	 */
	public function getDefinition(): string {
		return $this->definition;
	}

	/**
	 * @param string|null $term
	 * @param bool $stripTags
	 * @return string
	 */
	public function getFormattedDefinition( ?string $term, bool $stripTags = true ): string {
		if ( $term === null ) {
			$term = $this->getTerms()[0];
		}

		return sprintf(
			$this->format,
			$stripTags === true ? strip_tags( $this->definition ) : $this->definition,
			$term
		);
	}

	/**
	 * @param string|null $term
	 * @param bool $stripTags
	 * @return string
	 */
	public function getSimpleFormattedDefinition( ?string $term, bool $stripTags = true ): string {
		if ( $term === null ) {
			$term = $this->getTerms()[0];
		}

		return sprintf(
			$this->simpleFormat,
            strip_tags( $this->definition ),
			$term
		);
	}

	/**
	 * @return array
	 */
	public function __serialize(): array {
		return [
			'terms' => $this->terms,
			'definition' => $this->definition,
		];
	}

	/**
	 * Serialize this instance
	 *
	 * @return false|string|null
	 */
	public function serialize() {
		return json_encode( [
			'terms' => $this->terms,
			'definition' => $this->definition,
		] );
	}

	/**
	 * Unserialize a json string to an instance
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		if ( is_string( $serialized ) ) {

			$data = json_decode( $serialized, true );
			$terms = $data['terms'];
			$definition = $data['definition'];
		} else {
			$terms = $serialized['terms'];
			$definition = $serialized['definition'];

		}

		$this->terms = $terms;
		$this->definition = $definition;
	}
}
