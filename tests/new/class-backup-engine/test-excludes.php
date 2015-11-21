<?php

namespace HM\BackUpWordPress;

class Backup_Engine_Excludes extends \HM_Backup_UnitTestCase {

	public function setUp() {

		$this->setup_test_data();

		Path::get_instance()->set_path( dirname( __DIR__ ) . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );

		$this->backup = new Mock_File_Backup_Engine;
	}

	public function testBackUpDirIsExcludedWhenBackUpDirIsInRoot() {

		Path::get_instance()->set_path( $this->test_data . '/tmp' );

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

		$this->backup->set_excludes( '/exclude/' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertCount( 1, $files );


	}

	public function testExcludeAbsoluteRootDirPath() {

		$this->backup->set_excludes( $this->test_data . '/exclude/' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertCount( 1, $files );

	}

	public function testExcludeDirPathFragment() {

		$this->backup->set_excludes( 'exclude/' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertCount( 1, $files );

	}

	public function testExcludeAmbiguousAbsoluteDirPath() {

		$this->backup->set_excludes( 'exclude' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertCount( 1, $files );

	}

	public function testExcludeAbsoluteFilePath() {

		$this->backup->set_excludes( '/exclude/exclude.exclude' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAmbiguousAbsoluteFilePath() {

		$this->backup->set_excludes( 'exclude/exclude.exclude' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAbsolutePathWithWildcardFile() {

		$this->backup->set_excludes( '/exclude/*' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFile() {

		$this->backup->set_excludes( 'exclude/*' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeWildcardFileName() {

		$this->backup->set_excludes( '*.exclude' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAbsolutePathWithWildcardFileName() {

		$this->backup->set_excludes( '/exclude/*.exclude' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileName() {

		$this->backup->set_excludes( 'exclude/*.exclude' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeWildcardFileExtension() {

		$this->backup->set_excludes( 'exclude.*' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAbsolutePathWithWildcardFileExtension() {

		$this->backup->set_excludes( '/exclude/exclude.*' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileExtension() {

		$this->backup->set_excludes( 'exclude/exclude.*' );

		$files = $this->get_and_prepare_files();

		$this->assertContains( 'test-data.txt', $files );
		$this->assertContains( 'exclude', $files );
		$this->assertNotContains( 'exclude/exclude.exclude', $files );

		$this->assertCount( 2, $files );

	}

	public function testWildCard() {

		$this->backup->set_excludes( '*' );

		$files = $this->get_and_prepare_files();

		$this->assertEmpty( $files );

	}

	private function get_and_prepare_files() {

		$finder = $this->backup->get_files();
		$files = array();

		foreach ( $finder as $file ) {
			$files[] = $file->getRelativePathname();
		}

		return $files;

	}

}