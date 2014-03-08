<?php

namespace HMBKP;

function time() {
    return testDetermineStartTimeTestCase::$now ?: \time();
}

/**
 *
 * @extends WP_UnitTestCase
 */
class testDetermineStartTimeTestCase extends \HM_Backup_UnitTestCase {

	/**
     * @var int $now Timestamp that will be returned by time()
     */
    public static $now;

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
	public function testHourlyDefaultArgs() {

		// Test with current time
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly' );

		// Should be the beginning of the current minute + 10 minutes
		$this->assertEquals( time() + 600 - date( 's', time() ), $timestamp );

		// 13:00
		self::$now = strtotime( '13:00' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'now' => time() ) );
		$this->assertEquals( strtotime( '13:10' ), $timestamp );

		// 23:59
		self::$now = strtotime( '23:59' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'now' => time() ) );
		$this->assertEquals( strtotime( '00:09 + 1 day' ), $timestamp ); // Tomorrow at 10 past midnight

	}

	/**
	 * Test that setting the hourly schedule to various future times works as expected
	 *
	 * @access public
	 */
	public function testHourlyFutureStart() {

		self::$now = strtotime( '13:00' );

		// 13:01
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'hours' => 13, 'minutes' => 1, 'now' => time() ) );
		$this->assertEquals( strtotime( '13:01' ), $timestamp );

		// 14:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'hours' => 14, 'minutes' => 0, 'now' => time() ) );
		$this->assertEquals( strtotime( '14:00' ), $timestamp );

		// 23:59
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'hours' => 23, 'minutes' => 59, 'now' => time() ) );
		$this->assertEquals( strtotime( '23:59' ), $timestamp );

	}

	/**
	 * Test that setting the hourly schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testHourlyPastStart() {

		self::$now = strtotime( '13:00' );

		// 01:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'hours' => 1, 'minutes' => 0, 'now' => time() ) );
		$this->assertEquals( strtotime( '14:00' ), $timestamp ); // An hour after current time

		// 12:59
		self::$now = strtotime( '12:59' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_hourly', array( 'hours' => 12, 'minutes' => 59, 'now' => time() ) );
		$this->assertEquals( strtotime( '13:59' ), $timestamp );

	}

	/**
	 * Test that the default time args are respected for twice daily schedule
	 *
	 * @access public
	 */
	public function testTwiceDailyDefaultArgs() {

		// Test with current time
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily' );

		// Should be the beginning of the current minute + 10 minutes
		$this->assertEquals( time() + 600 - date( 's', time() ), $timestamp );

		// 13:00
		self::$now = strtotime( '13:00' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'now' => time() ) );
		$this->assertEquals( strtotime( '13:10' ), $timestamp );

		// 23:59
		self::$now = strtotime( '23:59' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'now' => time() ) );
		$this->assertEquals( strtotime( '00:09 + 1 day' ), $timestamp ); // Tomorrow at 10 past midnight

	}

	/**
	 * Test that setting the twice daily schedule to various future times works as expected
	 *
	 * @access public
	 */
	public function testTwiceDailyFutureStart() {

		self::$now = strtotime( '13:00' );

		// 13:01
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 13, 'minutes' => 1, 'now' => time() ) );
		$this->assertEquals( strtotime( '13:01' ), $timestamp );

		// 14:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 14, 'minutes' => 0, 'now' => time() ) );
		$this->assertEquals( strtotime( '14:00' ), $timestamp );

		// 23:59
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 23, 'minutes' => 59, 'now' => time() ) );
		$this->assertEquals( strtotime( '23:59' ), $timestamp );

	}

	/**
	 * Test that setting the twice daily schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testTwiceDailyPastStart() {

		self::$now = strtotime( '12:00' );

		// 01:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 1, 'minutes' => 0, 'now' => time() ) );
		$this->assertEquals( strtotime( '13:00' ), $timestamp ); // 12 hours after the start time

		self::$now = strtotime( '13:00' );

		// 01:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 1, 'minutes' => 0, 'now' => time() ) );
		$this->assertEquals( strtotime( '01:00 + 1 day' ), $timestamp ); // Tomorrow at 1am as we've already missed both schedules today

		// 12:59
		self::$now = strtotime( '12:59' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_twicedaily', array( 'hours' => 12, 'minutes' => 59, 'now' => time() ) );
		$this->assertEquals( strtotime( '00:59 + 1 day' ), $timestamp ); // Tomorrow at 59 minutes past midnight

	}

	/**
	 * Test that the default time args are respected for daily schedule
	 *
	 * @access public
	 */
	public function testDailyDefaultArgs() {

		// Test with current time
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily' );

		// Should be the beginning of the current minute + 10 minutes
		$this->assertEquals( time() + 600 - date( 's', time() ), $timestamp );

		// 13:00
		self::$now = strtotime( '13:00' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'now' => time() ) );
		$this->assertEquals( strtotime( '13:10' ), $timestamp );

		// 23:59
		self::$now = strtotime( '23:59' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'now' => time() ) );
		$this->assertEquals( strtotime( '00:09 + 1 day' ), $timestamp ); // Tomorrow at 10 past midnight

	}

	/**
	 * Test that setting the daily schedule to various future times works as expected
	 *
	 * @access public
	 */
	public function testDailyFutureStart() {

		self::$now = strtotime( '13:00' );

		// 13:01
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'hours' => 13, 'minutes' => 1, 'now' => time() ) );
		$this->assertEquals( strtotime( '13:01' ), $timestamp );

		// 14:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'hours' => 14, 'minutes' => 0, 'now' => time() ) );
		$this->assertEquals( strtotime( '14:00' ), $timestamp );

		// 23:59
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'hours' => 23, 'minutes' => 59, 'now' => time() ) );
		$this->assertEquals( strtotime( '23:59' ), $timestamp );

	}

	/**
	 * Test that setting the daily schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testDailyPastStart() {

		self::$now = strtotime( '12:00' );

		// 01:00
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'hours' => 1, 'minutes' => 0, 'now' => time() ) );
		$this->assertEquals( strtotime( '01:00 + 1 day' ), $timestamp ); // 24 hours after the start time

		// 12:59
		self::$now = strtotime( '12:59' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_daily', array( 'hours' => 12, 'minutes' => 59, 'now' => time() ) );
		$this->assertEquals( strtotime( '12:59 + 1 day' ), $timestamp );

	}

	/**
	 * Test that the default time args are respected for weekly schedule
	 *
	 * @access public
	 */
	public function testWeeklyDefaultArgs() {

		$this->markTestIncomplete();

		// @todo not currently working could be bug, could be something wrong with test

		// Test with current time
		$timestamp = hmbkp_determine_start_time( 'hmbkp_weekly' );

		// Should be the beginning of the current minute + 10 minutes
		$this->assertEquals( time() + 600 - date( 's', time() ), $timestamp );

		// 13:00
		self::$now = strtotime( '13:00' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_weekly', array( 'now' => time() ) );
		$this->assertEquals( strtotime( '13:10' ), $timestamp );

		// 23:59
		self::$now = strtotime( '23:59' );
		$timestamp = hmbkp_determine_start_time( 'hmbkp_weekly', array( 'now' => time() ) );
		$this->assertEquals( strtotime( '00:09 + 1 day' ), $timestamp ); // Tomorrow at 10 past midnight

	}

	/**
	 * Test that setting the daily schedule to various future times works as expected
	 *
	 * @access public
	 */
	public function testWeeklyFutureStart() {

		$this->markTestIncomplete();

	}

	/**
	 * Test that setting the daily schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testWeeklyPastStart() {

		$this->markTestIncomplete();

	}

	/**
	 * Test that the default time args are respected for fortnightly schedule
	 *
	 * @access public
	 */
	public function testFortnightlyDefaultArgs() {

		$this->markTestIncomplete();

	}

	/**
	 * Test that setting the fortnightly schedule to various future times works as expected
	 *
	 * @access public
	 */
	public function testFortnightlyFutureStart() {

		$this->markTestIncomplete();

	}

	/**
	 * Test that setting the fortnightly schedule to various past times works as expected
	 *
	 * @access public
	 */
	public function testFortnightlyPastStart() {

		$this->markTestIncomplete();

	}

	/**
	 * Test that the default time args are respected for monthly schedule
	 *
	 * @access public
	 */
	public function testMonthlyDefaultArgs() {

		$this->markTestIncomplete();

	}

	/**
	 * Test that setting the fortnightly monthly to various future times works as expected
	 *
	 * @access public
	 */
	public function testMonthlyFutureStart() {

		$this->markTestIncomplete();

	}

	/**
	 * Test that setting the fortnightly monthly to various past times works as expected
	 *
	 * @access public
	 */
	public function testMonthlyPastStart() {

		$this->markTestIncomplete();

	}

}