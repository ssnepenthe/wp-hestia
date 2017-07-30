<?php
/**
 * Plates_Provider class.
 *
 * @package hestia
 */

namespace SSNepenthe\Hestia\View;

use Pimple\Container;
use League\Plates\Engine;
use Pimple\ServiceProviderInterface;

/**
 * Defines the plates provider class.
 */
class Plates_Provider implements ServiceProviderInterface {
	/**
	 * Provider-specific registration logic.
	 *
	 * @param  Container $container Container instance.
	 *
	 * @return void
	 */
	public function register( Container $container ) {
		$container['plates'] = function( Container $c ) {
			$manager = new Plates_Manager();

			// That's a lot of engines...
			if ( is_dir( get_stylesheet_directory() . '/templates' ) ) {
				$manager->add_dir( get_stylesheet_directory() . '/templates' );
			}

			$manager->add_dir( get_stylesheet_directory() );

			if ( is_dir( get_template_directory() . '/templates' ) ) {
				$manager->add_dir( get_template_directory() . '/templates' );
			}

			$manager->add_dir( get_template_directory() );

			$manager->add_dir( $c['dir'] . '/templates' );

			return $manager;
		};
	}
}
