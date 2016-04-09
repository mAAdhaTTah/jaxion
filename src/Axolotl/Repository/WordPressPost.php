<?php
namespace Intraxia\Jaxion\Axolotl\Repository;

use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Axolotl\Relationship\BelongsToOne;
use Intraxia\Jaxion\Axolotl\Relationship\HasMany;
use Intraxia\Jaxion\Axolotl\Relationship\Root;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;
use WP_Error;
use WP_Post;


/**
 * Class WordPressPost
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl\Repository
 */
class WordPressPost extends AbstractWordPress {
	/**
	 * {@inheritDoc}
	 *
	 * @param int $id
	 *
	 * @return WP_Post|false
	 */
	protected function get_wp_object_by_id( $id ) {
		$args = array_merge( $this->get_wp_query_args(), array(
			'p' => (int) $id,
		) );

		$object = $this->main->query( $args );

		if ( ! $object ) {
			return false;
		}

		return $object[0];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params
	 *
	 * @return WP_Post[]
	 */
	protected function get_wp_objects_by_params( $params ) {
		$args = array_merge(
			$this->get_wp_query_args(),
			$params
		);

		return $this->main->query( $args );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 */
	protected function fill_table_attrs_from_meta( Model $model ) {
		$model->unguard();

		if ( $model instanceof UsesWordPressPost ) {
			foreach ( $model->get_table_keys() as $key ) {
				$model->set_attribute(
					$key,
					get_post_meta(
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
	 * Retrieves the default query args for the provided class.
	 *
	 * @return array
	 */
	protected function get_wp_query_args() {
		$class = $this->class;

		$args = array(
			'post_type'   => $class::get_post_type(),
		);

		/**
		 * Model object.
		 *
		 * @var Model $model
		 */
		$model = new $this->class;

		foreach ( $model->get_related_keys() as $related_key ) {
			/**
			 * Relation object.
			 *
			 * @var Root $relation
			 */
			$relation = $model->{"related_{$related_key}"}();

			if ( $relation instanceof HasMany &&
			     $relation->get_relationship_type() === 'post_post' &&
			     $relation->get_foreign_key() === 'post_parent'
			) {
				$args['post_parent'] = 0;
			}

			if ( $relation instanceof BelongsToOne &&
			     $relation->get_relationship_type() === 'post_post' &&
			     $relation->get_local_key() === 'post_parent'
			) {
				$args['post_parent__not_in'] = array( 0 );
			}
		}

		return $args;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 *
	 * @return int|WP_Error
	 */
	protected function save_wp_object( Model $model ) {
		$object = $model->get_underlying_wp_object();

		return isset( $object->ID ) ?
			wp_update_post( $object, true ) :
			wp_insert_post( $object->to_array(), true );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 */
	protected function save_table_attributes_to_meta( Model $model ) {
		foreach ( $model->get_changed_table_attributes() as $attribute => $value ) {
			update_post_meta(
				$model->get_primary_id(),
				$this->make_meta_key( $attribute ),
				$value
			);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param Model $model
	 * @param bool  $force
	 */
	protected function delete_wp_object( Model $model, $force = false ) {
		wp_delete_post(
			$model->get_primary_id(),
			$force
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param Model $model
	 */
	protected function delete_table_attributes_from_meta( Model $model ) {
		foreach ( $model->get_table_keys() as $key ) {
			delete_post_meta(
				$model->get_primary_id(),
				$key
			);
		}
	}
}
