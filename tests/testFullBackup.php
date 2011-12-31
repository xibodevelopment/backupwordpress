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
	
		$this->time = time();

		$this->backup = new HM_Backup();
		
		remove_action( 'hmbkp_backup_started', 'hmbkp_set_status', 10, 0 );
		remove_action( 'hmbkp_mysqldump_started', 'hmbkp_set_status_dumping_database' );
		remove_action( 'hmbkp_archive_started', 'hmbkp_set_status_archiving' );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 * @return null
	 */
	function tearDown() {

		if ( file_exists( $this->backup->archive_filepath() ) )
			unlink( $this->backup->archive_filepath() );
			
		echo ( time() - $this->time ) . ' Seconds ';

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithZip() {

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( 'Empty zip command path' );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$files = $this->backup->files();
		$files[] = $this->backup->database_dump_filename;

		$this->assertArchiveContains( $this->backup->archive_filepath(), $files );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), count( $files ) );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithZipArchive() {

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$files = $this->backup->files();
		$files[] = $this->backup->database_dump_filename;

		$this->assertArchiveContains( $this->backup->archive_filepath(), $files );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), count( $files ) );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithPclZip() {

		$this->backup->zip_command_path = false;
		$this->backup->skip_zip_archive = true;

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$files = $this->backup->files();
		$files[] = $this->backup->database_dump_filename;

		$this->assertArchiveContains( $this->backup->archive_filepath(), $files );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), count( $files ) );

	}

}