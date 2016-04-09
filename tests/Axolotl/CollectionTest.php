<?php
namespace Intraxia\Jaxion\Test\Axolotl;

use Intraxia\Jaxion\Axolotl\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();

		\Mockery::mock( 'overload:WP_Post' );
	}

	public function test_collection_should_implement_countable() {
		$this->assertInstanceOf( 'Countable', new Collection );
	}

	public function test_should_return_correct_count() {
		$this->assertCount( 2, new Collection( array( 'a', 'b' ) ) );
	}

	public function test_should_implement_iterator() {
		$this->assertInstanceOf( 'Iterator', new Collection );
	}

	public function test_should_iterate_correctly() {
		foreach ( new Collection( array( 'a', 'b' ) ) as $key => $item ) {
			$this->assertTrue( in_array( $key, array( 0, 1 ) ) );
			$this->assertTrue( in_array( $item, array( 'a', 'b' ) ) );
		}
	}

	public function test_should_add_to_collection() {
		$collection = new Collection( array( 'a' ) );

		$this->assertCount( 1, $collection );

		$collection->add( 'b' );

		$this->assertCount( 2, $collection );
	}

	public function test_should_get_element_by_index() {
		$collection = new Collection( array( 'a', 'b' ) );

		$this->assertSame( 'b', $collection->at( 1 ) );
	}

	public function test_should_throw_exception_setting_incorrect_class() {
		$this->setExpectedException( 'LogicException' );

		new Collection( array(), array(
			'model' => __CLASS__
		) );
	}

	public function test_should_throw_exception_adding_scalar_to_model_collection() {
		$collection = new Collection( array(), array(
			'model' => EntityManagerTest::POST_AND_META
		) );

		$this->setExpectedException( 'RuntimeException' );

		$collection->add( 0 );
	}

	public function test_should_add_model_to_collection_by_array() {
		$collection = new Collection( array(
			array( 'title' => 'Post title' )
		), array(
			'model' => EntityManagerTest::POST_AND_META
		) );

		$this->assertInstanceOf( EntityManagerTest::POST_AND_META, $collection->at( 0 ) );

		$collection->add( array( 'title' => 'Post title 2' ) );

		$this->assertInstanceOf( EntityManagerTest::POST_AND_META, $collection->at( 1 ) );
		$this->assertSame( 'Post title 2', $collection->at( 1 )->title );
	}

	public function test_should_add_model_to_collection_by_model() {
		$collection = new Collection( array(
			array( 'title' => 'Post title' )
		), array(
			'model' => EntityManagerTest::POST_AND_META
		) );

		$class = EntityManagerTest::POST_AND_META;

		$this->assertInstanceOf( $class, $collection->at( 0 ) );

		$collection->add( new $class( array( 'title' => 'Post title 2' ) ) );

		$this->assertInstanceOf( EntityManagerTest::POST_AND_META, $collection->at( 1 ) );
		$this->assertSame( 'Post title 2', $collection->at( 1 )->title );
	}

	public function test_should_not_change_unserializable() {
		$args       = array( 'a', 'b' );
		$collection = new Collection( $args );

		$this->assertSame( $args, $collection->serialize() );
	}

	public function test_should_serialze_serializables() {
		$collection = new Collection( array(
			array(
				'title' => 'Post title',
				'text'  => 'Text value',
			)
		), array(
			'model' => EntityManagerTest::POST_AND_META
		) );

		$this->assertCount( 1, $collection->serialize() );
	}
}
