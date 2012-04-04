<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @extends WP_UnitTestCase
 */
class testFullBackUpTestCase extends WP_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	/**
	 * Setup the backup object and create the tmp directory
	 *
	 * @access public
	 * @return null
	 */
	function setUp() {

		$this->backup = new HM_Backup();

		remove_action( 'hmbkp_backup_started', 'hmbkp_set_status', 10, 0 );
		remove_action( 'hmbkp_mysqldump_started', 'hmbkp_set_status_dumping_database' );
		remove_action( 'hmbkp_archive_started', 'hmbkp_set_status_archiving' );
		remove_action( 'hmbkp_backup_complete', 'hmbkp_backup_complete' );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 * @return null
	 */
	function tearDown() {

		if ( file_exists( $this->backup->get_archive_filepath() ) )
			unlink( $this->backup->get_archive_filepath() );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithZip() {

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( 'Empty zip command path' );

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'zip' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$files = $this->backup->get_files();
		$files[] = $this->backup->get_database_dump_filename();

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), $files );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), count( $files ) );

		$this->assertEmpty( $this->backup->errors() );

	}

	/**
	 * Test a full backup with the ZipArchive
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithZipArchive() {

	  	$this->backup->set_zip_command_path( false );

	  	$this->backup->backup();

	  	$this->assertEquals( $this->backup->get_archive_method(), 'ziparchive' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

	 	$files = $this->backup->get_files();
	 	$files[] = $this->backup->get_database_dump_filename();

	 	$this->assertArchiveContains( $this->backup->get_archive_filepath(), $files );
	 	$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), count( $files ) );

	 	$this->assertEmpty( $this->backup->errors() );

	}

	/**
	 * Test a full backup with PclZip
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithPclZip() {

		$this->backup->set_zip_command_path( false );
		$this->backup->skip_zip_archive = true;

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'pclzip' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$files = $this->backup->get_files();
		$files[] = $this->backup->get_database_dump_filename();

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), $files );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), count( $files ) );

		$this->assertEmpty( $this->backup->errors() );

	}

}