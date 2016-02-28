<?php
namespace Intraxia\Jaxion\Axolotl;

use Intraxia\Jaxion\Contract\Axolotl\EntityManager as EntityManagerContract;
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
		list( $object, $table ) = $this->extract_args( $class );

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

			/**
			 * Found model.
			 *
			 * @var Model $model
			 */
			$model = new $class( array( 'object' => $posts[0] ) );

			if ( $table ) {
				$this->fill_from_table( $model );
			} else {
				$this->fill_from_meta( $model );
			}
		} else {
			// Custom tables backed only not yet implemented.
			throw new LogicException;
		}

		return $model;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $class
	 * @param array  $params
	 */
	public function find_by( $class, $params = array() ) {
		// TODO: Implement find_by() method.
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
		$sql[] = "SELECT * FROM {$this->get_table_name( $model )}";
		$sql[] = "WHERE {$this->get_table_foreign_key( $model )} = %d";

		$sql = $this->wpdb->prepare(
			implode( ' ', $sql ),
			$this->get_primary_id( $model )
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
	protected function get_table_name( UsesCustomTable $model ) {
		return "{$this->wpdb->prefix}{$this->prefix}_{$model::get_table_name()}";
	}

	/**
	 * Generates the table foreign key, depending on the model
	 * implementation.
	 *
	 * @param Model $model
	 *
	 * @return string
	 *
	 * @throws LogicException
	 */
	protected function get_table_foreign_key( Model $model ) {
		if ( $model instanceof UsesWordPressPost ) {
			return 'post_id';
		}

		// Model w/o wp_object not yet supported.
		throw new LogicException;
	}

	/**
	 * Fetches the Model's primary ID, depending on the model
	 * implementation.
	 *
	 * @param Model $model
	 *
	 * @return int
	 *
	 * @throws LogicException
	 */
	protected function get_primary_id( Model $model ) {
		if ( $model instanceof UsesWordPressPost ) {
			return $model->get_underlying_wp_object()->ID;
		}

		// Model w/o wp_object not yet supported.
		throw new LogicException;
	}
}
