<?php
namespace Intraxia\Jaxion\Axolotl\Repository;

use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressTerm;
use WP_Error;
use WP_Term;

/**
 * Class WordPressTerm
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl\Repository
 */
class WordPressTerm extends AbstractWordPress {
	/**
	 * {@inheritDoc}
	 *
	 * @param int $id
	 *
	 * @return WP_Term|false
	 */
	protected function get_wp_object_by_id( $id ) {
		$class = $this->class;

		$term = get_term( $id, $class::get_taxonomy() );

		if ( is_wp_error( $term ) ) {
			return false;
		}

		return $term;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params
	 *
	 * @return WP_Term[]
	 */
	protected function get_wp_objects_by_params( $params ) {
		$class = $this->class;

		return get_terms( $class::get_taxonomy(), $params );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 */
	protected function fill_table_attrs_from_meta( Model $model ) {
		$model->unguard();

		if ( $model instanceof UsesWordPressTerm ) {
			foreach ( $model->get_table_keys() as $key ) {
				$model->set_attribute(
					$key,
					get_term_meta(
						$model->get_primary_id(),
						$this->make_meta_key( $key ),
						true
					)
				);
			}
		}

		$model->reguard();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 *
	 * @return int|WP_Error
	 */
	protected function save_wp_object( Model $model ) {
		$class = $this->class;
		$object = $model->get_underlying_wp_object();

		$term_id = $object->term_id;
		unset( $object->term_id );

		$result  = wp_update_term(
			$term_id,
			$class::get_taxonomy(),
			(array) $object
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result['term_id'];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 */
	protected function save_table_attributes_to_meta( Model $model ) {
		foreach ( $model->get_changed_table_attributes() as $attribute => $value ) {
			update_term_meta(
				$model->get_primary_id(),
				$this->make_meta_key( $attribute ),
				$value
			);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 * @param bool  $force
	 */
	protected function delete_wp_object( Model $model, $force = false ) {
		$class = $this->class;

		wp_delete_term(
			$model->get_primary_id(),
			$class::get_taxonomy()
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 */
	protected function delete_table_attributes_from_meta( Model $model ) {
		foreach ( $model->get_table_attributes() as $attribute ) {
			delete_term_meta(
				$model->get_primary_id(),
				$this->make_meta_key( $attribute )
			);
		}
	}
}
