<?php
namespace Intraxia\Jaxion\Axolotl;

use Intraxia\Jaxion\Axolotl\Relationship\Root as Relationship;
use Intraxia\Jaxion\Axolotl\Repository\AbstractRepository;
use Intraxia\Jaxion\Axolotl\Repository\CustomTable as CustomTableRepository;
use Intraxia\Jaxion\Axolotl\Repository\WordPressPost as WordPressPostRepository;
use Intraxia\Jaxion\Contract\Axolotl\EntityManager as EntityManagerContract;
use Intraxia\Jaxion\Contract\Axolotl\HasEagerRelationships;
use Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;
use LogicException;
use WP_Error;
use WP_Query;
use wpdb;

/**
 * Class EntityManager
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl
 */
class EntityManager implements EntityManagerContract {
	/**
	 * Post meta prefix.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * WP_Query instance.
	 *
	 * @var WP_Query
	 */
	protected $main;

	/**
	 * Global WPDB instance.
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * EntityManager constructor.
	 *
	 * @param WP_Query $main
	 * @param string   $prefix Post meta prefix.
	 */
	public function __construct( WP_Query $main, $prefix ) {
		global $wpdb;

		$this->wpdb   = $wpdb;
		$this->main   = $main;
		$this->prefix = $prefix;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $class
	 * @param int    $id
	 *
	 * @return Model|WP_Error
	 *
	 * @throws LogicException
	 */
	public function find( $class, $id ) {
		$repository = $this->get_repository( $class );
		$model      = $repository->find( $id );

		if ( is_wp_error( $model ) ) {
			return $model;
		}

		$this->handle_model( $repository, $model );

		return $model;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $class
	 * @param array  $params
	 *
	 * @return Collection
	 *
	 * @throws LogicException
	 */
	public function find_by( $class, $params = array() ) {
		$repository = $this->get_repository( $class );
		$collection = $repository->find_by( $params );

		foreach ( $collection as $model ) {
			$this->handle_model( $repository, $model );
		}

		return $collection;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $class
	 * @param array  $data
	 *
	 * @return Model|WP_Error
	 */
	public function create( $class, $data = array() ) {
		$repository = $this->get_repository( $class );
		$model      = $repository->create( $data );

		$this->handle_model( $repository, $model );

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
		return $this->get_repository( get_class( $model ) )->persist( $model );
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
		return $this->get_repository( get_class( $model ) )->delete( $model, $force );
	}

	/**
	 * Get the EntityManager prefix.
	 *
	 * @return string
	 */
	public function get_prefix() {
		return $this->prefix;
	}

	/**
	 * Get the main WP_Query instance.
	 *
	 * @return WP_Query
	 */
	public function get_main_query() {
		return $this->main;
	}

	/**
	 * Get the wpdb connection instance.
	 *
	 * @return wpdb
	 */
	public function get_wpdb() {
		return $this->wpdb;
	}

	/**
	 * Retrieves the repository for the given class.
	 *
	 * @param string $class
	 *
	 * @return Repository\AbstractRepository
	 *
	 * @throws LogicException
	 */
	protected function get_repository( $class ) {
		// We can only use Axolotl models.
		if ( ! is_subclass_of( $class, 'Intraxia\Jaxion\Axolotl\Model' ) ) {
			throw new LogicException;
		}

		if ( is_subclass_of(
			$class,
			'Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost'
		) ) {
			return new WordPressPostRepository( $this, $class );
		}

		if ( is_subclass_of(
			$class,
			'Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable'
		) ) {
			throw new LogicException;
		}

		// If a model doesn't have a backing data source,
		// the developer needs to fix this immediately.
		throw new LogicException;
	}

	/**
	 * Ensures the model is registered with the model and fills its relations.
	 *
	 * @param AbstractRepository $repository
	 * @param Model              $model
	 */
	protected function handle_model( AbstractRepository $repository, Model $model ) {
		$repository->register_model( $model );

		if ( $model instanceof HasEagerRelationships ) {
			$this->fill_related( $model, $model::get_eager_relationships() );
		}
	}

	/**
	 * Fills the Model with the provided relations.
	 *
	 * If no relations are provided, all relations are filled.
	 *
	 * @param Model $model
	 * @param array $relations
	 *
	 * @throws LogicException
	 */
	protected function fill_related( Model $model, array $relations = array() ) {
		if ( ! $relations ) {
			$relations = $model->get_related_keys();
		}

		foreach ( $relations as $relation ) {
			if ( ! in_array( $relation, $model->get_related_keys() ) ) {
				throw new LogicException;
			}

			if ( $model->relation_is_filled( $relation ) ) {
				continue;
			}

			/**
			 * Model relationship.
			 *
			 * @var Relationship $relation
			 */
			$relation = $model->{"related_{$relation}"}();
			$relation->attach_relation( $this );
		}
	}
}
