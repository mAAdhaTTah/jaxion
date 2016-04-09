<?php
namespace Intraxia\Jaxion\Contract\Axolotl;

interface HasEagerRelationships {
	/**
	 * Returns an array of relationships to load eagerly with the Model.
	 *
	 * This should define relationships that are always loaded with the Model,
	 * to ensure the relationships are cached and queried together.
	 *
	 * @return array
	 */
	public static function get_eager_relationships();
}
