<?php
namespace Intraxia\Jaxion\Axolotl\Repository;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Axolotl\EntityManager;
use Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable;
use WP_Error;
use WP_Query;
use wpdb;

/**
 * Class AbstractRepository
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl\Repository
 */
abstract class AbstractRepository {
	/**
	 * All models found by Repositories.
	 *
	 * @var array
	 */
	protected static $found = array();

	/**
	 * Retrieves the retrieved model by class and id.
	 *
	 * @param string $class
	 * @param int    $id
	 *
	 * @return bool
	 */
	protected static function get_found( $class, $id ) {
		if ( isset( self::$found[ $class ][ $id ] ) ) {
			return self::$found[ $class ][ $id ];
		}

		return false;
	}

	/**
	 * EntityManger service.
	 *
	 * @var EntityManager
	 */
	protected $database;

	/**
	 * Model class for this repository.
	 *
	 * @var string
	 */
	protected $class;

	/**
	 * EntityManager prefix.
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
	 * WordPress Database connection.
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * AbstractRepository constructor.
	 *
	 * @param EntityManager $database
	 * @param string        $class
	 */
	public function __construct( EntityManager $database, $class ) {
		$this->database = $database;
		$this->class    = $class;
		$this->prefix   = $database->get_prefix();
		$this->main     = $database->get_main_query();
		$this->wpdb     = $database->get_wpdb();

		if ( ! isset( self::$found[ $class ] ) ) {
			self::$found[ $class ] = array();
		}
	}

	/**
	 * Get a single model of the repository class with the given ID.
	 *
	 * @param int $id ID of the model.
	 *
	 * @return Model|WP_Error
	 */
	abstract public function find( $id );

	/**
	 * Finds all the models of the repository class for the given params.
	 *
	 * @param array $params Params to constrain the find.
	 *
	 * @return Collection|WP_Error
	 */
	abstract public function find_by( $params = array() );

	/**
	 * Create and saves a new model of the repository class
	 * with the given data.
	 *
	 * @param array $data
	 *
	 * @return Model|WP_Error
	 */
	abstract public function create( $data = array() );

	/**
	 * Updates a model with its latest data.
	 *
	 * @param Model $model
	 *
	 * @return Model|WP_Error
	 */
	abstract public function persist( Model $model );

	/**
	 * Delete the provided Model.
	 *
	 * @param Model $model
	 * @param bool  $force
	 *
	 * @return Model|WP_Error
	 */
	abstract public function delete( Model $model, $force = false );

	/**
	 * Registers the found Model with the EntityManager.
	 *
	 * @param Model $model
	 */
	public function register_model( Model $model ) {
		self::$found[ $this->class ][ $model->get_primary_id() ] = $model;
	}

	/**
	 * Fills the provided Model with attributes from its custom table.
	 *
	 * @param Model $model
	 */
	protected function fill_table_attrs_from_table( Model $model ) {
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
}
