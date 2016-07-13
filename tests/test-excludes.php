<?php

namespace HM\BackUpWordPress;

class Backup_Engine_Excludes extends \HM_Backup_UnitTestCase {

	public function setUp() {

		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );

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

	public function test_is_file_excluded() {

		// Non-excluded file, directly (not in a sub-folder) - false.

		// Non-excluded file, in a non-excluded sub-folder - false.

		// Non-excluded file, in an excluded sub-folder - false.

		// Excluded file, directly (not in a sub-folder) - true.

		// Excluded file, in a non-excluded sub-folder - true.

		// Excluded file, in an excluded sub-folder - true.
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
