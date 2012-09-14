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
	 */
	public function setUp() {

		$this->backup = new HM_Backup();
		$this->backup->set_excludes( '.git/' );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 */
	public function tearDown() {

		if ( file_exists( $this->backup->get_archive_filepath() ) )
			unlink( $this->backup->get_archive_filepath() );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 */
	public function testFullBackupWithZip() {

		if ( ! $this->backup->get_zip_command_path() )
			$this->markTestSkipped( 'Empty zip command path' );

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'zip' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$files = $this->backup->get_included_files();
		$files[] = $this->backup->get_database_dump_filename();

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), $files, $this->backup->get_root() );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), count( $files ) );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a full backup with the ZipArchive
	 *
	 * @access public
	 */
	public function testFullBackupWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'ziparchive' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$files = $this->backup->get_included_files();
		$files[] = $this->backup->get_database_dump_filename();

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), $files, $this->backup->get_root() );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), count( $files ) );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a full backup with PclZip
	 *
	 * @access public
	 */
	public function testFullBackupWithPclZip() {

		$this->backup->set_zip_command_path( false );
		$this->backup->skip_zip_archive = true;

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'pclzip' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$files = $this->backup->get_included_files();
		$files[] = $this->backup->get_database_dump_filename();

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), $files, $this->backup->get_root() );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), count( $files ) );

		$this->assertEmpty( $this->backup->get_errors() );

	}

}