<?php
namespace Intraxia\Jaxion\Axolotl\Repository;

use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;
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
		$args = array_merge( array(
			'p' => (int) $id,
		), $this->get_wp_query_args() );

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
	 * @return array
	 */
	protected function get_wp_objects_by_params( $params ) {
		$args = array_merge(
			$params,
			$this->get_wp_query_args()
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
						$model->get_underlying_wp_object()->ID,
						$this->create_meta_key( $key ),
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

		return array(
			'post_type' => $class::get_post_type(),
		);
	}
}
