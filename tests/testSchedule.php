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
		
		$this->schedule->cancel();
		
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

	public function testSetHourlySchedule() {

		$this->schedule->set_reoccurrence( 'hourly' );

		$this->assertEquals( 'hourly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetTwiceDailySchedule() {

		$this->schedule->set_reoccurrence( 'daily' );

		$this->assertEquals( 'daily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetDailySchedule() {

		$this->schedule->set_reoccurrence( 'twicedaily' );

		$this->assertEquals( 'twicedaily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}	

	public function testSetWeeklySchedule() {

		$this->schedule->set_reoccurrence( 'weekly' );

		$this->assertEquals( 'weekly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetFortnightlySchedule() {

		$this->schedule->set_reoccurrence( 'fortnightly' );

		$this->assertEquals( 'fortnightly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}	
	
	public function testSetMonthlySchedule() {

		$this->schedule->set_reoccurrence( 'monthly' );

		$this->assertEquals( 'monthly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

}