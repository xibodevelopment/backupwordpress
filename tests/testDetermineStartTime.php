<?php

/**
 * Overide the global time() function in local scope so we can mock time
 *
 * @uses testDetermineStartTimeTestCase::$now
 * @return int $timestamp;
 */
//function $this->time() {
//    return testDetermineStartTimeTestCase::$now ? testDetermineStartTimeTestCase::$now : \time();
//}

/**
 * Unit tests for the hmbkp_determine_start_time function
 *
 * @see hmbkp_determine_start_time()
 * @extends WP_UnitTestCase
 */
class testDetermineStartTimeTestCase extends HM_Backup_UnitTestCase {

	/**
     * @var int $now Timestamp that will be returned by $this->time()
     * @see $this->time()
     * @static
     * @access public
     */
    public static $now;

    public function setUp() {
    	$this->schedule_intervals = HMBKP_Scheduled_Backup::get_cron_schedules();
    }

	public function time() {
		return testDetermineStartTimeTestCase::$now ? testDetermineStartTimeTestCase::$now : time();
	}

	/**
     * Reset custom time after test
     */
    public function tearDown() {
        self::$now = null;
    }

	/**
	 * Test that the default time args are respected for hourly schedule
	 *
	 * @access public
	 */
	public function testDefaultArgs() {

		foreach ( $this->schedule_intervals as $interval_name => $schedule_interval ) {

			// Test with current time
			$timestamp = hmbkp_determine_start_time( $interval_name );

			// Should be the beginning of the current minute + 10 minutes
			$this->assertEquals( $this->time() + 600 - date( 's', $this->time() ), $timestamp, $interval_name );

			// 12:00
			self::$now = strtotime( '2014-03-05T12:00:00+00:00' );
			$timestamp = hmbkp_determine_start_time( $interval_name, array( 'now' => $this->time() ) );
			$this->assertEquals( strtotime( '2014-03-05T12:10:00+00:00' ), $timestamp, $interval_name );

			// 23:59
			self::$now = strtotime( '2014-03-05T23:59:00+00:00' );
			$timestamp = hmbkp_determine_start_time( $interval_name, array( 'now' => $this->time() ) );
			$this->assertEquals( strtotime( '2014-03-06T00:09:00+00:00' ), $timestamp ); // The next day at 9 minutes past midnight

			// 23:59 on the Dec 31
			self::$now = strtotime( '2013-12-31T23:59:00+00:00' );
			$timestamp = hmbkp_determine_start_time( $interval_name, array( 'now' => $this->time() ) );
			$this->assertEquals( strtotime( '2014-01-01T00:09:00+00:00' ), $timestamp ); // 1st of Jan of the next year at 9 minutes past midnight

			self::$now = null;

		}

	}

	/**
	 * Test that setting the hourly schedule to various future times works as expected
	 *
	 * @access public
	 */
	public function testFutureStart() {

		self::$now = strtotime( '2014-03-05T12:00:00+00:00' );

		foreach ( array( 'hmbkp_hourly', 'hmbkp_twicedaily', 'hmbkp_fortnightly' ) as $interval_name ) {

			// 13:01
			$timestamp = hmbkp_determine_start_time( $interval_name, array( 'hours' => 12, 'minutes' => 1, 'now' => $this->time() ) );
			$this->assertEquals( strtotime( '2014-03-05T12:01:00+00:00' ), $timestamp );

			// 23:59
			$timestamp = hmbkp_determine_start_time( $interval_name, array( 'hours' => 23, 'minutes' => 59, 'now' => $this->time() ) );
			$this->assertEquals( strtotime( '2014-03-05T23:59:00+00:00' ), $timestamp );

		}

	}

	/**
	 * Test that setting the hourly schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testHourlyPastStart() {

		self::$now = strtotime( '2014-03-05T12:00:00+00:00' );

		// 12:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'hours' => 12, 'minutes' => 0, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-05T13:00:00+00:00' ), $timestamp ); // An hour after current time

		// 11:59
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'hours' => 11, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-05T12:59:00+00:00' ), $timestamp ); // 59 minutes past the next hour

		// 01:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'hours' => 1, 'minutes' => 0, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-05T13:00:00+00:00' ), $timestamp ); // An hour after the current time

	}

	/**
	 * Test that setting the twice daily schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testTwiceDailyPastStart() {

		self::$now = strtotime( '2014-03-05T12:00:00+00:00' );

		// 01:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 1, 'minutes' => 0, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-05T13:00:00+00:00' ), $timestamp ); // 12 hours after the start time

		self::$now = strtotime( '2014-03-05T13:00:00+00:00' );

		// 01:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 1, 'minutes' => 0, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-06T01:00:00+00:00' ), $timestamp ); // Tomorrow at 1am as we've already missed both schedules today

		// 12:59
		self::$now = strtotime( '2014-03-05T12:59:00+00:00' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 12, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-06T00:59:00+00:00' ), $timestamp ); // Tomorrow at 59 minutes past midnight

	}

	/**
	 * Test that setting the daily schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testDailyPastStart() {

		self::$now = strtotime( '2014-03-05T12:00:00+00:00' );

		// 01:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'hours' => 1, 'minutes' => 0, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-06T01:00:00+00:00' ), $timestamp ); // 24 hours after the start time

		// 12:59
		self::$now = strtotime( '2014-03-05T12:59:00+00:00' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'hours' => 12, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-06T12:59:00+00:00' ), $timestamp );

	}

	/**
	 * Test that setting the daily schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testWeeklyPastStart() {

		self::$now = strtotime( '2014-03-05T12:59:00+00:00');

		$timestamp = hmbkp_determine_start_time( 'hmbkp_weekly', array( 'day_of_week' => 'monday', 'hours' => 1, 'minutes' => 0, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-10T01:00:00+00:00', $this->time() ), $timestamp );

		// 11:59
		$timestamp = hmbkp_determine_start_time( 'hmbkp_weekly', array( 'day_of_week' => 'wednesday', 'hours' => 11, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-12T11:59:00+00:00', $this->time() ), $timestamp ); // Next week

	}

	/**
	 * Test that setting the weekly schedule to various future times works as expected
	 *
	 * @access public
	 */
	public function testWeeklyFutureStart() {

		self::$now = strtotime( '2014-03-05T12:00:00+00:00' );

		// 23:59 on Friday
		$timestamp = hmbkp_determine_start_time( 'hmbkp_weekly', array( 'day_of_week' => 'friday', 'hours' => 23, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-07T23:59:00+00:00' ), $timestamp );

	}

	/**
	 * Test that setting the fortnightly schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testFortnightlyPastStart() {

		self::$now = strtotime( '2014-03-05T12:00:00+00:00' );

		$timestamp = hmbkp_determine_start_time( 'hmbkp_fortnightly', array( 'day_of_week' => 'monday', 'hours' => 1, 'minutes' => 0, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-10T01:00:00+00:00' ), $timestamp );

		// 11:59
		// @todo feels like this should actually be wednesday the 12th not the 19th
		$timestamp = hmbkp_determine_start_time( 'hmbkp_fortnightly', array( 'day_of_week' => 'wednesday', 'hours' => 11, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-19T11:59:00+00:00' ), $timestamp ); // Next week

	}

	/**
	 * Test that setting the monthly schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testMonthlyPastStart() {

		self::$now = strtotime( '2014-03-05T12:00:00+00:00' );

		$timestamp = hmbkp_determine_start_time( 'hmbkp_monthly', array( 'day_of_month' => '1', 'hours' => 1, 'minutes' => 0, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-04-01T01:00:00+00:00' ), $timestamp );

		$timestamp = hmbkp_determine_start_time( 'hmbkp_monthly', array( 'day_of_week' => '5', 'hours' => 11, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-04-05T11:59:00+00:00' ), $timestamp ); // Next week

	}

	/**
	 * Test that setting the monthly schedule to various future times works as expected
	 *
	 * @access public
	 */
	public function testMonthlyFutureStart() {

		self::$now = strtotime( '2014-03-05T12:00:00+00:00' );

		// 23:59 on the 25th
		$timestamp = hmbkp_determine_start_time( 'hmbkp_monthly', array( 'day_of_month' => '25', 'hours' => 23, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-03-25T23:59:00+00:00' ), $timestamp );

		// 23:59 on the Dec 31
		self::$now = strtotime( '2013-12-31T23:59:00+00:00' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_monthly', array( 'day_of_month' => '31', 'hours' => 23, 'minutes' => 59, 'now' => $this->time() ) );
		$this->assertEquals( strtotime( '2014-01-31T23:59:00+00:00' ), $timestamp );

	}

}