<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @extends WP_UnitTestCase
 */
class testScheduleTestCase extends WP_UnitTestCase {

	/**
	 * Contains the current backup schedule instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $schedule;

	/**
	 * Setup the backup object and create the tmp directory
	 *
	 * @access public
	 */
	public function setUp() {

		$this->schedule = new HMBKP_Scheduled_Backup( 'unit-test' );

	}

	public function tearDown() {
		unset( $this->schedule );
	}

	public function testDefaultReoccurrence() {

		$this->assertEquals( 'manually', $this->schedule->get_reoccurrence() );

	}

	public function testDefaultSchedule() {

		$this->assertEquals( 'manually', $this->schedule->get_reoccurrence() );

		$this->assertEmpty( $this->schedule->get_next_occurrence() );
		$this->assertEmpty( $this->schedule->get_schedule_start_time() );
		$this->assertEmpty( $this->schedule->get_interval() );

		$this->assertEquals( $this->schedule->get_schedule_start_time() + $this->schedule->get_interval(), $this->schedule->get_next_occurrence() );

	}

	public function testSetReoccurrence() {

		$this->schedule->set_reoccurrence( 'daily' );

		$this->assertEquals( 'daily', $this->schedule->get_reoccurrence() );

	}

	public function testSetSchedule() {

		$this->schedule->set_reoccurrence( 'daily' );

		$this->assertEquals( 'daily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time() + $this->schedule->get_interval(), $this->schedule->get_next_occurrence() );

	}

}