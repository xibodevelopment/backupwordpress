<?php

namespace HM\BackUpWordPress;

class Site_Backup_Tests extends \HM_Backup_UnitTestCase {

	public function setUp() {
		$this->backup = new Site_Backup;
		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );
	}

	public function tearDown() {
		$this->cleanup_test_data();
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

	public function test_multiple_backups_exclude_backups() {

		$this->backup->set_backup_filename( 'backup1.zip' );
		$this->backup->backup();
		$backup1 = $this->backup->get_backup_filepath();

		$this->backup->set_backup_filename( 'backup2.zip' );
		$this->backup->backup();
		$backup2 = $this->backup->get_backup_filepath();

		$this->assertEquals( filesize( $backup1 ), filesize( $backup2 ) );
		$this->assertArchiveNotContains( $backup2, array( 'backup1.zip' ) );

	}

}
