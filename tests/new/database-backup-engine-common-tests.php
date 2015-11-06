<?php

abstract class Database_Backup_Engine_Common_Tests extends \HM_Backup_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	public function tearDown() {

		if ( file_exists( $this->backup->get_backup_filepath() ) ) {
			unlink( $this->backup->get_backup_filepath() );
		}

	}

	public function test_backup() {

		$this->assertTrue( $this->backup->backup() );
		$this->assertFileExists( $this->backup->get_backup_filepath() );

	}

}