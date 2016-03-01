<?php
namespace Intraxia\Jaxion\Test\Axolotl\Relationship;

use Intraxia\Jaxion\Axolotl\Collection;
use Intraxia\Jaxion\Test\Axolotl\EntityManagerTest;
use Intraxia\Jaxion\Test\Axolotl\Stub\Relationship\PostAndTableWithHasManyByParentIDModel as Model;
use Mockery;

class HasManyTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();
		Mockery::mock( 'overload:WP_Post' );
	}

	public function test_should_attach_relation() {
		$model = new Model;
		$model->unguard();
		$model->ID = 1;
		$model->reguard();

		$database = Mockery::mock( 'Intraxia\Jaxion\Axolotl\EntityManager' );
		$database
			->shouldReceive( 'find_by' )
			->once()
			->with(
				EntityManagerTest::POST_AND_META_WITH_BELONGS_TO_ONE_BY_PARENT_ID,
				array(
					'post_parent' => 1,
					'nopaging'    => true
				)
			)
			->andReturn( $related = new Collection );

		$model->related_children()->attach_relation( $database );

		$this->assertSame( $related, $model->children );
	}

	public function test_should_return_relation_sha() {
		$model    = new Model;
		$relation = $model->related_children();

		$this->assertInternalType( 'string', $relation->get_sha() );
		$this->assertSame( $relation->get_sha(), $relation->get_sha() );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}
}
