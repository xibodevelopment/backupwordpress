<?php

/**
 * Tests for moving the backup path if it changes
 *
 * @extends WP_UnitTestCase
 */
class testMoveBackUpPathTestCase extends HM_Backup_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	/**
	 * Setup the backup object and create the tmp directory
	 *
	 * @access public
	 */
	public function setUp() {

		$this->backup = new HM_Backup();
		$this->backup->set_type( 'database' );

		// Remove the backup directory if it exists
		hmbkp_rmdirtree( hmbkp_path() );

		$this->custom_path = trailingslashit( WP_CONTENT_DIR ) . 'test-custom';

		hmbkp_rmdirtree( $this->custom_path );

		$this->backup->set_path( hmbkp_path() );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 */
	public function tearDown() {

		hmbkp_rmdirtree( $this->custom_path );

		delete_option( 'hmbkp_path' );

		hmbkp_rmdirtree( hmbkp_path() );

		unset( $this->backup );

	}


	/**
	 * If the option is updated directly it should be overritten with the default path immediately, nothing should be moved.
	 *
	 * @access public
	 * @return void
	 */
	public function testUpdateOptionBackupPath() {

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		update_option( 'hmbkp_path', $this->custom_path );

		hmbkp_constant_changes();

		$this->assertEquals( hmbkp_path(), hmbkp_path_default() );

		$this->assertFileExists( $this->backup->get_archive_filepath() );

	}


	/**
	 * If the HMBKP_PATH constant is removed then the path should revert to default and everything should be moved.
	 *
	 * @access public
	 * @return void
	 */
	public function testRemovedDefinedBackupPath() {

		update_option( 'hmbkp_path', $this->custom_path );
		$this->backup->set_path( hmbkp_path() );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		hmbkp_constant_changes();

		$this->assertFileNotExists( $this->backup->get_archive_filepath() );
		$this->assertFileExists( str_replace( $this->backup->get_path(), hmbkp_path(), $this->backup->get_archive_filepath() ) );

	}


	/**
	 * If the HMBKP_PATH constant is defined and the new directory is writable then everything should be moved there
	 *
	 * @access public
	 * @return void
	 */
	public function testWritableDefinedBackupPath() {

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		define( 'HMBKP_PATH', $this->custom_path );

		hmbkp_constant_changes();

		$this->assertEquals( hmbkp_path(), HMBKP_PATH );
		$this->assertFileExists( str_replace( $this->backup->get_path(), HMBKP_PATH, $this->backup->get_archive_filepath() ) );
		$this->assertFileNotExists( $this->backup->get_archive_filepath() );

	}

	/**
	 * If the HMBKP_PATH constant is defined and the new directory is writable then everything should be moved there
	 *
	 * @access public
	 * @return void
	 */
	public function testUnWritableDefinedBackupPath() {

		$this->assertEquals( hmbkp_path(), HMBKP_PATH );

		$this->backup->backup();

		$this->assertFileExists( $this->backup->get_archive_filepath() );

		chmod( $this->custom_path, 0555 );

		hmbkp_constant_changes();

		$this->assertEquals( hmbkp_path(), hmbkp_path_default() );

		chmod( $this->custom_path, 0755 );

		$this->assertFileExists( str_replace( $this->backup->get_path(), hmbkp_path_default(), $this->backup->get_archive_filepath() ) );

		// They should both exist because we didn't have permission to remove the old file
		$this->assertFileExists( $this->backup->get_archive_filepath() );

	}

}