<?php

namespace HM\BackUpWordPress;

abstract class Common_File_Backup_Engine_Tests extends \HM_Backup_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	public function setUp() {
		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );
	}

	public function tearDown() {

		if ( file_exists( Path::get_root() . '/exclude' ) ) {
			chmod( Path::get_root() . '/exclude', 0755 );
		}

		if ( file_exists( Path::get_root() . '/test-data.txt' ) ) {
			chmod( Path::get_root() . '/test-data.txt', 0644 );
		}

		if ( file_exists( $this->hidden ) ) {
			unlink( $this->hidden );
		}

		if ( file_exists( $this->symlink ) || is_link( $this->symlink ) ) {
			unlink( $this->symlink );
		}

		$this->cleanup_test_data();

	}

	public function test_backup() {

		$this->assertTrue( $this->backup->backup() );
		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 3 );

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
			$this->markTestSkipped( 'File was readable' );
		}

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'test-data.txt' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

	}

	public function test_backup_with_unreadable_directory() {

		chmod( Path::get_root() . '/exclude', 0220 );

		if ( is_readable( Path::get_root() . '/exclude' ) ) {
			$this->markTestSkipped( 'Directory was readable.' );
		}

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 1 );

	}

	public function test_file_with_strange_characters() {

		file_put_contents( PATH::get_root() . '/Groß.jpg', '' );

		$this->backup->backup();

		$this->assertFileExists( PATH::get_root() . '/Groß.jpg' );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( 'Groß.jpg' ) );

		unlink( PATH::get_root() . '/Groß.jpg' );

	}

	public function test_corrupt_jpeg() {

		copy( HMBKP_PLUGIN_PATH . 'tests/corrupted.jpg', PATH::get_root() . '/corrupted.jpg' );

		$this->assertFileExists( PATH::get_root() . '/corrupted.jpg' );
		$this->assertFalse( @imagecreatefromjpeg( PATH::get_root() . '/corrupted.jpg' ) );

		$this->assertTrue( $this->backup->backup() );
		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( 'corrupted.jpg' ) );

		unlink( PATH::get_root() . '/corrupted.jpg' );

	}

	public function test_adding_files_to_existing_backup() {

		$this->backup->backup();
		$filepath = $this->backup->get_backup_filepath();

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( 'test-data.txt', 'exclude', 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 3 );

		// create a new file
		if ( ! file_put_contents( Path::get_root() . '/new.file', 'test' ) ) {
			$this->markTestSkipped( 'new.file couldn\'t be created' );
		}

		$this->backup->backup();

		$this->assertEquals( $filepath, $this->backup->get_backup_filepath() );
		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( 'new.file' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 4 );

	}

	/**
	 * Test a complete backup of the WordPress Site
	 *
	 * @group full-backup
	 */
	public function test_complete_file_backup_with_excludes() {

		// Reset root back to defaults
		Path::get_instance()->set_root( false );

		$this->backup->set_excludes( new Excludes( array( 'wp-*' ) ) );
		$this->backup->backup();

		$finder = $this->backup->get_files();

		foreach ( $finder as $file ) {
			$files[] = $file->getRelativePathname();
		}

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), iterator_count( $finder ) );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), $files );

	}

	/**
	 * Test a complete backup of the WordPress Site
	 *
	 * @group full-backup
	 */
	public function test_complete_file_backup() {

		// Reset root back to defaults
		Path::get_instance()->set_root( false );

		$this->backup->backup();

		$finder = $this->backup->get_files();

		foreach ( $finder as $file ) {
			$files[] = $file->getRelativePathname();
		}

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), iterator_count( $finder ) );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), $files );

	}
}
