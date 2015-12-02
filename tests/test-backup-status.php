<?php

namespace HM\BackUpWordPress;

/**
 * Unit tests for the Backup_Status class
 *
 * @extends HM_Backup_UnitTestCase
 */
class Test_Backup_Status extends \HM_Backup_UnitTestCase {

	public function setUp() {
		$this->setup_test_data();
		Path::get_instance()->set_path( $this->test_data . '/tmp' );
		Path::get_instance()->set_root( $this->test_data );
		$this->status = new Backup_Status( 'status1' );
	}

	public function tearDown() {
		$this->cleanup_test_data();
	}

	public function test_not_started() {
		$this->assertFalse( $this->status->is_started() );
	}

	public function test_not_started_status_filepath() {
		$this->assertFileNotExists( $this->status->get_status_filepath() );
	}

	public function test_not_started_filename() {
		$this->assertEmpty( $this->status->get_backup_filename() );
	}

	public function test_not_started_status() {
		$this->assertEmpty( $this->status->get_status() );
	}

	public function test_not_started_start_time() {
		$this->assertEquals( 0, $this->status->get_start_time() );
	}

	public function test_not_started_set_status() {
		$this->status->set_status( 'running' );
		$this->assertEmpty( $this->status->get_status() );
	}

	public function test_started() {
		$this->status->start( 'pri', 'sm' );
		$this->assertTrue( $this->status->is_started() );
	}

	public function test_started_status_filepath() {
		$this->status->start( 'pri', 'sm' );
		$this->assertFileExists( $this->status->get_status_filepath() );
	}

	public function test_started_filename() {
		$this->status->start( 'pri', 'sm' );
		$this->assertEquals( 'pri', $this->status->get_backup_filename() );
	}

	public function test_started_status() {
		$this->status->start( 'pri', 'sm' );
		$this->assertEquals( 'sm', $this->status->get_status() );
	}

	public function test_started_start_time() {
		$this->status->start( 'pri', 'sm' );
		$this->assertNotEquals( 0, $this->status->get_start_time() );
	}

	public function test_started_set_status() {
		$this->status->start( 'pri', 'sm' );
		$this->status->set_status( 'running' );
		$this->assertEquals( 'running', $this->status->get_status() );
	}

	public function test_finish() {
		$this->status->start( 'pri', 'sm' );
		$this->status->finish();
		$this->assertFileNotExists( $this->status->get_status_filepath() );
	}

	public function test_multiple_status_dont_clash() {

		$status1 = $this->status;
		$status2 = new Backup_Status( 'status2' );

		$status1->start( 'darth', 'vadar' );
		$status2->start( 'master', 'yoda' );

		$this->assertNotEquals( $status1->get_status_filepath(), $status2->get_status_filepath() );
		$this->assertEquals( 'darth', $status1->get_backup_filename() );
		$this->assertEquals( 'vadar', $status1->get_status() );
		$this->assertEquals( 'master', $status2->get_backup_filename() );
		$this->assertEquals( 'yoda', $status2->get_status() );

	}

}
