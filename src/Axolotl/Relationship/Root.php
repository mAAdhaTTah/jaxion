<?php
namespace Intraxia\Jaxion\Axolotl\Relationship;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Axolotl\EntityManager;
use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Axolotl\Repository\WordPressPost;
use Intraxia\Jaxion\Axolotl\Repository\WordPressTerm;
use Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressTerm;
use RuntimeException;

/**
 * Class Root
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl\Relationship
 */
abstract class Root {
	/**
	 * Model the relationship is on.
	 *
	 * @var Model
	 */
	protected $model;

	/**
	 * Class name the Model is related to.
	 *
	 * @var string
	 */
	protected $class;

	/**
	 * Whether the Relationship is by object or model.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Root relationship constructor.
	 *
	 * @param Model  $model
	 * @param string $class
	 * @param string $type
	 */
	public function __construct( Model $model, $class, $type ) {
		$this->model = $model;
		$this->class = $class;
		$this->type  = $type;
	}

	/**
	 * Returns the relationship's Model.
	 *
	 * @return Model
	 */
	public function get_model() {
		return $this->model;
	}

	/**
	 * Returns the relationship's related class.
	 *
	 * @return string
	 */
	public function get_class() {
		return $this->class;
	}

	/**
	 * Returns whether the relation is on the object or table.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Sets the relationship target on the model.
	 *
	 * @param Model|Collection $target
	 */
	public function set_target( $target ) {
		$this->get_model()->set_related( $this->get_sha(), $target );
	}

	/**
	 * Returns the relationship type between source and destination Model.
	 *
	 * @return string {source}_{dest}
	 */
	protected function get_relationship_type() {
		$relationship = '';

		if ( $this->get_model() instanceof UsesWordPressPost ) {
			$relationship .= 'post';
		} elseif ( $this->get_model() instanceof UsesWordPressTerm ) {
			$relationship .= 'taxonomy';
		} elseif ( $this->get_model() instanceof UsesCustomTable ) {
			$relationship .= 'table';
		}

		$relationship .= '_';

		if ( is_subclass_of(
			$this->get_class(),
			'Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost'
		) ) {
			$relationship .= 'post';
		} elseif ( is_subclass_of(
			$this->get_class(),
			'Intraxia\Jaxion\Contract\Axolotl\UsesWordPressTerm'
		) ) {
			$relationship .= 'term';
		} elseif ( is_subclass_of(
			$this->get_class(),
			'Intraxia\Jaxion\Contract\Axolotl\UsesCustomTable'
		) ) {
			$relationship .= 'table';
		}

		return $relationship;
	}

	/**
	 * Returns the query args to
	 * fetch the provided relation.
	 *
	 * @param EntityManager $database
	 */
	abstract public function attach_relation( EntityManager $database );

	/**
	 * Generate the sha for the relation.
	 *
	 * @return string
	 */
	abstract public function get_sha();
}
