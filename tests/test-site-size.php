<?php

namespace HM\BackUpWordPress;

class Site_Size_Tests extends \HM_Backup_UnitTestCase {

	public function setUp() {
		$this->size = new Site_Size;
		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );
		$this->root = new \SplFileInfo( Path::get_root() );
	}

	public function tearDown() {
		if ( file_exists( Path::get_path() . '/.files' ) ) {
			unlink( Path::get_path() . '/.files' );
		}
		$this->cleanup_test_data();
		delete_transient( 'hmbkp_directory_filesizes_running' );
	}

	public function test_rebuild_directory_filesizes() {

		$this->assertNull( $this->size->filesize( $this->root ) );
		$this->size->recursive_filesize_scanner();
		$this->assertNotEmpty( $this->size->filesize( $this->root ), '', 40 );

	}

	public function test_filesize_excludes() {
		$this->size = new Site_Size( 'file', new Excludes( 'exclude' ) );
		$this->size->recursive_filesize_scanner();
		$this->assertNotEmpty( $this->size->get_site_size() );
	}


	public function test_lock() {

		$this->assertFalse( $this->size->is_site_size_being_calculated() );
		$this->size->rebuild_directory_filesizes();
		$this->assertTrue( $this->size->is_site_size_being_calculated() );
		$this->assertFalse( $this->size->rebuild_directory_filesizes() );

	}

	public function test_is_site_cached() {
		$this->assertFalse( $this->size->is_site_size_cached() );
		$this->size->rebuild_directory_filesizes();
		$this->assertTrue( $this->size->is_site_size_being_calculated() );
	}

	public function test_old_cache_is_cleared() {

		$this->assertFalse( $this->size->is_site_size_cached() );
		$this->size = new Site_Size( 'file' );
		$this->size->recursive_filesize_scanner();
		$this->assertTrue( $this->size->is_site_size_cached() );

		// Set the filemtime to over a week ago
		touch( PATH::get_path() . '/.files', time() - WEEK_IN_SECONDS - 10 );
		clearstatcache();

		$this->assertFalse( $this->size->is_site_size_cached() );

	}

	public function test_site_size_file() {
		$this->size = new Site_Size( 'file' );
		$this->size->recursive_filesize_scanner();
		$this->assertNotEmpty( $this->size->get_site_size() );
	}

	public function test_site_size_formatted() {
		$this->size = new Site_Size( 'file' );
		$this->size->recursive_filesize_scanner();
		$this->assertNotEmpty( $this->size->get_formatted_site_size() );
	}

	public function test_site_size_database() {
		$size_database = new Site_Size( 'database' );
		$this->assertNotEmpty( $size_database->get_site_size() );
	}

	public function test_site_size_without_filescanner_complete_equals_database() {

		$size_complete = $this->size;
		$size_database = new Site_Size( 'database' );

		$this->assertEquals( $size_complete->get_site_size(), $size_database->get_site_size() );

	}

	public function test_site_size_with_filescanner_complete_equals_database_plus_files() {

		$this->size->recursive_filesize_scanner();

		$size_complete = $this->size;
		$size_database = new Site_Size( 'database' );
		$size_file = new Site_Size( 'file' );

		$this->assertNotEmpty( $size_database->get_site_size() );
		$this->assertNotEmpty( $size_file->get_site_size() );

		$this->assertEquals( $size_complete->get_site_size(), $size_database->get_site_size() + $size_file->get_site_size() );

	}

	/**
	 * File is excluded directly (either in the root or any non-excluded sub-directory).
	 *
	 * Main test: Site_Size->filesize( $file )
	 * Expected:  file size should be 0.
	 */
	public function test_file_size_excluded_directly() {

		$file_path =  $this->test_data . '/test-data.txt';
		$file      = new \SplFileInfo( $file_path );

		// Check the file is created and its size is NOT 0.
		$this->assertContains( $this->root->getPath(), $file->getPath() );
		$this->assertNotSame( $file->getSize(), 0 );

		// Exclude file directly - file size should be 0.
		$excluded_file_site_size = new Site_Size( 'file', new Excludes( $file_path ) );
		$this->assertSame( $excluded_file_site_size->filesize( $file ), 0 );
	}

	/**
	 * File is excluded as a result of being in an excluded directory.
	 *
	 * Main test: Site_Size->filesize( $file )
	 * Expected:  file size should be 0.
	 */
	public function test_file_size_excluded_via_parent_directory() {

		$file_path = $this->test_data . '/test-data.txt';
		$file      = new \SplFileInfo( $file_path );

		// Check the file is created and its size is NOT 0.
		$this->assertContains( $this->root->getPath(), $file->getPath() );
		$this->assertNotSame( $file->getSize(), 0 );

		// Exclude the parent directory, so the file in it is excluded by "inheritance" - file size should be 0.
		$excluded_dir_name      = basename( $file->getPath() ); // test-data directory, the parent dir of the file.
		$excluded_dir           = new Excludes( $excluded_dir_name );
		$excluded_dir_site_size = new Site_Size( 'file', $excluded_dir );

		// Check the directory is excluded. File size in that directory should return 0.
		$this->assertContains( $excluded_dir_name, $excluded_dir->get_user_excludes() );
		$this->assertSame( $excluded_dir_site_size->filesize( $file ), 0 );
	}

	/**
	 * File is NOT excluded directly (either in the root or any non-excluded sub-directory).
	 *
	 * Main test: Site_Size->filesize( $file )
	 * Expected:  file size should be what it is.
	 */
	public function test_file_size_not_excluded_directly() {

		$file_path =  $this->test_data . '/test-data.txt';
		$file      = new \SplFileInfo( $file_path );

		// Check the file is created and its size is NOT 0.
		$this->assertContains( $this->root->getPath(), $file->getPath() );
		$this->assertNotSame( $file->getSize(), 0 );

		// Check file size via BWP function. It should NOT be 0, should be the same size as via getSize().
		$site_size = new Site_Size( 'file' );
		$this->assertNotSame( $site_size->filesize( $file ), 0 );
		$this->assertSame( $site_size->filesize( $file ), $file->getSize() );
	}

}
