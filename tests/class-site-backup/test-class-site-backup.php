<?php

namespace HM\BackUpWordPress;

class Site_Backup_Tests extends \HM_Backup_UnitTestCase {

	public function setUp() {
		$this->backup = new Backup( 'backup.zip' );
		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );
	}

	public function tearDown() {
		$this->cleanup_test_data();
	}

	/**
	 * @dataProvider file_backup_engine_provider
	 */
	public function test_database_backup( $file_backup_engines ) {

		add_filter( 'hmbkp_file_backup_engines', function() use ( $file_backup_engines ) {
			return $file_backup_engines;
		} );

		$this->backup->set_type( 'database' );
		$this->backup->run();

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( basename( $this->backup->get_database_backup_filepath() ) ) );

	}

	/**
	 * @dataProvider file_backup_engine_provider
	 */
	public function test_only_database_zipped_up( $file_backup_engines ) {

		add_filter( 'hmbkp_file_backup_engines', function() use ( $file_backup_engines ) {
			return $file_backup_engines;
		} );

		$this->backup->set_type( 'database' );
		Path::get_instance()->reset_path();

		file_put_contents( PATH::get_path() . '/foo.zip.SmuhtP', 'bar' );
		file_put_contents( PATH::get_path() . '/zicBotXQ', 'baz' );

		$this->backup->run();

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), array( basename( $this->backup->get_database_backup_filepath() ) ) );
		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'zicBotXQ', 'foo.zip.SmuhtP' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 1 );

	}

	/**
	 * @dataProvider file_backup_engine_provider
	 */
	public function test_files_backup( $file_backup_engines ) {

		add_filter( 'hmbkp_file_backup_engines', function() use ( $file_backup_engines ) {
			return $file_backup_engines;
		} );

		$this->backup->set_type( 'files' );
		$this->backup->run();

		$finder = new Mock_File_Backup_Engine;
		$finder = $finder->get_files();

		foreach ( $finder as $file ) {
			$files[] = $file->getRelativePathname();
		}

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), $files );

	}

	/**
	 * @dataProvider file_backup_engine_provider
	 */
	public function test_complete_backup( $file_backup_engines ) {

		add_filter( 'hmbkp_file_backup_engines', function() use ( $file_backup_engines ) {
			return $file_backup_engines;
		} );

		$this->backup->run();

		$finder = new Mock_File_Backup_Engine;
		$finder = $finder->get_files();

		foreach ( $finder as $file ) {
			$files[] = $file->getRelativePathname();
		}

		$files[] = basename( $this->backup->get_database_backup_filepath() );

		$this->assertFileExists( $this->backup->get_backup_filepath() );
		$this->assertArchiveContains( $this->backup->get_backup_filepath(), $files );

	}

	/**
	 * @dataProvider file_backup_engine_provider
	 */
	public function test_multiple_backups_exclude_backups( $file_backup_engines ) {

		add_filter( 'hmbkp_file_backup_engines', function() use ( $file_backup_engines ) {
			return $file_backup_engines;
		} );

		$this->backup->set_backup_filename( 'backup1.zip' );
		$this->backup->run();
		$backup1 = $this->backup->get_backup_filepath();

		$this->backup->set_backup_filename( 'backup2.zip' );
		$this->backup->run();
		$backup2 = $this->backup->get_backup_filepath();

		// Allow the filesize to vary by 10 bytes to avoid minor changes causing failures
		$this->assertEquals( filesize( $backup1 ), filesize( $backup2 ), '', 10 );
		$this->assertArchiveNotContains( $backup2, array( 'backup1.zip' ) );

	}

	/**
	 * Ensure we run each of the complete backup tests with each Backup Engine
	 */
	public function file_backup_engine_provider() {

		return array(
			array( array( new Zip_File_Backup_Engine ) ),
			array( array( new Zip_Archive_File_Backup_Engine ) ),
			array( array( new Pclzip_File_Backup_Engine ) ),
			array( array( new Zip_File_Backup_Engine, new Zip_Archive_File_Backup_Engine, new Pclzip_File_Backup_Engine ) ),
		);

	}
}
