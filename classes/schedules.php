<?php

/**
 * A simple class for loading schedules
 */
class HMBKP_Schedules {

	/**
	 * An array of schedules
	 *
	 * @var mixed
	 * @access private
	 */
	private $schedules;

	/**
	 * Load the schedules from wp_options and store in $this->schedules
	 *
	 * @access public
	 */
	public function __construct() {

		global $wpdb;

		// Load all schedule options from the database
		$schedules = $wpdb->get_col( "SELECT option_name from $wpdb->options WHERE option_name LIKE 'hmbkp\_schedule\_%'" );

		// Instantiate each one as a HMBKP_Scheduled_Backup
		$this->schedules = array_map( array( $this, 'instantiate' ), array_filter( (array) $schedules ) );

	}

	/**
	 * Get an array of schedules
	 *
	 * @access public
	 * @return array
	 */
	public function get_schedules() {
		return $this->schedules;
	}

	/**
	 * Get a schedule by ID
	 * 
	 * @return HMBKP_Scheduled_Backup
	 */
	public function get_schedule( $id ) {

		foreach ( $this->schedules as $schedule )
			if ( $schedule->get_id() == $id )
				return $schedule;

		return null;
	}

	/**
	 * Instantiate the individual scheduled backup objects
	 *
	 * @access private
	 * @param string $id
	 * @return array An array of HMBKP_Scheduled_Backup objects
	 */
	private function instantiate( $id ) {

		return new HMBKP_Scheduled_Backup( str_replace( 'hmbkp_schedule_', '', $id ) );

	}

}