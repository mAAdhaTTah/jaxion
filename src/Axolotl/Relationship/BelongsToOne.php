<?php
namespace Intraxia\Jaxion\Axolotl\Relationship;

use Intraxia\Jaxion\Axolotl\EntityManager;
use Intraxia\Jaxion\Axolotl\Model;
use LogicException;

/**
 * Class HasMany
 *
 * @package Intraxia\Jaxion
 * @subpackage Axolotl\Relationship
 */
class BelongsToOne extends Root {
	/**
	 * Relationship's primary key name
	 * on related model.
	 *
	 * @var string
	 */
	protected $foreign_key = '';

	/**
	 * HasMany constructor.
	 *
	 * @param Model  $model
	 * @param string $class
	 * @param string $type
	 * @param string $foreign_key
	 */
	public function __construct( Model $model, $class, $type, $foreign_key ) {
		$this->foreign_key = $foreign_key;

		parent::__construct( $model, $class, $type );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param EntityManager $database
	 *
	 * @throws LogicException
	 */
	public function attach_relation( EntityManager $database ) {
		switch ( $this->get_type() ) {
			case 'object':
				$target = $database->find(
					$this->get_class(),
					$this->get_model()->get_underlying_wp_object()->{$this->foreign_key}
				);
				break;
			case 'table': // @todo implement
			default:
				throw new LogicException;
		}

		$this->set_target( $target );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_sha() {
		return sha1(
			__CLASS__ .
			get_class( $this->model ) .
			$this->class .
			$this->type .
			$this->foreign_key
		);
	}
}
