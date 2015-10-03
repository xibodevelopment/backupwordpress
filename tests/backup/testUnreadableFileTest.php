<?php

/**
 * Tests for the Archive process with unreadable files
 *
 * @extends WP_UnitTestCase
 */
class testUnreadableFileTestCase extends HM_Backup_UnitTestCase {

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

		chmod( $this->backup->get_root() . '/test-data.txt', 0220 );

		if ( is_readable( $this->backup->get_root() . '/test-data.txt' ) ) {
			$this->markTestSkipped( "File was readable." );
		}

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 */
	public function tearDown() {

		hmbkp_rmdirtree( hmbkp_path() );

		chmod( $this->backup->get_root() . '/test-data.txt', 0664 );

		unset( $this->backup );

		HM\BackUpWordPress\Path::get_instance()->reset_path();

	}

	/**
	 * Test an unreadable file with the shell commands
	 *
	 */
	public function testArchiveUnreadableFileWithZip() {

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();

		$this->assertEmpty( $this->backup->get_errors() );

		$this->assertNotEmpty( $this->backup->get_warnings() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

	}

	/**
	 * Test an unreadable file with the zipArchive commands
	 *
	 */
	public function testArchiveUnreadableFileWithZipArchive() {

		$this->backup->set_zip_command_path( false );

		$this->backup->zip_archive();

		$this->assertEmpty( $this->backup->get_errors() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

	}

}