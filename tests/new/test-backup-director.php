<?php

namespace HM\BackUpWordPress;

class Backup_Director_Tests extends \HM_Backup_UnitTestCase {

	public function setUp() {

		$this->good_backup_engine = __NAMESPACE__ . '\\Mock_Backup_Engine';
		$this->bad_backup_engine = __NAMESPACE__ . '\\Mock_Failing_Backup_Engine';

	}

	public function test_backup_director_good() {
		$backup = new Backup_Director( array( $this->good_backup_engine ) );
		$backup->backup();
		$this->assertEquals( $this->good_backup_engine, $backup->selected_backup_engine() );
	}

	public function test_backup_director_good_first() {
		$backup = new Backup_Director( array( $this->good_backup_engine, $this->bad_backup_engine ) );
		$backup->backup();
		$this->assertEquals( $this->good_backup_engine, $backup->selected_backup_engine() );
	}

	public function test_backup_director_good_last() {
		$backup = new Backup_Director( array( $this->bad_backup_engine, $this->good_backup_engine ) );
		$backup->backup();
		$this->assertEquals( $this->good_backup_engine, $backup->selected_backup_engine() );
	}

	public function test_backup_director_bad() {
		$backup = new Backup_Director( array( $this->bad_backup_engine ) );
		$backup->backup();
		$this->assertFalse( $backup->selected_backup_engine() );
	}

	public function test_backup_director_lots_of_bad() {
		$backup = new Backup_Director( array( $this->bad_backup_engine, $this->bad_backup_engine, $this->bad_backup_engine, $this->bad_backup_engine, $this->good_backup_engine, $this->bad_backup_engine ) );
		$backup->backup();
		$this->assertEquals( $this->good_backup_engine, $backup->selected_backup_engine() );
	}

}