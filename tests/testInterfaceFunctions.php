<?php

/**
 * Tests for utility functions in the interface script
 *
 * @extends WP_UnitTestCase
 */
class TestInterfaceFunctions extends WP_UnitTestCase {

	/**
	 * @group customConfig
	 */
	public function test_is_disabled_function_when_function_is_disabled() {

		$function_to_check = 'exec';

		$this->assertTrue( hmbkp_is_disabled_function( $function_to_check ) );
	}

	/**
	 * Assert that function returns false
	 */
	public function test_is_disabled_function_when_function_is_enabled() {

		$function_to_check = 'exec';

		$this->assertFalse( hmbkp_is_disabled_function( $function_to_check ) );

	}

	/**
	 * @group customConfig
	 * @expectedException PHPUnit_Framework_Error_Warning
	 */
	public function test_is_disabled_function_when_ini_get_is_unavailable() {

		$function_to_check = 'exec';

		$this->assertTrue( @hmbkp_is_disabled_function( $function_to_check ) );
	}

}
