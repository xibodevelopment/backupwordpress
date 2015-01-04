<?php

/**
 * Test the the property getters works
 *
 * @extends WP_UnitTestCase
 */
class testPropertiesTestCase extends HM_Backup_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	/**
	 * Setup the backup object
	 *
	 * @access public
	 */
	public function setUp() {

		$this->backup = new HM\BackUpWordPress\Backup();
		$this->backup->set_type( 'database' );

		$this->custom_path = WP_CONTENT_DIR . '/custom';

		wp_mkdir_p( $this->custom_path );

	}

	public function tearDown() {

		hmbkp_rmdirtree( hmbkp_path() );

		unset( $this->backup );

		HM\BackUpWordPress\Path::get_instance()->reset_path();

	}

	/**
	 * What if the backup path is in root
	 *
	 * @access public
	 */
	public function testRootBackupPath() {

		HM\BackUpWordPress\Path::get_instance()->set_path( '/' );
		$this->backup->set_archive_filename( 'backup.zip' );

		$this->assertEquals( '/', hmbkp_path() );

		$this->assertEquals( '/backup.zip', $this->backup->get_archive_filepath() );

		if ( ! is_writable( hmbkp_path() ) ) {
			$this->markTestSkipped( 'Root not writable' );
		}

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

	}

	/**
	 * Make sure setting a custom path + archive filename correctly sets the archive filepath
	 *
	 * @access public
	 */
	public function testCustomBackupPath() {

		HM\BackUpWordPress\Path::get_instance()->set_path( WP_CONTENT_DIR . '/custom' );

		$this->backup->set_archive_filename( 'backup.zip' );

		$this->assertEquals( HM\BackUpWordPress\Backup::conform_dir( WP_CONTENT_DIR . '/custom/backup.zip' ), $this->backup->get_archive_filepath() );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

	}

	/**
	 * Make sure setting a custom path + database dump filename correctly sets the database dump filepath
	 *
	 * @access public
	 */
	public function testCustomDatabaseDumpPath() {

		HM\BackUpWordPress\Path::get_instance()->set_path( WP_CONTENT_DIR . '/custom' );

		$this->backup->set_database_dump_filename( 'dump.sql' );

		$this->assertEquals( HM\BackUpWordPress\Backup::conform_dir( WP_CONTENT_DIR . '/custom/dump.sql' ), $this->backup->get_database_dump_filepath() );

		$this->backup->dump_database();

		$this->assertFileExists( $this->backup->get_database_dump_filepath() );

	}

}