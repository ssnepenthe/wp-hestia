<?php
/**
 * Delegates to an arbitrary number of template locating strategies.
 *
 * @package hestia
 */

namespace SSNepenthe\Hestia\Template;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * This class accepts any number of template locators and delegates to each until an
 * appropriate template file has been found.
 */
class Template_Locator_Stack implements Template_Locator_Interface {
	/**
	 * List of template locators.
	 *
	 * @var array
	 */
	protected $stack = [];

	/**
	 * Class constructor.
	 *
	 * @param array $locators List of template locators.
	 */
	public function __construct( array $locators = [] ) {
		foreach ( $locators as $locator ) {
			$this->push( $locator );
		}
	}

	/**
	 * Loops through template locators returning the first found template.
	 *
	 * @param  array $templates List of template files.
	 *
	 * @return string
	 */
	public function locate( array $templates ) {
		foreach ( $this->stack as $locator ) {
			$template = $locator->locate( $templates );

			if ( $template ) {
				return $template;
			}
		}

		return '';
	}

	/**
	 * Adds a template locator to the stack.
	 *
	 * @param  Template_Locator_Interface $locator Template locator.
	 */
	public function push( Template_Locator_Interface $locator ) {
		$this->stack[] = $locator;
	}
}
