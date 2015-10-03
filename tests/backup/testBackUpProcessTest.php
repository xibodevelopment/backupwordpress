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
	 */
	public function setUp() {

		HM\BackUpWordPress\Path::get_instance()->set_path( dirname( __FILE__ ) . '/tmp' );

		$this->backup = new HM\BackUpWordPress\Backup();

		$this->backup->set_root( dirname( __FILE__ ) . '/test-data' );

		wp_mkdir_p( dirname( __FILE__ ) . '/tmp' );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 */
	public function tearDown() {

		hmbkp_rmdirtree( hmbkp_path() );

		@unlink( $this->backup->get_root() . '/new.file' );

		HM\BackUpWordPress\Path::get_instance()->reset_path();

	}

	/**
	 * Test a full backup with the shell commands
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

		if ( ! copy( $this->backup->get_archive_filepath(), hmbkp_path() . '/delta.zip' ) ) {
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
	 * Test a files only backup with the zip command
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

}