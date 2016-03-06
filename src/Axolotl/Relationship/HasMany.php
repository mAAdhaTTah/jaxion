<?php
namespace Intraxia\Jaxion\Axolotl\Relationship;

use Intraxia\Jaxion\Axolotl\EntityManager;
use Intraxia\Jaxion\Axolotl\Model;
use LogicException;

/**
 * Class HasMany
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl\Relationship
 */
class HasMany extends Root {
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
		if ( $this->get_model()->is_filling() ) {
			return;
		}

		$this->get_model()->set_filling( true );

		switch ( $this->get_type() ) {
			case 'object':
				$target = $database->find_by(
					$this->get_class(),
					$this->make_target_params()
				);
				break;
			case 'table': // @todo implement
			default:
				throw new LogicException;
		}

		$this->set_target( $target );

		$this->get_model()->set_filling( false );
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

	/**
	 * Gets the relationship's foreign key.
	 *
	 * @return string
	 */
	public function get_foreign_key() {
		return $this->foreign_key;
	}

	/**
	 * Gets the params required for the EntityManager to find the target models.
	 *
	 * @return array
	 *
	 * @throws LogicException
	 */
	protected function make_target_params() {
		switch ( $this->get_relationship_type() ) {
			case 'post_post':
				return array(
					$this->foreign_key => $this->get_model()->get_primary_id(),
					'nopaging'         => true,
				);
			case 'post_term':
				return array();
			case 'term_post':
				$model = $this->get_model();
				return array(
					'nopaging'  => true,
					'tax_query' => array(
						array(
							'taxonomy' => $model::get_taxonomy(),
							'field'    => 'term_id',
							'terms'    => $model->get_primary_id(),
						)
					),
				);
			default:
				throw new LogicException;
		}
	}
}
