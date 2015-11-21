<?php

namespace HM\BackUpWordPress;

class Site_Backup_Tests extends \HM_Backup_UnitTestCase {

	public function setUp() {

		$this->backup = new Site_Backup;

	}

	public function tearDown() {

		rmdirtree( Path::get_path() );

	}

	public function test_database_backup() {

		$this->backup->set_type( 'database' );
		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( basename( $this->backup->get_database_backup_filepath() ) ) );

	}

	public function test_files_backup() {

		$this->backup->set_type( 'files' );
		$this->backup->backup();

		$finder = new Mock_File_Backup_Engine;
		$finder = $finder->get_files();

		foreach( $finder as $file ) {
			$files[] = $file->getRelativePathname();
		}

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), $files );

	}

	public function test_complete_backup() {

		$this->backup->backup();

		$finder = new Mock_File_Backup_Engine;
		$finder = $finder->get_files();

		foreach( $finder as $file ) {
			$files[] = $file->getRelativePathname();
		}

		$files[] = basename( $this->backup->get_database_backup_filepath() );

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), $files );

	}

}