<?php
namespace Intraxia\Jaxion\Axolotl;

use Intraxia\Jaxion\Axolotl\Relationship\Root as Relationship;
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
	 * Found models.
	 *
	 * @var array
	 */
	private $found = array();

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
		list( $object, $table ) = $this->extract_args( $class );

		if ( isset( $this->found[ $class ][ $id ] ) ) {
			return $this->found[ $class ][ $id ];
		}

		if ( $object ) {
			$args = array_merge( array(
				'p' => (int) $id,
			), $this->get_wp_query_args( $class ) );

			$posts = $this->main->query( $args );

			if ( ! $posts ) {
				return new WP_Error(
					'not_found',
					__( 'Entity not found', 'jaxion' )
				);
			}

			$model = $this->make_model_from_wp_object(
				$class,
				$posts[0],
				$table
			);

			$this->register_model( $model );
		} else {
			// Custom tables backed only not yet implemented.
			throw new LogicException;
		}

		if ( $model instanceof HasEagerRelationships ) {
			$this->fill_related( $model, $model::get_eager_relationships() );
		}

		return $model;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $class
	 * @param array  $params
	 *
	 * @throws LogicException
	 */
	public function find_by( $class, $params = array() ) {
		list( $object, $table ) = $this->extract_args( $class );

		$collection = new Collection(
			array(),
			array( 'model' => $class )
		);

		if ( $object ) {
			$args = array_merge(
				$params,
				$this->get_wp_query_args( $class )
			);

			$posts = $this->main->query( $args );

			foreach ( $posts as $post ) {
				$model = $this->make_model_from_wp_object(
					$class,
					$post,
					$table
				);

				$this->register_model( $model );
				$collection->add( $model );
			}
		} else {
			// Custom tables backed only not yet implemented.
			throw new LogicException;
		}

		foreach ( $collection as $model ) {
			if ( $model instanceof HasEagerRelationships ) {
				$this->fill_related( $model, $model::get_eager_relationships() );
			}
		}

		return $collection;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $class
	 * @param array  $data
	 */
	public function create( $class, $data = array() ) {
		// TODO: Implement create() method.
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 */
	public function persist( Model $model ) {
		// TODO: Implement persist() method.
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Model $model
	 * @param bool  $force
	 */
	public function delete( Model $model, $force = false ) {
		// TODO: Implement delete() method.
	}

	/**
	 * Retrieves the default query args for the provided class.
	 *
	 * @param string $class
	 *
	 * @return array
	 */
	protected function get_wp_query_args( $class ) {
		$args = array();

		if ( is_subclass_of(
			$class,
			'Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost'
		) ) {
			$args = array(
				'post_type' => $class::get_post_type(),
			);
		}

		return $args;
	}

	/**
	 * Generates the unique key for a model's
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function create_meta_key( $key ) {
		return "_{$this->prefix}_{$key}";
	}

	/**
	 * Ensures the provided class string is the correct class.
	 *
	 * @param string $class
	 *
	 * @return array
	 *
	 * @throws LogicException
	 */
	protected function extract_args( $class ) {
		// We can only use Axolotl models.
		if ( ! is_subclass_of( $class, 'Intraxia\Jaxion\Axolotl\Model' ) ) {
			throw new LogicException;
		}

		$object = false;
		$table  = false;

		if ( is_subclass_of(
			$class,
			'Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost'
		) ) {
			$object = true;
		}

		if ( is_subclass_of(
			$class,
			'Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable'
		) ) {
			$table = true;
		}

		// If a model doesn't have a backing data source,
		// the developer needs to fix this immediately.
		if ( ! $object && ! $table ) {
			throw new LogicException;
		}

		return array( $object, $table );
	}

	/**
	 * Builds the provided model class from the a wp object.
	 *
	 * @param string $class
	 * @param object $post
	 * @param bool   $table
	 *
	 * @return Model
	 */
	protected function make_model_from_wp_object( $class, $post, $table ) {
		if ( isset( $this->found[ $class ][ $post->ID ] ) ) {
			return $this->found[ $class ][ $post->ID ];
		}

		$model = new $class( array( 'object' => $post ) );

		if ( $table ) {
			$this->fill_from_table( $model );
		} else {
			$this->fill_from_meta( $model );
		}

		return $model;
	}

	/**
	 * Fills the provided Model with its meta attributes.
	 *
	 * @param Model $model
	 */
	protected function fill_from_meta( $model ) {
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
	 * Fills the provided Model with attributes from its custom table.
	 *
	 * @param Model $model
	 */
	protected function fill_from_table( Model $model ) {
		$sql[] = "SELECT * FROM {$this->make_table_name( $model )}";
		$sql[] = "WHERE {$model->get_foreign_key()} = %d";

		$sql = $this->wpdb->prepare(
			implode( ' ', $sql ),
			$model->get_primary_id()
		);

		$row = $this->wpdb->get_row( $sql, ARRAY_A );

		$model->unguard();

		foreach ( $row as $key => $value ) {
			$model->set_attribute( $key, $value );
		}

		$model->reguard();
	}

	/**
	 * Creates the table name string for the provided Model.
	 *
	 * @param UsesCustomTable $model
	 *
	 * @return string
	 */
	protected function make_table_name( UsesCustomTable $model ) {
		return "{$this->wpdb->prefix}{$this->prefix}_{$model::get_table_name()}";
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

			/**
			 * Model relationship.
			 *
			 * @var Relationship $relation
			 */
			$relation = $model->{"related_{$relation}"}();
			$relation->attach_relation( $this );
		}
	}

	/**
	 * Registers the found Model with the EntityManager.
	 *
	 * @param Model $model
	 */
	protected function register_model( Model $model ) {
		if ( ! isset( $this->found[ get_class( $model ) ] ) ) {
			$this->found[ get_class( $model ) ] = array();
		}

		$this->found[ get_class( $model ) ][ $model->get_primary_id() ] = $model;
	}
}
