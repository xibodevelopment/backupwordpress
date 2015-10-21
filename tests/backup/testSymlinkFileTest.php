<?php

/**
 * Tests for the Archive process with symlinks
 *
 * @extends WP_UnitTestCase
 */
class testSymlinkFileTestCase extends HM_Backup_UnitTestCase {

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

		if ( ! function_exists( 'symlink' ) ) {
			$this->markTestSkipped( 'symlink function not defined' );
		}

		HM\BackUpWordPress\Path::get_instance()->set_path( dirname( __FILE__ ) . '/tmp' );

		$this->backup = new HM\BackUpWordPress\Backup();
		$this->backup->set_root( dirname( __FILE__ ) . '/test-data/' );
		$this->backup->set_type( 'file' );

		wp_mkdir_p( hmbkp_path() );

		$this->symlink = dirname( __FILE__ ) . '/test-data/' . basename( __FILE__ );

		if ( ! @symlink( __FILE__, $this->symlink ) ) {
			$this->markTestSkipped( 'Couldn\'t create symlink to test with' );
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

		if ( file_exists( $this->symlink ) ) {
			unlink( $this->symlink );
		}

		HM\BackUpWordPress\Path::get_instance()->reset_path();

	}

	/**
	 * Test an unreadable file with the shell commands
	 *
	 */
	public function testArchiveSymlinkFileWithZip() {

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->assertFileExists( $this->symlink );

		$this->backup->zip();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test an unreadable file with the zipArchive commands
	 *
	 */
	public function testArchiveSymlinkFileWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->assertFileExists( $this->symlink );

		$this->backup->zip_archive();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

}