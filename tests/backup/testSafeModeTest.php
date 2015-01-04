<?php

/**
 * Test the the property getters works
 *
 * @extends WP_UnitTestCase
 */
class testSafeModeTestCase extends HM_Backup_UnitTestCase {

	function ini_get_mock() {

		return $this->safe_mode;

	}

	function testSafeModeEmpty() {

		$this->safe_mode = '';

		$this->assertEmpty( $this->ini_get_mock() );

		$this->assertFalse( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeUpperCaseOff() {

		$this->safe_mode = 'Off';

		$this->assertEquals( $this->ini_get_mock(), 'Off' );

		$this->assertFalse( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeLowerCaseOff() {

		$this->safe_mode = 'off';

		$this->assertEquals( $this->ini_get_mock(), 'off' );

		$this->assertFalse( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeWierdCaseOff() {

		$this->safe_mode = 'oFf';

		$this->assertEquals( $this->ini_get_mock(), 'oFf' );

		$this->assertFalse( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeStringZero() {

		$this->safe_mode = '0';

		$this->assertEquals( $this->ini_get_mock(), '0' );

		$this->assertFalse( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeIntZero() {

		$this->safe_mode = 0;

		$this->assertEquals( $this->ini_get_mock(), 0 );

		$this->assertFalse( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeFalse() {

		$this->safe_mode = false;

		$this->assertFalse( $this->ini_get_mock() );

		$this->assertFalse( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeNull() {

		$this->safe_mode = null;

		$this->assertNull( $this->ini_get_mock() );

		$this->assertFalse( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeIntOne() {

		$this->safe_mode = 1;

		$this->assertEquals( $this->ini_get_mock(), 1 );

		$this->assertTrue( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeStringOne() {

		$this->safe_mode = '1';

		$this->assertEquals( $this->ini_get_mock(), '1' );

		$this->assertTrue( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeUpperCaseOn() {

		$this->safe_mode = 'On';

		$this->assertEquals( $this->ini_get_mock(), 'On' );

		$this->assertTrue( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeLowerCaseOn() {

		$this->safe_mode = 'on';

		$this->assertEquals( $this->ini_get_mock(), 'on' );

		$this->assertTrue( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeWierdCaseOn() {

		$this->safe_mode = 'oN';

		$this->assertEquals( $this->ini_get_mock(), 'oN' );

		$this->assertTrue( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

	function testSafeModeTrue() {

		$this->safe_mode = true;

		$this->assertEquals( $this->ini_get_mock(), true );

		$this->assertTrue( HM_Backup::is_safe_mode_active( array( $this, 'ini_get_mock' ) ) );

	}

}