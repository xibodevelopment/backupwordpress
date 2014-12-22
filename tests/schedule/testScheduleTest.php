<?php

/**
 * Tests for methods dealing with scheduling backups
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
	 * Setup the schedule
	 *
	 * @access public
	 */
	public function setUp() {

		$this->schedule = new HMBKP_Scheduled_Backup( 'unit-test' );

		$this->recurrences = HMBKP_Scheduled_Backup::get_cron_schedules();

	}

	/**
	 * Teardown the schedule and cleanup
	 *
	 * @access public
	 */
	public function tearDown() {

		$this->schedule->cancel();

		unset( $this->schedule );

		unset( $this->recurrences );

	}

	/**
	 * Test that the default schedule is manual
	 *
	 * @access public
	 */
	public function testDefaultSchedule() {

		// The default recurrence should be manual
		$this->assertEquals( 'manually', $this->schedule->get_reoccurrence() );

		// There shouldn't be a next occurence
		$this->assertEmpty( $this->schedule->get_next_occurrence() );

		// There shoudldn't be a start time
		$this->assertEmpty( $this->schedule->get_schedule_start_time() );

		// There shouldn't be an interval
		$this->assertEmpty( $this->schedule->get_interval() );

	}

	/**
	 * Test that setting a start time in the past causes a wp_error and default to now instead
	 *
	 * @access public
	 */
	public function set_past_start_time() {

		$this->assertTrue( is_wp_error( $this->schedule->set_schedule_start_time( time() - 7200 ) ) );
		$this->assertEquals( $this->schedule->get_schedule_start_time(), time(), '', 30 );

	}

	/**
	 * Test that we can set the each schedule without a start time
	 *
	 * @access public
	 */
	public function testSetrecurrences() {

		foreach ( $this->recurrences as $reoccurrence => $settings ) {

			$this->schedule->set_reoccurrence( $reoccurrence );
			$this->assertEquals( $reoccurrence, $this->schedule->get_reoccurrence() );

			// The default start time should be now
			$this->assertEquals( time(), $this->schedule->get_schedule_start_time(), '', 30 );

			// Check that the start time is the same as the next occurance
			$this->assertEquals( time(), $this->schedule->get_next_occurrence(), '', 30 );

		}

	}

	/**
	 * Test that the cron even is re-setup if the cron option in the database is lost
	 *
	 * @access public
	 */
	public function testReSetupAfterDeleteCron() {

		$this->schedule->set_reoccurrence( 'hmbkp_hourly' );
		$this->assertEquals( 'hmbkp_hourly', $this->schedule->get_reoccurrence() );

		// The default start time should be now
		$this->assertEquals( time(), $this->schedule->get_schedule_start_time(), '', 30 );

		// Check that the start time is the same as the next occurance
		$this->assertEquals( time(), $this->schedule->get_next_occurrence(), '', 30 );

		$this->schedule->save();

		// delete the cron_array
		delete_option( 'cron' );

		$this->schedule->__construct( 'unit-test' );

		// The default start time should be now
		$this->assertEquals( time(), $this->schedule->get_schedule_start_time(), '', 30 );

		// Check that the start time is the same as the next occurance
		$this->assertEquals( $this->schedule->get_next_occurrence(), time(), '', 30 );

	}

	/**
	 * Test that we can set the each schedule with a start time
	 *
	 * @access public
	 */
	public function testSetFutureSchedule() {

		foreach ( $this->recurrences as $reoccurrence => $settings ) {

			$this->schedule->set_reoccurrence( 'hmbkp_hourly' );
			$this->assertEquals( 'hmbkp_hourly', $this->schedule->get_reoccurrence() );

			$this->assertFalse( is_wp_error( $this->schedule->set_schedule_start_time( time() + 7200 ) ) );
			$this->assertEquals( $this->schedule->get_schedule_start_time(), time() + 7200, '', 30 );

			$this->assertEquals( $this->schedule->get_next_occurrence(), time() + 7200, '', 30 );

		}

	}

	/**
	 * Test that we everything is properly removed when we unschedule
	 *
	 * @access public
	 */
	public function testUnschedule() {

		$this->schedule->set_reoccurrence( 'hmbkp_hourly' );
		$this->assertEquals( 'hmbkp_hourly', $this->schedule->get_reoccurrence() );

		// The default start time should be now
		$this->assertEquals( time(), $this->schedule->get_schedule_start_time(), '', 30 );

		// Check that the start time is the same as the next occurance
		$this->assertEquals( time(), $this->schedule->get_next_occurrence(), '', 30 );

		$this->schedule->unschedule();

		// Check that the start time is the same as the next occurance
		$this->assertEmpty( $this->schedule->get_next_occurrence() );

	}

}
