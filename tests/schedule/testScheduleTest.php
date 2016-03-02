<?php

namespace HM\BackUpWordPress;

/**
 * Tests for methods dealing with scheduling backups
 *
 * @extends WP_UnitTestCase
 */
class testScheduleTestCase extends \HM_Backup_UnitTestCase {

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
	 */
	public function setUp() {

		$this->schedule = new Scheduled_Backup( 'unit-test' );

		$this->recurrences = get_cron_schedules();

	}

	/**
	 * Teardown the schedule and cleanup
	 *
	 */
	public function tearDown() {

		$this->schedule->cancel();

		unset( $this->schedule );

		unset( $this->recurrences );

	}

	/**
	 * Test that the default schedule is manual
	 *
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
	 */
	public function set_past_start_time() {

		$this->assertTrue( is_wp_error( $this->schedule->set_schedule_start_time( time() - 7200 ) ) );
		$this->assertEquals( $this->schedule->get_schedule_start_time(), time(), '', 30 );

	}

	/**
	 * Test that we can set the each schedule without a start time
	 *
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
	 */
	public function testReSetupAfterDeleteCron() {

		$this->schedule->set_reoccurrence( 'hourly' );
		$this->assertEquals( 'hourly', $this->schedule->get_reoccurrence() );

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
	 */
	public function testSetFutureSchedule() {

		foreach ( $this->recurrences as $reoccurrence => $settings ) {

			$this->schedule->set_reoccurrence( 'hourly' );
			$this->assertEquals( 'hourly', $this->schedule->get_reoccurrence() );

			$this->assertFalse( is_wp_error( $this->schedule->set_schedule_start_time( time() + 7200 ) ) );
			$this->assertEquals( $this->schedule->get_schedule_start_time(), time() + 7200, '', 30 );

			$this->assertEquals( $this->schedule->get_next_occurrence(), time() + 7200, '', 30 );

		}

	}

	/**
	 * Test that we everything is properly removed when we unschedule
	 *
	 */
	public function testUnschedule() {

		$this->schedule->set_reoccurrence( 'hourly' );
		$this->assertEquals( 'hourly', $this->schedule->get_reoccurrence() );

		// The default start time should be now
		$this->assertEquals( time(), $this->schedule->get_schedule_start_time(), '', 30 );

		// Check that the start time is the same as the next occurance
		$this->assertEquals( time(), $this->schedule->get_next_occurrence(), '', 30 );

		$this->schedule->unschedule();

		// Check that the start time is the same as the next occurance
		$this->assertEmpty( $this->schedule->get_next_occurrence() );

	}

	public function testAverageBackupDurationCorrectValuesMinutes() {

		$test_values = array(
			array(
				'start' => time(),
				'end' => time() + ( 10 * MINUTE_IN_SECONDS ),
			),
			array(
				'start' => time(),
				'end' => time() + ( 5 * MINUTE_IN_SECONDS ),
			),
		);

		$this->add_mock_backup_data( $test_values );

		// We round the average so 7.5 becomes 8
		$this->assertEquals( '8 mins', $this->schedule->get_schedule_average_duration() );
	}

	public function testAverageBackupDurationCorrectValuesHours() {

		$test_values = array(
			array(
				'start' => time(),
				'end' => time() + ( 10 * HOUR_IN_SECONDS ),
			),
			array(
				'start' => time(),
				'end' => time() + ( 5 * HOUR_IN_SECONDS ),
			),
		);

		$this->add_mock_backup_data( $test_values );

		// We round the average so 7.5 becomes 8
		$this->assertEquals( '8 hours', $this->schedule->get_schedule_average_duration() );
	}

	public function testAverageBackupDurationIncorrectValues() {

		// Add an initial fake run
		$this->add_mock_backup_data( array(
			'start' => time(),
			'end' => time() + ( 7 * MINUTE_IN_SECONDS ),
		) );

		$current_average = $this->schedule->get_schedule_average_duration();

		$test_values = array(
			array(
				'start' => time(),
				'end' => time() - ( 10 * MINUTE_IN_SECONDS ),
			),
			array(
				'start' => time(),
				'end' => time() - ( 5 * MINUTE_IN_SECONDS ),
			),
		);

		$this->add_mock_backup_data( $test_values );

		// Value should not have changed
		$this->assertEquals( $current_average, $this->schedule->get_schedule_average_duration() );
	}

	public function testAverageBackupDurationZeroValues() {

		// Add an initial fake run
		$this->add_mock_backup_data( array(
			'start' => time(),
			'end' => time() + ( 7 * MINUTE_IN_SECONDS ),
		) );

		$current_average = $this->schedule->get_schedule_average_duration();

		$test_values = array(
			array(
				'start' => 0,
				'end' => 0,
			),
			array(
				'start' => 0,
				'end' => 0,
			),
		);

		$this->add_mock_backup_data( $test_values );

		// Value should not have changed
		$this->assertEquals( $current_average, $this->schedule->get_schedule_average_duration() );
	}

	protected function add_mock_backup_data( $data = array() ) {

		foreach ( $data as $run ) {
			$this->schedule->update_average_schedule_run_time( $run['start'], $run['end'] );
		}

	}

}
