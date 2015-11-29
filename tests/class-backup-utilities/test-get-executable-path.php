<?php

namespace HM\BackUpWordPress;

class Backup_Engine_Get_Executable_Path_Tests extends \HM_Backup_UnitTestCase {

	public function setUp() {

		$paths = array(
			'mysql',
			'/usr/bin/mysqlnope',
			'/usr/bin/mysqlwrong',
			'/usr/bin/mysqltryagain',
		);

		$this->paths = array_combine( $paths, $paths );

	}

	public function test_can_pick_first_path() {
		$this->assertEquals( 'mysql', Backup_Utilities::get_executable_path( $this->paths ) );
	}

	public function test_can_pick_shuffled_command_path() {

		$paths = $this->paths;
		shuffle( $paths );

		$this->assertEquals( 'mysql', Backup_Utilities::get_executable_path( $paths ) );

	}

	public function test_remove_default_command_path() {

		$paths = $this->paths;
		$paths[] = $path = trim( shell_exec( 'which mysql' ) );
		unset( $paths['mysql'] );
		shuffle( $paths );

		$this->assertEquals( $path, Backup_Utilities::get_executable_path( $paths ) );

	}

}