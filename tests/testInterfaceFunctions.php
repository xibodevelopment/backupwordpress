<?php

/**
 * Tests for utility functions in the interface script
 *
 * @extends WP_UnitTestCase
 */
class TestInterfaceFunctions extends WP_UnitTestCase {

	/**
	 * @group: functionavailable
	 */
	public function test_function_is_available_when_function_is_disabled() {

		$function_to_check = 'exec';

		$this->assertFalse( hmbkp_is_function_available( $function_to_check ) );
	}

	/**
	 * @group: functionavailable
	 */
	public function test_function_is_available_when_function_is_enabled() {

		$function_to_check = 'exec';

		$this->assertTrue( hmbkp_is_function_available( $function_to_check ) );

	}

	/**
	 * @group: functionavailable
	 * @expectedException PHPUnit_Framework_Error_Warning
	 */
	public function test_function_is_available_when_ini_get_is_unavailable() {

		$function_to_check = 'exec';

		$this->assertTrue( @hmbkp_is_function_available( $function_to_check ) );
	}

}
