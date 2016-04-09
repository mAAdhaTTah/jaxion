<?php
namespace Intraxia\Jaxion\Axolotl\Repository;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable;
use LogicException;
use WP_Error;
use WP_Post;
use WP_Term;

/**
 * Class AbstractWordPress
 *
 * @package    Intraxia\Jaxion
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
	public function find_by( array $params = array() ) {
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
	 *
	 * @return Model|WP_Error
	 */
	public function create( array $data = array() ) {
		/**
		 * Model object.
		 *
		 * @var Model $model
		 */
		$model = new $this->class( $data );

		$id = $this->save_wp_object( $model );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$model->set_attribute(
			'object',
			$this->get_wp_object_by_id( $id )
		);

		$this->save_table_attributes( $model );
		$model->sync_original();

		return $model;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 *
	 * @return Model|WP_Error
	 */
	public function persist( Model $model ) {
		$id = $this->save_wp_object( $model );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$model->set_attribute(
			'object',
			$this->get_wp_object_by_id( $id )
		);

		$this->save_table_attributes( $model );
		$model->sync_original();

		return $model;
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
		$result = $this->delete_wp_object( $model, $force );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $force ) {
			$this->delete_table_attributes( $model );
		}

		return $model;
	}

	/**
	 * Builds the provided model class from the a wp object.
	 *
	 * @param WP_Post|WP_Term $object
	 *
	 * @return Model
	 */
	protected function make_model_from_wp_object( $object ) {
		if ( $model = self::get_found( $this->class, $this->get_wp_object_id( $object ) ) ) {
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
	 * Save the table attributes for the provided model
	 * to either a custom table or the Model's object's
	 * metadata.
	 *
	 * @param Model $model
	 */
	protected function save_table_attributes( Model $model ) {
		if ( $model instanceof UsesCustomTable ) {
			$this->save_table_attributes_to_table( $model );
		} else {
			$this->save_table_attributes_to_meta( $model );
		}
	}

	/**
	 * Delete the table attributes for the provided model
	 * from either a custom table or the Model's object's
	 * metadata.
	 *
	 * @param Model $model
	 */
	protected function delete_table_attributes( Model $model ) {
		if ( $model instanceof UsesCustomTable ) {
			$this->delete_table_attributes_from_table( $model );
		} else {
			$this->delete_table_attributes_from_meta( $model );
		}
	}

	/**
	 * Generates the unique meta key for
	 * a WordPress model's data.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function make_meta_key( $key ) {
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

	/**
	 * Save the provided WordPress object to the WordPress database.
	 *
	 * @param Model $model
	 *
	 * @return int|WP_Error
	 */
	abstract protected function save_wp_object( Model $model );

	/**
	 * Saves the provided Model's changed table attributes
	 * to the appropriate meta table.
	 *
	 * @param Model $model
	 */
	abstract protected function save_table_attributes_to_meta( Model $model );

	/**
	 * Delete the WordPress object associated with the
	 * provided model. If `$force` is true, the object
	 * will be deleted directly rather than
	 *
	 * @param Model $model
	 * @param bool  $force
	 */
	abstract protected function delete_wp_object( Model $model, $force = false );

	/**
	 * Delete all of the metadata associated with the object on
	 * the provided model.
	 *
	 * @param Model $model
	 */
	abstract protected function delete_table_attributes_from_meta( Model $model );

	/**
	 * Gets the primary ID of the provided WordPress object.
	 *
	 * @param WP_Post|WP_Term $object
	 *
	 * @return int|null
	 *
	 * @throws LogicException
	 */
	protected function get_wp_object_id( $object ) {
		if ( $object instanceof WP_Post ) {
			return $object->ID;
		}

		if ( $object instanceof WP_Term ||
		     // This is required for WP4.3- compatibility, when they returned stdClass.
		     ( $object instanceof \stdClass && property_exists( $object, 'term_id' ) )
		) {
			return $object->term_id;
		}

		throw new LogicException;
	}
}
