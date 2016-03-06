<?php
namespace Intraxia\Jaxion\Contract\Axolotl;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Axolotl\Model;
use WP_Error;

interface EntityManager {
	/**
	 * Get a single model of the provided class with the given ID.
	 *
	 * @param string $class Fully qualified class name of model.
	 * @param int    $id    ID of the model.
	 *
	 * @return Model|WP_Error
	 */
	public function find( $class, $id );

	/**
	 * Finds all the models of the provided class for the given params.
	 *
	 * This method will return an empty Collection if the query returns no models.
	 *
	 * @param string $class  Fully qualified class name of models to find.
	 * @param array  $params Params to constrain the find.
	 *
	 * @return Collection|WP_Error
	 */
	public function find_by( $class, $params = array() );

	/**
	 * Saves a new model of the provided class with the given data.
	 *
	 * @param string $class
	 * @param array  $data
	 *
	 * @return Model|WP_Error
	 */
	public function create( $class, $data = array() );

	/**
	 * Updates a model with its latest data.
	 *
	 * @param Model $model
	 *
	 * @return Model|WP_Error
	 */
	public function persist( Model $model );

	/**
	 * Delete the provided Model.
	 *
	 * @param Model $model
	 * @param bool  $force
	 *
	 * @return Model|WP_Error
	 */
	public function delete( Model $model, $force = false );

	/**
	 * Frees all currently managed models from the EntityManager.
	 *
	 * This ensures no models are managed by the Manager and all
	 * future models are refetched from the database.
	 */
	public function free();
}
