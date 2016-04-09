<?php
namespace Intraxia\Jaxion\Axolotl;

use Countable;
use Intraxia\Jaxion\Contract\Axolotl\Serializes;
use Iterator;
use LogicException;
use RuntimeException;

/**
 * Class Collection
 *
 * @package Intraxia\Jaxion
 * @subpackage Axolotl
 */
class Collection implements Countable, Iterator, Serializes {
	/**
	 * Collection elements.
	 *
	 * @var array
	 */
	protected $elements = array();

	/**
	 * Models registered to the collection.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Where Collection is in loop.
	 *
	 * @var int
	 */
	protected $position = 0;

	/**
	 * Collection constructor.
	 *
	 * @param array $elements
	 * @param array $config
	 */
	public function __construct( array $elements = array(), array $config = array() ) {
		$this->parse_config( $config );

		foreach ( $elements as $element ) {
			$this->add( $element );
		}
	}

	/**
	 * Adds a new element to the Collection.
	 *
	 * @param mixed $element
	 *
	 * @throws RuntimeException
	 */
	public function add( $element ) {
		if ( $this->model && is_array( $element ) ) {
			$element = new $this->model( $element );
		}

		if ( $this->model && ! ( $element instanceof $this->model ) ) {
			throw new RuntimeException;
		}

		$this->elements[] = $element;
	}

	/**
	 * Fetches the element at the provided index.
	 *
	 * @param int $index
	 *
	 * @return mixed
	 */
	public function at( $index ) {
		return $this->elements[ $index ];
	}

	/**
	 * Return the current element.
	 *
	 * @return mixed
	 */
	public function current() {
		return $this->at( $this->position );
	}

	/**
	 * Move forward to next element.
	 */
	public function next() {
		$this->position ++;
	}

	/**
	 * Return the key of the current element.
	 *
	 * @return mixed
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return bool
	 */
	public function valid() {
		return isset( $this->elements[ $this->position ] );
	}

	/**
	 * Rewind the Iterator to the first element.
	 */
	public function rewind() {
		$this->position = 0;
	}

	/**
	 * Count elements of an object.
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->elements );
	}

	/**
	 * Parses the Collection config to set its properties.
	 *
	 * @param array $config
	 *
	 * @throws LogicException
	 */
	protected function parse_config( array $config ) {
		if ( isset( $config['model'] ) ) {
			$model = $config['model'];

			if ( ! is_subclass_of( $model, 'Intraxia\Jaxion\Axolotl\Model' ) ) {
				throw new LogicException;
			}

			$this->model = $model;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function serialize() {
		return array_map(function( $element ) {
			if ( $element instanceof Serializes ) {
				return $element->serialize();
			}

			return $element;
		}, $this->elements);
	}
}
