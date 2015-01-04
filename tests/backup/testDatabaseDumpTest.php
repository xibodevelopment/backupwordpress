<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @extends WP_UnitTestCase
 */
class testDatabaseDumpTestCase extends HM_Backup_UnitTestCase {

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
		$this->backup->set_path( dirname( __FILE__ ) . '/tmp' );

		wp_mkdir_p( $this->backup->get_path() );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 */
	public function tearDown() {

		hmbkp_rmdirtree( $this->backup->get_path() );
		hmbkp_rmdirtree( hmbkp_path() );

		delete_option( 'hmbkp_path' );
		delete_option( 'hmbkp_default_path' );

		unset( $this->backup );

	}

	/**
	 * Test a database dump with the zip command
	 *
	 * @access public
	 */
	public function testDatabaseDumpWithMysqldump() {

		if ( ! $this->backup->get_mysqldump_command_path() )
            $this->markTestSkipped( "Empty mysqldump command path" );

		$this->backup->mysqldump();

		$this->assertFileExists( $this->backup->get_database_dump_filepath() );

	}

	/**
	 * Test a database dump with the PHP fallback
	 *
	 * @access public
	 */
	public function testDatabaseDumpWithFallback() {

		$this->backup->mysqldump_fallback();

		$this->assertFileExists( $this->backup->get_database_dump_filepath() );

	}

}