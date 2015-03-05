<?php

namespace HM\BackUpWordPress;

/**
 * A simple class for loading schedules
 */
class Schedules {

	/**
	 * An array of schedules
	 *
	 * @var mixed
	 * @access private
	 */
	private $schedules;

	/**
	 *
	 */
	protected static $instance;

	public static function get_instance() {

		if ( ! ( self::$instance instanceof Schedules ) ) {
			self::$instance = new Schedules();
		}

		return self::$instance;

	}

	/**
	 * Load the schedules from wp_options and store in $this->schedules
	 *
	 */
	private function __construct() {
		$this->refresh_schedules();
	}

	public function refresh_schedules() {

		global $wpdb;

		// Load all schedule options from the database
		$schedules = $wpdb->get_col( "SELECT option_name from $wpdb->options WHERE option_name LIKE 'hmbkp\_schedule\_%'" );

		// Instantiate each one as a Scheduled_Backup
		$this->schedules = array_map( array( $this, 'instantiate' ), array_filter( (array) $schedules ) );

	}

	/**
	 * Get an array of schedules
	 *
	 * @return Scheduled_Backup[]
	 */
	public function get_schedules() {
		return $this->schedules;
	}

	/**
	 * Get a schedule by ID
	 *
	 * @param $id
	 * @return Scheduled_Backup
	 */
	public function get_schedule( $id ) {

		foreach ( $this->schedules as $schedule ) {
			if ( $schedule->get_id() == $id ) {
				return $schedule;
			}
		}

		return null;
	}

	/**
	 * Instantiate the individual scheduled backup objects
	 *
	 * @access private
	 * @param string $id
	 * @return Scheduled_Backup
	 */
	private function instantiate( $id ) {
		return new Scheduled_Backup( str_replace( 'hmbkp_schedule_', '', $id ) );
	}

}
