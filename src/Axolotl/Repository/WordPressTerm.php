<?php
namespace Intraxia\Jaxion\Axolotl\Repository;

use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressTerm;
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
						$this->create_meta_key( $key ),
						true
					)
				);
			}
		}

		$model->reguard();
	}
}
