<?php
namespace Intraxia\Jaxion\Test\Axolotl;

use Intraxia\Jaxion\Axolotl\EntityManager;
use Mockery;
use stdClass;
use WP_Mock;
use WP_Mock\Functions;
use WP_Post;

class EntityManagerTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @var EntityManager|Mockery\MockInterface
	 */
	protected $manager;

	/**
	 * @var \WP_Query|Mockery\MockInterface
	 */
	protected $query;

	/**
	 * @var Mockery\MockInterface
	 */
	protected $wpdb;

	/**
	 * @var string
	 */
	const MISIMPLEMENTED = 'Intraxia\Jaxion\Test\Axolotl\Stub\MisimplementedModel';

	/**
	 * @var string
	 */
	const POST_AND_META = 'Intraxia\Jaxion\Test\Axolotl\Stub\PostAndMetaModel';

	/**
	 * @var string
	 */
	const POST_AND_TABLE = 'Intraxia\Jaxion\Test\Axolotl\Stub\PostAndTableModel';

	/**
	 * @var string
	 */
	const POST_AND_META_WITH_HAS_MANY_BY_PARENT_ID = 'Intraxia\Jaxion\Test\Axolotl\Stub\Relationship\PostAndTableWithHasManyByParentIDModel';

	/**
	 * @var string
	 */
	const POST_AND_META_WITH_BELONGS_TO_ONE_BY_PARENT_ID = 'Intraxia\Jaxion\Test\Axolotl\Stub\Relationship\PostAndTableWithBelongsToOneByParentIDModel';

	/**
	 * @var string
	 */
	const TABLE = 'Intraxia\Jaxion\Test\Axolotl\Stub\TableModel';

	public function setUp() {
		global $wpdb;

		parent::setUp();
		WP_Mock::setUp();

		Mockery::mock( 'overload:WP_Error' );
		Mockery::mock( 'overload:WP_Post' );

		$this->wpdb         = $wpdb = Mockery::mock( 'wpdb' );
		$this->wpdb->prefix = 'wp_';
		$this->query        = Mockery::mock( 'WP_Query' );
		$this->manager      = new EntityManager( $this->query, 'ax' );
	}

	public function test_should_throw_exception_with_wrong_class() {
		$this->setExpectedException( 'LogicException' );

		$this->manager->find( __CLASS__, 1 );
	}

	public function test_should_throw_exception_with_misimplmeneted_class() {
		$this->setExpectedException( 'LogicException' );

		$this->manager->find( self::MISIMPLEMENTED, 1 );
	}

	public function test_should_return_error_if_model_not_found() {
		WP_Mock::wpPassthruFunction( '__', array(
			'Entity not found',
			'jaxion'
		) );
		WP_Mock::wpFunction( 'is_wp_error', array(
			'times'  => 1,
			'return' => true
		) );
		$this->query
			->shouldReceive( 'query' )
			->once()
			->with( array(
				'p'         => 1,
				'post_type' => 'custom'
			) )
			->andReturn( array() );

		$result = $this->manager->find( self::POST_AND_META, 1 );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	public function test_should_return_found_post_and_meta_model() {
		$post             = new WP_Post;
		$post->ID         = 1;
		$post->post_title = 'Post title';

		$this->query
			->shouldReceive( 'query' )
			->once()
			->with( array(
				'p'         => 1,
				'post_type' => 'custom'
			) )
			->andReturn( array( $post ) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_ax_text', true ),
			'return' => 'Text value',
		) );

		$result = $this->manager->find( self::POST_AND_META, 1 );

		$this->assertInstanceOf( self::POST_AND_META, $result );
		$this->assertSame( 1, $result->ID );
		$this->assertSame( 'Post title', $result->title );
		$this->assertSame( 'Text value', $result->text );
	}

	public function test_should_return_found_post_and_table_model() {
		$post             = new WP_Post;
		$post->ID         = 1;
		$post->post_title = 'Post title';

		$table          = new stdClass;
		$table->id      = 1;
		$table->post_id = 1;
		$table->text    = 'Text value';

		$this->query
			->shouldReceive( 'query' )
			->once()
			->with( array(
				'p'         => 1,
				'post_type' => 'custom'
			) )
			->andReturn( array( $post ) );

		$this->wpdb
			->shouldReceive( 'prepare' )
			->once()
			->with( 'SELECT * FROM wp_ax_custom WHERE post_id = %d', 1 )
			->andReturn( $query = 'SELECT * FROM wp_ax_custom WHERE post_id = 1' );
		$this->wpdb
			->shouldReceive( 'get_row' )
			->once()
			->with( $query, ARRAY_A )
			->andReturn( $table );

		$result = $this->manager->find( self::POST_AND_TABLE, 1 );

		$this->assertInstanceOf( self::POST_AND_TABLE, $result );
		$this->assertSame( 1, $result->ID );
		$this->assertSame( 'Post title', $result->title );
		$this->assertSame( 'Text value', $result->text );
	}

	public function test_should_return_found_post_and_meta_model_with_has_many_by_parent_id() {
		$parent             = new WP_Post;
		$parent->ID         = 1;
		$parent->post_title = 'Post title';

		$this->query
			->shouldReceive( 'query' )
			->once()
			->with( array(
				'p'         => 1,
				'post_type' => 'custom',
			) )
			->andReturn( array( $parent ) );
		$child1              = new WP_Post;
		$child1->ID          = 2;
		$child1->post_title  = 'Post title';
		$child1->post_parent = 1;

		$child2              = new WP_Post;
		$child2->ID          = 3;
		$child2->post_title  = 'Post title';
		$child2->post_parent = 1;
		$this->query
			->shouldReceive( 'query' )
			->once()
			->with( array(
				'post_parent' => 1,
				'nopaging'    => true,
				'post_type'   => 'custom',
			) )
			->andReturn( array( $child1, $child2 ) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 1, '_ax_text', true ),
			'return' => 'Text value',
		) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 2, '_ax_text', true ),
			'return' => 'Text value 2',
		) );

		WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 3, '_ax_text', true ),
			'return' => 'Text value 3',
		) );

		$result = $this->manager->find( self::POST_AND_META_WITH_HAS_MANY_BY_PARENT_ID, 1 );

		$this->assertInstanceOf( self::POST_AND_META, $result );
		$this->assertSame( 1, $result->ID );
		$this->assertSame( 'Post title', $result->title );
		$this->assertSame( 'Text value', $result->text );
		$this->assertInstanceOf( 'Intraxia\Jaxion\Axolotl\Collection', $result->children );
		$this->assertCount( 2, $result->children );

		foreach ( $result->children as $child ) {
			$this->assertInstanceOf( self::POST_AND_META_WITH_BELONGS_TO_ONE_BY_PARENT_ID, $child );
		}
	}

	public function test_find_should_throw_on_unimplemented_feature() {
		$this->setExpectedException( 'LogicException' );

		$this->manager->find( self::TABLE, 1 );
	}

	public function tearDown() {
		parent::tearDown();
		$this->manager->free();
		Mockery::close();
		WP_Mock::tearDown();
	}
}
