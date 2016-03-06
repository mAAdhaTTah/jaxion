<?php
namespace Intraxia\Jaxion\Axolotl\Repository;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable;
use WP_Error;

/**
 * Class AbstractWordPress
 *
 * @package Intraxia\Jaxion
 * @subpackage Axolotl\Repository
 */
abstract class AbstractWordPress extends AbstractRepository {
	/**
	 * {@inheritDoc}
	 *
	 * @param int $id
	 */
	public function find( $id ) {
		if ( $model = self::get_found( $this->class, $id ) ) {
			return $model;
		}

		$object = $this->get_wp_object_by_id( $id );

		if ( ! $object ) {
			return new WP_Error(
				'not_found',
				__( 'Entity not found', 'jaxion' )
			);
		}

		return $this->make_model_from_wp_object( $object );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params
	 *
	 * @return Collection
	 */
	public function find_by( $params = array() ) {
		$collection = new Collection(
			array(),
			array( 'model' => $this->class )
		);

		foreach ( $this->get_wp_objects_by_params( $params ) as $object ) {
			$collection->add(
				$this->make_model_from_wp_object( $object )
			);
		}

		return $collection;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $data
	 */
	public function create( $data = array() ) {
		// TODO: Implement create() method.
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 *
	 * @return Model|WP_Error
	 */
	public function persist( Model $model ) {
		// TODO: Implement persist() method.
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 * @param bool  $force
	 *
	 * @return Model|WP_Error
	 */
	public function delete( Model $model, $force = false ) {
		// TODO: Implement delete() method.
	}

	/**
	 * Builds the provided model class from the a wp object.
	 *
	 * @param object $object
	 *
	 * @return Model
	 */
	protected function make_model_from_wp_object( $object ) {
		if ( $model = self::get_found( $this->class, $object->ID ) ) {
			return $model;
		}

		/**
		 * Found Model.
		 *
		 * @var Model $model
		 */
		$model = new $this->class( array( 'object' => $object ) );

		if ( $model instanceof UsesCustomTable ) {
			$this->fill_table_attrs_from_table( $model );
		} else {
			$this->fill_table_attrs_from_meta( $model );
		}

		return $model;
	}

	/**
	 * Generates the unique meta key for
	 * a WordPress model's data.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function create_meta_key( $key ) {
		return "_{$this->prefix}_{$key}";
	}

	/**
	 * Retrieves an WordPress object by the provided id.
	 *
	 * @param int $id
	 *
	 * @return object|false
	 */
	abstract protected function get_wp_object_by_id( $id );

	/**
	 * Retrieves an array of WordPress objects by the provided params.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	abstract protected function get_wp_objects_by_params( $params );

	/**
	 * Fills the provided Model with its meta attributes.
	 *
	 * @param Model $model
	 */
	abstract protected function fill_table_attrs_from_meta( Model $model );
}
