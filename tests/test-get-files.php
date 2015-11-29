<?php

namespace HM\BackUpWordPress;

class Backup_Engine_Get_Files extends \HM_Backup_UnitTestCase {

	public function setUp() {

		$this->backup = new Mock_File_Backup_Engine;
		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );

	}

	public function tearDown() {
		chmod( Path::get_root() . '/exclude', 0755 );
		$this->cleanup_test_data();
	}

	public function test_get_files() {

		$files = $this->backup->get_files();
		$this->assertEquals( count( $files ), 3 );

	}

	public function test_get_files_includes_hidden_files() {

		file_put_contents( $this->test_data . '/.hidden', '' );

		$files = $this->backup->get_files();
		$this->assertEquals( count( $files ), 4 );

	}

	public function test_unreadable_files_ignored() {

		chmod( Path::get_root() . '/test-data.txt', 0220 );

		if ( is_readable( Path::get_root() . '/test-data.txt' ) ) {
			$this->markTestSkipped( "File was readable." );
		}

		$files = $this->backup->get_files();
		$this->assertEquals( count( $files ), 2 );

	}

	public function test_unreadable_directory_ignored() {

		chmod( Path::get_root() . '/exclude', 0220 );

		if ( is_readable( Path::get_root() . '/exclude' ) ) {
			$this->markTestSkipped( "directory was readable." );
		}

		$files = $this->backup->get_files();
		$this->assertEquals( count( $files ), 1 );

	}

	public function test_vcs_ignored() {

		mkdir( $this->test_data . '/.git' );

		$files = $this->backup->get_files();
		$this->assertEquals( count( $files ), 3 );

	}

	public function test_default_excludes_ignored() {

		$excludes = new Excludes;

		$default_excludes = $excludes->get_default_excludes();

		foreach ( $default_excludes as $default_exclude ) {
			$default_exclude = str_replace( '*/', '', $default_exclude );
			mkdir( trailingslashit( $this->test_data ) . $default_exclude );
		}

		$files = $this->backup->get_files();
		$this->assertEquals( count( $files ), 3 );

	}

	/**
	 * The .gitignore file should be ignored because .git is a default exclude
	 */
	public function test_excluded_git_in_filename_is_ignored() {

		file_put_contents( $this->test_data . '/.gitignore', '' );

		$files = $this->backup->get_files();
		$this->assertEquals( count( $files ), 3 );

	}

	/**
	 * These folders shouln't be excluded just because `updraft` is an excluded directory
	 */
	public function test_excluded_dir_in_name_isnt_ignored() {

		$this->markTestSkipped( 'This fails because our default excludes are too generic' );

		mkdir( $this->test_data . '/updraft-plus' );
		file_put_contents( $this->test_data . '/updraft-plus/file.txt', 'The cake is a lie.' );
		mkdir( $this->test_data . '/plus-updraft' );
		file_put_contents( $this->test_data . '/plus-updraft/file.txt', 'The cake is a lie.' );

		$files = $this->backup->get_files();
		$this->assertEquals( count( $files ), 7 );

	}

}
