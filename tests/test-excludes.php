<?php

namespace HM\BackUpWordPress;

class Backup_Engine_Excludes extends \HM_Backup_UnitTestCase {

	public function setUp() {

		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );
		$this->root = new \SplFileInfo( Path::get_root() );

		$this->backup = new Mock_File_Backup_Engine;
	}

	public function tearDown() {
		$this->cleanup_test_data();
	}

	public function testBackUpDirIsExcludedWhenBackUpDirIsInRoot() {

		$excludes = new Excludes;

		$this->assertContains( Path::get_root(), Path::get_path() );
		$this->assertContains( str_replace( trailingslashit( Path::get_root() ), '', Path::get_path() ), $excludes->get_excludes() );

	}

	public function testNoExcludesExceptDefaults() {

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'exclude', $files );
		$this->assertContains( 'exclude/exclude.exclude', $files );
		$this->assertContains( 'test-data.txt', $files );

		$this->assertCount( 3, $files );

	}

	public function testExcludeAbsoluteDirPath() {

		$this->backup->set_excludes( new Excludes( '/exclude/' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertCount( 1, $files );


	}

	public function testExcludeAbsoluteRootDirPath() {

		$this->backup->set_excludes( new Excludes( $this->test_data . '/exclude/' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertCount( 1, $files );

	}

	public function testExcludeDirPathFragment() {

		$this->backup->set_excludes( new Excludes( 'exclude/' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertCount( 1, $files );

	}

	public function testExcludeAmbiguousAbsoluteDirPath() {

		$this->backup->set_excludes( new Excludes( 'exclude' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertCount( 1, $files );

	}

	public function testExcludeAbsoluteFilePath() {

		$this->backup->set_excludes( new Excludes( '/exclude/exclude.exclude' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAmbiguousAbsoluteFilePath() {

		$this->backup->set_excludes( new Excludes( 'exclude/exclude.exclude' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAbsolutePathWithWildcardFile() {

		$this->backup->set_excludes( new Excludes( '/exclude/*' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFile() {

		$this->backup->set_excludes( new Excludes( 'exclude/*' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeWildcardFileName() {

		$this->backup->set_excludes( new Excludes( '*.exclude' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAbsolutePathWithWildcardFileName() {

		$this->backup->set_excludes( new Excludes( '/exclude/*.exclude' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileName() {

		$this->backup->set_excludes( new Excludes( 'exclude/*.exclude' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeWildcardFileExtension() {

		$this->backup->set_excludes( new Excludes( 'exclude.*' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAbsolutePathWithWildcardFileExtension() {

		$this->backup->set_excludes( new Excludes( '/exclude/exclude.*' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileExtension() {

		$this->backup->set_excludes( new Excludes( 'exclude/exclude.*' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludePartialFilename() {

		$this->backup->set_excludes( new Excludes( 'test-*' ) );

		$files = $this->get_and_prepare_files();

		$this->assertNotContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludePartialDirectory() {

		$this->backup->set_excludes( new Excludes( 'excl*' ) );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertNotContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 1, $files );

	}

	public function testWildCard() {

		$this->backup->set_excludes( new Excludes( '*' ) );

		$files = $this->get_and_prepare_files();

		$this->assertEmpty( $files );

	}

	/**
	 * File is excluded directly (either in the root or any non-excluded sub-directory).
	 *
	 * Main test: Excludes->is_file_excluded( $file )
	 * Expected:  true.
	 */
	public function test_excluded_file_directly_is_excluded() {

		$file_path =  $this->test_data . '/test-data.txt';
		$file      = new \SplFileInfo( $file_path );

		// Check the file is created and its size is NOT 0.
		$this->assertContains( $this->root->getPath(), $file->getPath() );
		$this->assertNotSame( $file->getSize(), 0 );

		// Exclude file directly.
		$excluded_file = new Excludes( $file_path );

		// Check the file is excluded - true.
		$this->assertContains( basename( $file->getPathname() ), $excluded_file->get_user_excludes() );
		$this->assertSame( $excluded_file->is_file_excluded( $file ), true );
	}

	/**
	 * File is excluded as a result of being in an excluded directory.
	 *
	 * Main test: Excludes->is_file_excluded( $file )
	 * Expected:  true.
	 */
	public function test_excluded_file_via_parent_directory_is_excluded() {

		$file_path = $this->test_data . '/test-data.txt';
		$file      = new \SplFileInfo( $file_path );

		// Check the file is created and its size is NOT 0.
		$this->assertContains( $this->root->getPath(), $file->getPath() );
		$this->assertNotSame( $file->getSize(), 0 );

		// Exclude the parent directory, so the file in it is excluded by "inheritance".
		$excluded_dir_name = basename( $file->getPath() ); // test-data directory, the parent dir of the file.
		$excluded_dir      = new Excludes( $excluded_dir_name );

		// Check the directory is excluded. File in that directory should be excluded too.
		$this->assertContains( $excluded_dir_name, $excluded_dir->get_user_excludes() );
		$this->assertSame( $excluded_dir->is_file_excluded( $file ), true );
	}

	/**
	 * File is NOT excluded directly (either in the root or any non-excluded sub-directory).
	 *
	 * Main test: Excludes->is_file_excluded( $file )
	 * Expected:  false.
	 */
	public function test_non_excluded_file_is_excluded() {

		$file_path =  $this->test_data . '/test-data.txt';
		$file      = new \SplFileInfo( $file_path );

		// Check the file is created and its size is NOT 0.
		$this->assertContains( $this->root->getPath(), $file->getPath() );
		$this->assertNotSame( $file->getSize(), 0 );

		// Do NOT exclude the parent directory, so the file in it is also non excluded by "inheritance".
		$non_excluded_dir_name = basename( $file->getPath() ); // test-data directory, the parent dir of the file.
		$non_excluded_dir      = new Excludes();

		// Check the directory is NOT excluded. File in that directory should be NOT excluded too.
		$this->assertNotContains( $non_excluded_dir_name, $non_excluded_dir->get_user_excludes() );
		$this->assertSame( $non_excluded_dir->is_file_excluded( $file ), false );
	}

	private function get_and_prepare_files() {

		$finder = $this->backup->get_files();
		$files = array();

		foreach ( $finder as $file ) {
			$files[] = wp_normalize_path( $file->getRelativePathname() );
		}

		return $files;

	}

}
