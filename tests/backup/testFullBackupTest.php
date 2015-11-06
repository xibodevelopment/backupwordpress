<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @group full-backup
 * @extends WP_UnitTestCase
 */
class testFullBackUpTestCase extends HM_Backup_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;
	protected $path;

	/**
	 * Setup the backup object and create the tmp directory
	 *
	 */
	public function setUp() {

		$this->backup = new HM\BackUpWordPress\Backup();

		if ( defined( 'HMBKP_PATH' ) ) {
			$this->markTestSkipped( 'Skipped because of defines' );
		}

		$this->path = HM\BackUpWordPress\Path::get_instance();

		// Cleanup before we kickoff in-case theirs cruft around from previous failures
		$this->tearDown();

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 */
	public function tearDown() {

		// Remove all backup paths that exist
		foreach( $this->path->get_existing_paths() as $path ) {
			hmbkp_rmdirtree( $path );
		}

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 */
	public function testFullBackupWithZip() {

		if ( ! $this->backup->get_zip_command_path() ) {
			$this->markTestSkipped( 'Empty zip command path' );
		}

		$this->backup->backup();

		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$files = $this->backup->get_included_files();
		$files[] = $this->backup->get_database_dump_filename();

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), $files, Path::get_root() );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), count( $files ) );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a full backup with the ZipArchive
	 *
	 * @group pathTest
	 */
	public function testFullBackupWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->backup->backup();

		$this->assertEquals( 'ziparchive', $this->backup->get_archive_method() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$files = $this->backup->get_included_files();
		$files[] = $this->backup->get_database_dump_filename();

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), $files, Path::get_root() );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), count( $files ) );

		$this->assertEmpty( $this->backup->get_errors() );

	}

}