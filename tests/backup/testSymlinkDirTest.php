<?php

/**
 * Tests for the Archive process with symlinks
 *
 * @extends WP_UnitTestCase
 */
class testSymlinkDirTestCase extends HM_Backup_UnitTestCase {

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

		if ( ! function_exists( 'symlink' ) )
			$this->markTestSkipped( 'symlink function not defined' );

		$this->backup = new HM_Backup();
		$this->backup->set_root( dirname( __FILE__ ) . '/test-data/' );
		$this->backup->set_path( dirname( __FILE__ ) . '/tmp' );
		$this->backup->set_type( 'file' );

		wp_mkdir_p( $this->backup->get_path() );

		$this->symlink = dirname( __FILE__ ) . '/test-data/tests';

		if ( ! @symlink( dirname( __FILE__ ) . '/test-data-symlink/', $this->symlink ) )
			$this->markTestSkipped( 'Couldn\'t create symlink to test with' );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 */
	public function tearDown() {

		if ( ! function_exists( 'symlink' ) )
			return;

		hmbkp_rmdirtree( $this->backup->get_path() );
		hmbkp_rmdirtree( hmbkp_path() );

		delete_option( 'hmbkp_path' );
		delete_option( 'hmbkp_default_path' );

		unset( $this->backup );

		if ( file_exists( $this->symlink ) )
			unlink( $this->symlink );

	}

	/**
	 * Test an unreadable file with the shell commands
	 *
	 * @access public
	 */
	public function testArchiveSymlinkDirWithZip() {

		if ( ! $this->backup->get_zip_command_path() )
			$this->markTestSkipped( "Empty zip command path" );

		$this->assertFileExists( $this->symlink );

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 5 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test an unreadable file with the zipArchive commands
	 *
	 * @access public
	 */
	public function testArchiveSymlinkDirWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->assertFileExists( $this->symlink );

		$this->backup->zip_archive();

		$this->assertFileExists( $this->backup->get_archive_filepath() );
		$this->assertEquals( 'ziparchive', $this->backup->get_archive_method() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 5 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test an unreadable file with the PclZip commands
	 *
	 * @access public
	 */
	public function testArchiveSymlinkDirWithPclZip() {

		$this->backup->set_zip_command_path( false );

		$this->assertFileExists( $this->symlink );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 5 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

}