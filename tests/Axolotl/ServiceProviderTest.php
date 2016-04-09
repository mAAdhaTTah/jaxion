<?php
namespace Intraxia\Jaxion\Test\Axolotl;

use Intraxia\Jaxion\Axolotl\ServiceProvider;
use Mockery;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase {
	public function test_should_define_register_on_container() {
		$provider  = new ServiceProvider;
		$container = Mockery::mock( 'Intraxia\Jaxion\Contract\Core\Container' );
		$container->shouldReceive( 'define' )
			->once()
			->with(
				array( 'database' => 'Intraxia\Jaxion\Contract\Axolotl\EntityManager' ),
				Mockery::type( 'Closure' )
			);

		$provider->register( $container );
	}

	public function tearDown() {
		parent::tearDown();
		Mockery::close();
	}
}
