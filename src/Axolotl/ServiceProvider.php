<?php
namespace Intraxia\Jaxion\Axolotl;

use Intraxia\Jaxion\Contract\Core\Container;
use Intraxia\Jaxion\Contract\Core\ServiceProvider as ServiceProviderContract;
use WP_Query;

/**
 * Class ServiceProvider
 *
 * @package    Intraxia\Jaxion
 * @subpackage Axolotl
 */
class ServiceProvider implements ServiceProviderContract {
	/**
	 * {@inheritdoc}
	 *
	 * @param Container $container
	 */
	public function register( Container $container ) {
		$container->define(
			array( 'database' => 'Intraxia\Jaxion\Contract\Axolotl\EntityManager' ),
			function ( $app ) {
				return new EntityManager( new WP_Query, $app->fetch( 'slug' ) );
			}
		);
	}
}
