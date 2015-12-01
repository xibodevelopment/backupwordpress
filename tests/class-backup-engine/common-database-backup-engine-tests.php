<?php

namespace HM\BackUpWordPress;

abstract class Common_Database_Backup_Engine_Tests extends \HM_Backup_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	public function setUp() {
		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );
	}

	public function tearDown() {
		$this->cleanup_test_data();
	}

	public function test_backup() {

		$this->assertTrue( $this->backup->backup() );
		$this->assertFileExists( $this->backup->get_backup_filepath() );

	}

}
