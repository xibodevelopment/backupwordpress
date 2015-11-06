<?php

namespace HM\BackUpWordPress;

abstract class File_Backup_Engine_Common_Tests extends \HM_Backup_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	public function setUp() {

		$this->setup_test_data();

		Path::get_instance()->set_path( dirname( __DIR__ ) . '/tmp' );
		$this->backup->set_root( $this->test_data );

	}

	public function tearDown() {

		hmbkp_rmdirtree( Path::get_path() );

		chmod( Path::get_root() . '/exclude', 0755 );
		hmbkp_rmdirtree( $this->test_data );
		hmbkp_rmdirtree( $this->test_data_symlink );

		if ( file_exists( $this->hidden ) ) {
			unlink( $this->hidden );
		}

		if ( file_exists( $this->symlink ) || is_link( $this->symlink ) ) {
			unlink( $this->symlink );
		}

	}

	public function test_backup() {

		$this->assertTrue( $this->backup->backup() );
		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 3 );

	}

	public function test_full_backup() {

		$this->markTestSkipped();

		$this->backup->set_root( $this->backup->get_home_path() );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$files = $this->backup->get_files();

		$this->assertArchiveContains( $this->backup->get_backup_filepath(), $files, Path::get_root() );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), count( $files ) );

	}

	/**
	 * Test an unreadable file with the shell commands
	 *
	 */
	public function test_backup_with_hidden_file() {

		$this->hidden = $this->test_data . '/.hidden';

		if ( ! file_put_contents( $this->hidden, 'test' ) ) {
			$this->markTestSkipped( 'Couldn\'t create hidden file to test with' );
		}

		$this->assertFileExists( $this->hidden );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( basename( $this->hidden ) ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 4 );

	}

	public function test_backup_with_symlink_directory() {

		if ( ! function_exists( 'symlink' ) ) {
			$this->markTestSkipped( 'symlink function not defined' );
		}

		$this->symlink = $this->test_data . '/tests';

		if ( ! symlink( trailingslashit( $this->test_data_symlink ), $this->symlink ) ) {
			$this->markTestSkipped( 'Couldn\'t create symlink to test with' );
		}

		$this->assertFileExists( $this->symlink );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 5 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	public function test_backup_with_symlink_file() {

		if ( ! function_exists( 'symlink' ) ) {
			$this->markTestSkipped( 'symlink function not defined' );
		}

		$this->symlink = trailingslashit( $this->test_data ) . basename( __FILE__ );

		if ( ! symlink( __FILE__, $this->symlink ) ) {
			$this->markTestSkipped( 'Couldn\'t create symlink to test with' );
		}

		$this->assertFileExists( $this->symlink );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 4 );

		$this->assertEmpty( $this->backup->get_errors() );

	}


	public function test_backup_with_broken_symlink() {

		if ( ! function_exists( 'symlink' ) ) {
			$this->markTestSkipped( 'symlink function not defined' );
		}

		$this->symlink = trailingslashit( $this->test_data ) . basename( __FILE__ );
		file_put_contents( $this->test_data . '/symlink', '' );
		$symlink_created = symlink( $this->test_data . '/symlink', $this->symlink );
		unlink( $this->test_data . '/symlink' );

		if ( ! $symlink_created ) {
			$this->markTestSkipped( 'Couldn\'t create symlink to test with' );
		}

		$this->assertFileNotExists( $this->symlink );
		$this->assertTrue( is_link( $this->symlink ) );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( basename( $this->symlink ) ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 3 );

		$this->assertEmpty( $this->backup->get_errors() );

	}

	public function test_backup_with_unreadable_file() {

		chmod( Path::get_root() . '/test-data.txt', 0220 );

		if ( is_readable( Path::get_root() . '/test-data.txt' ) ) {
			$this->markTestSkipped( "File was readable." );
		}

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

	}

	public function test_backup_with_unreadable_directory() {

		chmod( Path::get_root() . '/exclude', 0220 );

		if ( is_readable( Path::get_root() . '/exclude' ) ) {
			$this->markTestSkipped( "Directory was readable." );
		}

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 1 );

	}

}