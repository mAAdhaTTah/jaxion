<?php
namespace Intraxia\Jaxion\Test\Axolotl\Relationship;

use Intraxia\Jaxion\Test\Axolotl\EntityManagerTest;
use Intraxia\Jaxion\Test\Axolotl\Stub\Relationship\PostAndTableWithBelongsToOneByParentIDModel as Model;
use Mockery;
use stdClass;

class BelongsToOneTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();
		Mockery::mock( 'overload:WP_Post' );
	}

	public function test_should_attach_relation() {
		$model = new Model;
		$model->unguard();
		$model->ID                                      = 2;
		$model->get_underlying_wp_object()->post_parent = 1;
		$model->reguard();
		$database = Mockery::mock( 'Intraxia\Jaxion\Axolotl\EntityManager' );
		$database
			->shouldReceive( 'find' )
			->once()
			->with( EntityManagerTest::POST_AND_META_WITH_HAS_MANY_BY_PARENT_ID, 1 )
			->andReturn(
				$related = Mockery::mock(
					EntityManagerTest::POST_AND_META_WITH_HAS_MANY_BY_PARENT_ID
				)
			);

		$model->related_parent()->attach_relation( $database );


		$this->assertSame( $related , $model->parent );
	}

	public function test_should_return_relation_sha() {
		$model    = new Model;
		$relation = $model->related_parent();

		$this->assertInternalType( 'string', $relation->get_sha() );
		$this->assertSame( $relation->get_sha(), $relation->get_sha() );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}
}
