<?php

namespace HM\BackUpWordPress;

class Backup_Director_Tests extends \HM_Backup_UnitTestCase {

	public function setUp() {

		$this->good_backup_engine = new Mock_Backup_Engine;
		$this->bad_backup_engine = new Mock_Failing_Backup_Engine;
		$this->backup = new Backup( 'backup.zip' );

	}

	public function test_backup_director_good() {
		$backup_engine = $this->backup->perform_backup( array( $this->good_backup_engine ) );
		$this->assertEquals( $this->good_backup_engine, $backup_engine );
	}

	public function test_backup_director_good_first() {
		$backup_engine = $this->backup->perform_backup( array( $this->good_backup_engine, $this->bad_backup_engine ) );
		$this->assertEquals( $this->good_backup_engine, $backup_engine );
	}

	public function test_backup_director_good_last() {
		$backup_engine = $this->backup->perform_backup( array( $this->bad_backup_engine, $this->good_backup_engine ) );
		$this->assertEquals( $this->good_backup_engine, $backup_engine );
	}

	public function test_backup_director_bad() {
		$backup_engine = $this->backup->perform_backup( array( $this->bad_backup_engine ) );
		$this->assertFalse( $backup_engine );
	}

	public function test_backup_director_lots_of_bad() {
		$backup_engine = $this->backup->perform_backup( array( $this->bad_backup_engine, $this->bad_backup_engine, $this->bad_backup_engine, $this->bad_backup_engine, $this->good_backup_engine, $this->bad_backup_engine ) );
		$this->assertEquals( $this->good_backup_engine, $backup_engine );
	}

}
