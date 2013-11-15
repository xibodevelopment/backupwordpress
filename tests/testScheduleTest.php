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

		$this->schedule->set_schedule_start_time( array( 'type' => 'hmbkp_hourly' ) );

		$this->assertEquals( 'hmbkp_hourly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetHourlyFutureSchedule() {

		$future_time = date( 'H', time() + 7200 ); // 2 hours from now
		$recurrence = array(
			'type'         => 'hmbkp_hourly',
			'hours'        => $future_time,
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_hourly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetHourlyPastSchedule() {

		$past_time = date( 'H', time() - 7200 ); // 2 hours in the past
		$recurrence = array(
			'type'         => 'hmbkp_hourly',
			'hours'        => $past_time,
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_hourly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetTwiceDailySchedule() {

		$this->schedule->set_schedule_start_time( array( 'type' => 'hmbkp_twicedaily' ) );

		$this->assertEquals( 'hmbkp_twicedaily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetTwiceDailyFutureSchedule() {

		$future_time = date( 'H', time() + 7200 ); // 2 hours from now
		$recurrence = array(
			'type'         => 'hmbkp_twicedaily',
			'hours'        => $future_time,
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_twicedaily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetTwiceDailyPastSchedule() {

		$past_time = date( 'H', time() - 7200 ); // 2 hours in the past
		$recurrence = array(
			'type'         => 'hmbkp_twicedaily',
			'hours'        => $past_time,
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_twicedaily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetDailySchedule() {

		$this->schedule->set_schedule_start_time( array( 'type' => 'hmbkp_daily' ) );

		$this->assertEquals( 'hmbkp_daily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetDailyFutureSchedule() {

		$future_time = date( 'H', time() + 7200 ); // 2 hours from now
		$recurrence = array(
			'type'         => 'hmbkp_daily',
			'hours'        => $future_time,
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_daily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetDailyPastSchedule() {

		$past_time = date( 'H', time() - 7200 ); // 2 hours in the past
		$recurrence = array(
			'type'         => 'hmbkp_daily',
			'hours'        => $past_time,
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_daily', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetWeeklySchedule() {

		$this->schedule->set_schedule_start_time( array( 'type' => 'hmbkp_weekly' ) );

		$this->assertEquals( 'hmbkp_weekly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetWeeklyFutureSchedule() {

		$tomorrow = date( 'l', strtotime( '+1 day' ) );

		$recurrence = array(
			'type'         => 'hmbkp_weekly',
			'day_of_week'  => strtolower( $tomorrow ),
			'hours'        => '11',
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_weekly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetWeeklyPastSchedule() {

		$yesterday = date( 'l', strtotime( '-1 day' ) );

		$recurrence = array(
			'type'         => 'hmbkp_weekly',
			'day_of_week'  => strtolower( $yesterday ),
			'hours'        => '11',
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_weekly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetFortnightlySchedule() {

		$this->schedule->set_schedule_start_time( array( 'type' => 'hmbkp_fortnightly' ) );

		$this->assertEquals( 'hmbkp_fortnightly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetFortnightlyFutureSchedule() {

		$tomorrow = date( 'l', strtotime( '+1 day' ) );

		$recurrence = array(
			'type'         => 'hmbkp_fortnightly',
			'day_of_week'  => strtolower( $tomorrow ),
			'hours'        => '11',
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_fortnightly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetFortnightlyPastSchedule() {

		$yesterday = date( 'l', strtotime( '-1 day' ) );

		$recurrence = array(
			'type'         => 'hmbkp_fortnightly',
			'day_of_week'  => strtolower( $yesterday ),
			'hours'        => '11',
			'minutes'      => '00'
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_fortnightly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetMonthlySchedule() {

		$this->schedule->set_schedule_start_time( array( 'type' => 'hmbkp_monthly' ) );

		$this->assertEquals( 'hmbkp_monthly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetMonthlyFutureSchedule() {

		$five_days_in_future = date( 'j', strtotime( '+5 days' ) );

		$recurrence = array(
			'type'         => 'hmbkp_monthly',
			'day_of_month' => $five_days_in_future
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_monthly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

	public function testSetMonthlyPastSchedule() {

		$five_days_in_past = date( 'j', strtotime( '-5 days' ) );

		$recurrence = array(
			'type'         => 'hmbkp_monthly',
			'day_of_month' => $five_days_in_past
		);

		$this->schedule->set_schedule_start_time( $recurrence );

		$this->assertEquals( 'hmbkp_monthly', $this->schedule->get_reoccurrence() );

		$this->assertEquals( $this->schedule->get_schedule_start_time(), $this->schedule->get_next_occurrence() );

	}

}