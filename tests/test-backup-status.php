<?php

namespace HM\BackUpWordPress;

use Symfony\Component\Process\Process as Process;

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
		$status = new Backup_Status( 'backup' );
		$status->finish();
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
		$this->assertTrue( $this->status->start( 'pri', 'sm' ) );
		$this->assertTrue( $this->status->is_started() );
		$this->assertTrue( $this->status->is_running() );
		$this->assertTrue( $this->status->finish() );
	}

	public function test_start_and_finish() {
		$this->assertTrue( $this->status->start( 'pri', 'sm' ) );
		$this->assertTrue( $this->status->is_running() );
		$this->assertFalse( $this->status->start( 'pri', 'sm' ) );
		$this->assertTrue( $this->status->finish() );
		$this->assertTrue( $this->status->start( 'pri', 'sm' ) );
	}

	public function test_start_status_filepath() {
		$this->assertTrue( $this->status->start( 'pri', 'sm' ) );
		$this->assertFileExists( $this->status->get_status_filepath() );
	}

	public function test_started_filename() {
		$this->assertTrue( $this->status->start( 'pri', 'sm' ) );
		$this->assertEquals( 'pri', $this->status->get_backup_filename() );
	}

	public function test_started_status() {
		$this->assertTrue( $this->status->start( 'pri', 'sm' ) );
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

	public function test_manually_crash() {
		Path::get_instance()->reset_path();
		$process = new Process( 'wp backupwordpress backup' );
		$status = new Backup_Status( 'backup' );
		try {
			$process->run( function() use ( $process, $status ) {
				if ( $process->getPid() ) {
					$this->assertTrue( $status->is_started() );
					$this->assertTrue( $status->is_running() );
					exec( 'kill -9 ' . $process->getPid() );
				}
			} );
		} catch ( \Exception $e ) {}
		sleep( 5 );
		$this->assertFalse( $status->is_running() );
		$this->assertTrue( $status->is_started() );
		$this->assertTrue( $status->has_crashed() );
	}

	public function test_in_another_thread() {
		Path::get_instance()->reset_path();
		$process = new Process( 'wp backupwordpress backup --database_only' );
		$process->run();
		$this->assertFileExists( PATH::get_path() . '/backup.zip' );
	}

	public function test_in_multiple_threads() {
		Path::get_instance()->reset_path();
		$process = new Process( 'wp backupwordpress backup --database_only' );

		$phpunit = $this;
		$status = new Backup_Status( 'backup' );

		$process->run( function( $type, $buffer ) use ( $phpunit, $process, $status ) {
			if ( Process::ERR !== $type ) {

				$this->assertTrue( $process->isRunning() );
				$this->assertTrue( $status->is_running() );
				$this->assertFalse( $status->start( 'test', 'test' ) );

				if ( $process->stop() ) {
					sleep( 3 );
					$this->assertFalse( $status->is_running() );
					$this->assertTrue( $status->has_crashed() );
				}

			}
		} );
	}

	public function test_killed_process() {

		Path::get_instance()->reset_path();
		$process = new Process( 'wp backupwordpress backup --database_only' );

		$phpunit = $this;
		$status = new Backup_Status( 'backup' );

		$process->run( function( $type, $buffer ) use ( $phpunit, $process, $status ) {
			if ( Process::ERR !== $type ) {

				$this->assertTrue( $process->isRunning() );
				$this->assertTrue( $status->is_running() );

				exec( 'kill -9 ' . $process->getPid() );

				sleep( 3 );

				$this->assertFalse( $process->isRunning() );
				$this->assertFalse( $status->is_running() );
				$this->assertTrue( $status->has_crashed() );

				$process->stop();

			}
		} );
	}

	public function test_multiple_status_dont_clash() {

		$status1 = $this->status;
		$status2 = new Backup_Status( 'status2' );

		$this->assertTrue( $status1->start( 'darth', 'vadar' ) );
		$this->assertTrue( $status1->is_running() );
		$this->assertfalse( $status2->is_running() );

		$this->assertTrue( $status2->start( 'master', 'yoda' ) );
		$this->assertTrue( $status2->is_running() );

		$this->assertNotEquals( $status1->get_status_filepath(), $status2->get_status_filepath() );
		$this->assertEquals( 'darth', $status1->get_backup_filename() );
		$this->assertEquals( 'vadar', $status1->get_status() );
		$this->assertEquals( 'master', $status2->get_backup_filename() );
		$this->assertEquals( 'yoda', $status2->get_status() );

	}
}
