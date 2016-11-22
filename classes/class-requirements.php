<?php

namespace HM\BackUpWordPress;

/**
 * A singleton to handle the registering, unregistering
 * and storage of individual requirements
 */
class Requirements {

	/**
	 * The array of requirements
	 *
	 * Should be of the format array( (string) group => __CLASS__ );
	 * @var array
	 */
	private static $requirements = array();


	/**
	 * Get the array of registered requirements
	 *
	 * @param string $group
	 * @return array
	 */
	public static function get_requirements( $group = '' ) {

		$requirements = $group ? self::$requirements[ $group ] : self::$requirements;

		ksort( $requirements );

		return array_map( array( 'self', 'instantiate' ), $requirements );

	}

	/**
	 * Get the requirement groups
	 *
	 * @return string[]
	 */
	public static function get_requirement_groups() {
		return array_keys( self::$requirements );
	}

	/**
	 * Register a new requirement
	 *
	 * @param        $class
	 * @param string $group
	 */
	public static function register( $class, $group = 'misc' ) {
		self::$requirements[ $group ][] = $class;
	}

	/**
	 * Instantiate the individual requirement classes
	 *
	 * @param string $class
	 * @return array An array of instantiated classes
	 */
	private static function instantiate( $class ) {
		$$class = new $class;
		return $$class;
	}
}
