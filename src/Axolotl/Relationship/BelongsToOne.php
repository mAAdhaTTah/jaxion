<?php
namespace Intraxia\Jaxion\Axolotl\Relationship;

use Intraxia\Jaxion\Axolotl\EntityManager;
use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;
use LogicException;

/**
 * Class HasMany
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl\Relationship
 */
class BelongsToOne extends Root {
	/**
	 * Relationship's primary key name
	 * on related model.
	 *
	 * @var string
	 */
	protected $local_key = '';

	/**
	 * HasMany constructor.
	 *
	 * @param Model  $model
	 * @param string $class
	 * @param string $type
	 * @param string $local_key
	 */
	public function __construct( Model $model, $class, $type, $local_key ) {
		$this->local_key = $local_key;

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

		$id = $this->make_target_id();

		if ( $id ) {
			$target = $database->find(
				$this->get_class(),
				$id
			);

			$this->set_target( $target );
		}

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
			$this->local_key
		);
	}

	/**
	 * Get the relationship's local key.
	 *
	 * @return string
	 */
	public function get_local_key() {
		return $this->local_key;
	}

	/**
	 * Gets the ID value for the target model to search by.
	 *
	 * @return int|false
	 *
	 * @throws LogicException
	 */
	protected function make_target_id() {
		$class = $this->get_class();

		switch ( $this->get_relationship_type() ) {
			case 'post_post':
				return $this->get_model()
					->get_underlying_wp_object()
					->{$this->local_key};
			case 'post_term':
				$terms = wp_get_post_terms(
					$this->get_model()
						->get_underlying_wp_object()
						->ID,
					$class::get_taxonomy()
				);

				if ( ! $terms || is_wp_error( $terms ) ) {
					return false;
				}

				return $terms[0]->term_id;
			default:
				throw new LogicException;
		}
	}
}
