<?php

/**
 * Tests for the Archive process with symlinks
 *
 * @extends WP_UnitTestCase
 */
class testBrokenSymlinkTestCase extends HM_Backup_UnitTestCase {

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

		$this->symlink = dirname( __FILE__ ) . '/test-data/' . basename( __FILE__ );

		file_put_contents( dirname( __FILE__ ) . '/test-data/symlink', '' );

		$symlink_created = @symlink( dirname( __FILE__ ) . '/test-data/symlink', $this->symlink );
		
		unlink( dirname( __FILE__ ) . '/test-data/symlink' );
		
		if ( ! $symlink_created )		
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

		@unlink( $this->symlink );

	}

	/**
	 * Test a broken symlink with the shell commands
	 *
	 * @access public
	 */
	public function testArchiveBrokenSymlinkWithZip() {

		if ( ! $this->backup->get_zip_command_path() )
            $this->markTestSkipped( "Empty zip command path" );

		$this->assertFileNotExists( $this->symlink );
		$this->assertTrue( is_link( $this->symlink ) );

		$this->backup->zip();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a broken symlink with the zipArchive commands
	 *
	 * @access public
	 */
	public function testArchiveBrokenSymlinkWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->assertFileNotExists( $this->symlink );
		$this->assertTrue( is_link( $this->symlink ) );

		$this->backup->zip_archive();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	/**
	 * Test a broken symlink with the PclZip commands
	 *
	 * @access public
	 */
	public function testArchiveBrokenSymlinkWithPclZip() {

		$this->markTestSkipped( "PclZip can't handle broken symlinks" );

		$this->backup->set_zip_command_path(  false );

		$this->assertFileNotExists( $this->symlink );
		$this->assertTrue( is_link( $this->symlink ) );

		$this->backup->pcl_zip();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 3 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

}