<?php

namespace HM\BackUpWordPress;

class Mysqldump_Database_Backup_Engine_Tests extends Common_Database_Backup_Engine_Tests {

	protected $backup;

	public function setUp() {
		$this->backup = new Mysqldump_Database_Backup_Engine;
        if ( ! $this->backup->get_mysqldump_executable_path() ) {
            $this->markTestSkipped( 'mysqldump not available' );
        }
        parent::setUp();
	}

	public function test_default_command_path() {
		$this->assertEquals( 'mysqldump', $this->backup->get_mysqldump_executable_path() );
	}

	public function test_check_user_can_connect_to_database_via_cli() {
		$this->assertNotWPError( $this->backup->check_user_can_connect_to_database_via_cli() );
	}

}
