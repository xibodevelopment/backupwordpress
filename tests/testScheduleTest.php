<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @extends WP_UnitTestCase
 */
class testScheduleTestCase extends HM_Backup_UnitTestCase {

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

		$this->schedule->set_reoccurrence( 'hmbkp_hourly' );

		$this->assertEquals( 'hmbkp_hourly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetTwiceDailySchedule() {

		$this->schedule->set_reoccurrence( 'hmbkp_daily' );

		$this->assertEquals( 'hmbkp_daily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetDailySchedule() {

		$this->schedule->set_reoccurrence( 'hmbkp_twicedaily' );

		$this->assertEquals( 'hmbkp_twicedaily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetWeeklySchedule() {

		$this->schedule->set_reoccurrence( 'hmbkp_weekly' );

		$this->assertEquals( 'hmbkp_weekly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetFortnightlySchedule() {

		$this->schedule->set_reoccurrence( 'hmbkp_fortnightly' );

		$this->assertEquals( 'hmbkp_fortnightly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetMonthlySchedule() {

		$this->schedule->set_reoccurrence( 'hmbkp_monthly' );

		$this->assertEquals( 'hmbkp_monthly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

}