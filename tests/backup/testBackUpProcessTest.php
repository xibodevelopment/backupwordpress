<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @extends WP_UnitTestCase
 */
class testBackUpProcessTestCase extends HM_Backup_UnitTestCase {

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
		$this->backup->set_root( dirname( __FILE__ ) . '/test-data' );
		$this->backup->set_path( dirname( __FILE__ ) . '/tmp' );

		hmbkp_rmdirtree( $this->backup->get_path() );
		wp_mkdir_p( dirname( __FILE__ ) . '/tmp' );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 */
	public function tearDown() {

		hmbkp_rmdirtree( $this->backup->get_path() );
		hmbkp_rmdirtree( hmbkp_path() );

		delete_option( 'hmbkp_path' );
		delete_option( 'hmbkp_default_path' );

		@unlink( $this->backup->get_root() . '/new.file' );

		unset( $this->backup );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 */
	public function testFullBackupWithCommands() {

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( 'Empty zip command path' );
		}

        if ( ! $this->backup->get_mysqldump_command_path() ) {
            $this->markTestSkipped( 'Empty mysqldump command path' );
        }

		$this->backup->backup();

		$this->assertEquals( 'zip', $this->backup->get_archive_method() );
		$this->assertEquals( 'mysqldump', $this->backup->get_mysqldump_method() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( 'test-data.txt', $this->backup->get_database_dump_filename() ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

	}

	public function testDeltaBackupWithCommands() {

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( 'Empty zip command path' );
		}

        if ( ! $this->backup->get_mysqldump_command_path() ) {
            $this->markTestSkipped( 'Empty mysqldump command path' );
        }

		$this->backup->backup();

		$this->assertEquals( 'zip', $this->backup->get_archive_method() );
		$this->assertEquals( 'mysqldump', $this->backup->get_mysqldump_method() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( 'test-data.txt', $this->backup->get_database_dump_filename() ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		if ( ! copy( $this->backup->get_archive_filepath(), $this->backup->get_path() . '/delta.zip' ) ) {
		    $this->markTestSkipped( 'Unable to copy backup' );
		}

		// create a new file
		file_put_contents( $this->backup->get_root() . '/new.file', 'test' );

		if ( ! file_exists( $this->backup->get_root() . '/new.file' ) ) {
			$this->markTestSkipped( 'new.file couldn\'t be created' );
		}

		$this->backup->set_archive_filename( 'delta.zip' );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( 'new.file', 'test-data.txt', $this->backup->get_database_dump_filename() ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 5 );

	}

	/**
	 * Test a full backup with the ZipArchive
	 *
	 * @access public
	 */
	public function testFullBackupWithZipArchiveMysqldumpFallback() {

		$this->backup->set_zip_command_path( false );
		$this->backup->set_mysqldump_command_path( false );

		$this->assertTrue( class_exists( 'ZipArchive' ) );

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'ziparchive' );
		$this->assertEquals( $this->backup->get_mysqldump_method(), 'mysqldump_fallback' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( 'test-data.txt', $this->backup->get_database_dump_filename() ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a full backup with the PclZip
	 *
	 * @access public
	 */
	public function testFullBackupWithPclZipAndMysqldumpFallback() {

		$this->backup->set_zip_command_path( false );
		$this->backup->set_mysqldump_command_path( false );

		$this->backup->skip_zip_archive = true;

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'pclzip' );
		$this->assertEquals( $this->backup->get_mysqldump_method(), 'mysqldump_fallback' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( 'test-data.txt', $this->backup->get_database_dump_filename() ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a files only backup with the zip command
	 *
	 * @access public
	 */
	public function testFileOnlyWithZipCommand() {

		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'zip' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a files only backup with ZipArchive
	 *
	 * @access public
	 */
	public function testFileOnlyWithZipArchive() {

		$this->backup->set_type( 'file' );
		$this->backup->set_zip_command_path( false );

		$this->assertTrue( class_exists( 'ZipArchive' ) );

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'ziparchive' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a files only backup with PclZip
	 *
	 * @access public
	 */
	public function testFileOnlyWithPclZip() {

		$this->backup->set_type( 'file' );
		$this->backup->set_zip_command_path( false );

		$this->backup->skip_zip_archive = true;

		$this->backup->backup();

		$this->assertEquals( $this->backup->get_archive_method(), 'pclzip' );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

}