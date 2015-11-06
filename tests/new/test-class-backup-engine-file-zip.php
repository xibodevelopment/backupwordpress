<?php

namespace HM\BackUpWordPress;

class Zip_File_Backup_Engine_Tests extends File_Backup_Engine_Common_Tests {

	protected $backup;

	public function setUp() {
		$this->backup = new Zip_File_Backup_Engine;
		parent::setUp();
	}

	/**
	 * Override the common version of this test as `zip` does include unreadable directories,
	 * it just doesn't include any of the files in the unreadable directory
	 */
	public function test_backup_with_unreadable_directory() {

		chmod( $this->backup->get_root() . '/exclude', 0220 );

		if ( is_readable( $this->backup->get_root() . '/exclude' ) ) {
			$this->markTestSkipped( "Directory was readable." );
		}

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_backup_filepath() );

		$this->assertArchiveNotContains( $this->backup->get_backup_filepath(), array( 'exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_backup_filepath(), 2 );

	}

}