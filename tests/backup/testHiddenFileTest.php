<?php

/**
 * Tests for the Archive process with symlinks
 *
 * @extends WP_UnitTestCase
 */
class testHiddenFileTestCase extends HM_Backup_UnitTestCase {

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
	 */
	public function setUp() {

		HM\BackUpWordPress\Path::get_instance()->set_path( dirname( __FILE__ ) . '/tmp' );

		$this->backup = new HM\BackUpWordPress\Backup();
		$this->backup->set_root( dirname( __FILE__ ) . '/test-data/' );
		$this->backup->set_type( 'file' );

		wp_mkdir_p( hmbkp_path() );

		$this->hidden = dirname( __FILE__ ) . '/test-data/' . '.hidden';

		if ( ! @file_put_contents( $this->hidden, 'test' ) ) {
			$this->markTestSkipped( 'Couldn\'t create hidden file to test with' );
		}

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 */
	public function tearDown() {

		hmbkp_rmdirtree( hmbkp_path() );

		unset( $this->backup );

		if ( file_exists( $this->hidden ) ) {
			unlink( $this->hidden );
		}

		HM\BackUpWordPress\Path::get_instance()->reset_path();

	}

	/**
	 * Test an unreadable file with the shell commands
	 *
	 */
	public function testArchiveHiddenFileWithZip() {

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->assertFileExists( $this->hidden );

		$this->backup->zip();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->hidden ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test an unreadable file with the zipArchive commands
	 *
	 */
	public function testArchiveHiddenFileWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->assertFileExists( $this->hidden );

		$this->backup->zip_archive();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->hidden ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test an unreadable file with the PclZip commands
	 *
	 */
	public function testArchiveHiddenFileWithPclZip() {

		$this->backup->set_zip_command_path( false );

		$this->assertFileExists( $this->hidden );

		$this->backup->pcl_zip();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->hidden ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

}