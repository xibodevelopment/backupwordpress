<?php

namespace HM\BackUpWordPress;

class Exec_Available_Tests extends \HM_Backup_UnitTestCase {

	function ini_get_mock() {
		return $this->disabled;
	}

	function test_nothing_disabled() {

		$this->disabled = '';
		$this->assertFalse( Backup_Utilities::is_function_disabled( 'exec', array( $this, 'ini_get_mock' ) ) );

	}

	function test_shell_exec_disabled() {

		$this->disabled = 'shell_exec';
		$this->assertFalse( Backup_Utilities::is_function_disabled( 'exec', array( $this, 'ini_get_mock' ) ) );

	}

	function test_only_exec_disabled() {

		$this->disabled = 'exec';
		$this->assertTrue( Backup_Utilities::is_function_disabled( 'exec', array( $this, 'ini_get_mock' ) ) );

	}

	function test_multiple_disabled_comma_delimited() {

		$this->disabled = 'exec,shell_exec';
		$this->assertTrue( Backup_Utilities::is_function_disabled( 'exec', array( $this, 'ini_get_mock' ) ) );

		$this->disabled = 'shell_exec,exec,proc_open';
		$this->assertTrue( Backup_Utilities::is_function_disabled( 'exec', array( $this, 'ini_get_mock' ) ) );

	}

	function test_multiple_disabled_space_delimited() {

		$this->disabled = 'exec shell_exec';
		$this->assertTrue( Backup_Utilities::is_function_disabled( 'exec', array( $this, 'ini_get_mock' ) ) );

		$this->disabled = 'shell_exec exec proc_open';
		$this->assertTrue( Backup_Utilities::is_function_disabled( 'exec', array( $this, 'ini_get_mock' ) ) );

	}
}
