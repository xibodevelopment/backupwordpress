<?php

namespace HM\BackUpWordPress;

/**
 * The Backup Scheduler
 *
 * Handles everything related to managing and running a backup schedule
 *
 * @uses Backup
 * @uses
 */
class Scheduled_Backup {

	/**
	 * The unique schedule id
	 *
	 * @var string
	 * @access private
	 */
	private $id = '';

	/**
	 * The slugified version of the schedule name
	 *
	 * @var string
	 * @access private
	 */
	private $slug = '';

	/**
	 * The raw schedule options from the database
	 *
	 * @var array
	 * @access private
	 */
	private $options = array(
		'max_backups'   => 3,
		'excludes'      => array(),
		'type'          => 'complete',
		'reoccurrence'  => 'manually'
	);

	/**
	 * Setup the schedule object
	 * Loads the options from the database and populates properties
	 *
	 * @param string $id
	 *
	 * @throws Exception
	 */

	public function __construct( $id ) {

		// Verify the schedule id
		if ( ! is_string( $id ) || ! trim( $id ) ) {
			throw new \Exception( 'Argument 1 for ' . __METHOD__ . ' must be a non-empty string' );
		}

		// Store id for later
		$this->id = $id;

		// Load the options
		$this->options = array_merge( $this->options, array_filter( (array) get_option( 'hmbkp_schedule_' . $this->get_id() ) ) );

		if ( defined( 'HMBKP_SCHEDULE_START_TIME' ) && strtotime( 'HMBKP_SCHEDULE_START_TIME' ) ) {
			$this->set_schedule_start_time( strtotime( 'HMBKP_SCHEDULE_START_TIME' ) );
		}

		// Setup the schedule if it isn't set
		if ( ( ! $this->is_cron_scheduled() && $this->get_reoccurrence() !== 'manually' ) ) {
			$this->schedule();
		}

		$this->backup_filename = implode( '-', array(
			sanitize_title( str_ireplace( array(
				'http://',
				'https://',
				'www'
			), '', home_url() ) ),
			$this->get_id(),
			$this->get_type(),
			current_time( 'Y-m-d-H-i-s' )
		) ) . '.zip';

		$this->database_dump_filename = implode( '-', array(
			'database',
			sanitize_title( str_ireplace( array( 'http://', 'https://', 'www' ), '', home_url() ) ),
			$this->get_id()
		) ) . '.sql';

		$this->status = new Backup_Status( $this->get_id() );

	}

	/**
	 * Get the id for this schedule
	 */
	public function get_id() {
		return esc_attr( $this->id );
	}

	/**
	 * Get a slugified version of name
	 */
	public function get_slug() {

		// We cache slug in $this to save expensive calls to sanitize_title
		if ( ! empty( $this->slug ) ) {
			return $this->slug;
		}

		return $this->slug = sanitize_title( $this->get_name() );

	}

	/**
	 * Returns the given option value
	 *
	 * @param $option_name
	 * @return mixed The option value
	 */
	public function get_schedule_option( $option_name ) {
		if ( isset( $this->options[ $option_name ] ) ) {
			return $this->options[ $option_name ];
		}
	}

	/**
	 * Get the name of this backup schedule
	 *
	 * @return string
	 */
	public function get_name() {
		return ucwords( $this->get_type() ) . ' ' . $this->get_reoccurrence();
	}

	/**
	 * Get the type of backup
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->options['type'];
	}

	/**
	 * Set the type of backup
	 *
	 * @param string $type
	 */
	public function set_type( $type ) {
		if ( ! isset( $this->options['type'] ) || $this->options['type'] !== $type ) {
			$this->options['type'] = $type;
		}
	}

	/**
	 * Get the exclude rules
	 *
	 * @return array
	 */
	public function get_excludes() {
		return new Excludes( $this->options['excludes'] );
	}

	/**
	 * Set the exclude rules
	 *
	 * @param mixed $excludes A comma separated list or array of exclude rules
	 * @param bool $append Whether to replace or append to existing rules
	 *
	 * @return string
	 */
	public function set_excludes( $exclude_rules, $append = false ) {

		// Normalize the exclude rules before we save them
		$excludes = new Excludes;
		$excludes = $excludes->normalize( (array) $exclude_rules );

		// If these are valid excludes and they are different save them
		if ( empty( $this->options['excludes'] ) || $this->options['excludes'] !== $excludes ) {
			$this->options['excludes'] = $append && ! empty( $this->options['excludes'] ) ? array_merge( (array) $this->options['excludes'], (array) $excludes ) : (array) $excludes;
		}

	}

	/**
	 * Get the maximum number of backups to keep
	 *
	 * @return int
	 */
	public function get_max_backups() {
		return (int) $this->options['max_backups'];
	}

	/**
	 * Set the maximum number of backups to keep
	 *
	 * @param int $max
	 *
	 * @return WP_Error|boolean
	 */
	public function set_max_backups( $max ) {
		$this->options['max_backups'] = $max;
	}

	public function get_status() {
		return $this->status;
	}

	/**
	 * Back compat with old set_status mathod
	 *
 	 * @deprecated 3.4 Backup->status->set_status()
	 */
	public function set_status( $message ) {
		_deprecated_function( __FUNCTION__, '3.4', 'Backup->status->set_status()' );
		$this->status->set_status( $message );
	}

	/**
	 * Get the array of services options for this schedule
	 *
	 * @param      $service
	 * @param null $option
	 *
	 * @return array
	 */
	public function get_service_options( $service, $option = null ) {

		if ( ! is_null( $option ) ) {

			if ( isset( $this->options[ $service ][ $option ] ) ) {
				return $this->options[ $service ][ $option ];
			}

			return array();

		}

		if ( isset( $this->options[ $service ] ) ) {
			return $this->options[ $service ];
		}

		return array();

	}

	/**
	 * Set the service options for this schedule
	 *
	 * @param $service
	 * @param array $options
	 */
	public function set_service_options( $service, Array $options ) {
		$this->options[ $service ] = $options;
	}

	/**
	 * Get the start time for the schedule
	 *
	 * @return int timestamp || 0 for manual only schedules
	 */
	public function get_schedule_start_time( $gmt = true ) {

		if ( 'manually' === $this->get_reoccurrence() ) {
			return 0;
		}

		if ( ! $gmt ) {
			$offset = get_option( 'gmt_offset' ) * 3600;
		} else {
			$offset = 0;
		}

		if ( ! empty( $this->options['schedule_start_time'] ) ) {
			return $this->options['schedule_start_time'] + $offset;
		}

		$this->set_schedule_start_time( time() );

		return time() + $offset;

	}

	/**
	 * Set the schedule start time.
	 *
	 * @param timestamp $time
	 */
	public function set_schedule_start_time( $time ) {

		$this->options['schedule_start_time'] = $time;

		$this->schedule();

	}

	/**
	 * Get the schedule reoccurrence
	 *
	 */
	public function get_reoccurrence() {
		return $this->options['reoccurrence'];
	}

	/**
	 * Set the schedule reoccurrence
	 *
	 * @param string $reoccurrence
	 *
	 * @return \WP_Error|null|boolean
	 */
	public function set_reoccurrence( $reoccurrence ) {

		$hmbkp_schedules = cron_schedules();

		// Check it's valid
		if ( ! is_string( $reoccurrence ) || ! trim( $reoccurrence ) || ( ! in_array( $reoccurrence, array_keys( $hmbkp_schedules ) ) ) && 'manually' !== $reoccurrence ) {
			return new \WP_Error( 'hmbkp_invalid_argument_error', sprintf( __( 'Argument 1 for %s must be a valid cron recurrence or "manually"', 'backupwordpress' ), __METHOD__ ) );
		}

		// If the recurrence is already set to the same thing then there's no need to continue
		if ( isset( $this->options['reoccurrence'] ) && $this->options['reoccurrence'] === $reoccurrence && $this->is_cron_scheduled() ) {
			return;
		}


		$this->options['reoccurrence'] = $reoccurrence;

		if ( 'manually' === $reoccurrence ) {
			$this->unschedule();

		} else {
			$this->schedule();
		}

		return true;

	}

	/**
	 * Get the interval between backups
	 *
	 * @return int
	 */
	public function get_interval() {

		$hmbkp_schedules = cron_schedules();

		if ( 'manually' === $this->get_reoccurrence() ) {
			return 0;
		}

		return $hmbkp_schedules[ $this->get_reoccurrence() ]['interval'];

	}

	/**
	 * Get the next occurrence of this scheduled backup
	 *
	 */
	public function get_next_occurrence( $gmt = true ) {

		$time = wp_next_scheduled( 'hmbkp_schedule_hook', array( 'id' => $this->get_id() ) );

		if ( ! $time ) {
			$time = 0;
		}

		if ( ! $gmt ) {
			$time += get_option( 'gmt_offset' ) * 3600;
		}

		return $time;

	}

	public function is_cron_scheduled() {
		return (bool) $this->get_next_occurrence();
	}

	/**
	 * Schedule the backup cron
	 *
	 */
	public function schedule() {

		// Clear any existing hooks
		$this->unschedule();

		$schedule_timestamp = $this->get_schedule_start_time();

		wp_schedule_event( $schedule_timestamp, $this->get_reoccurrence(), 'hmbkp_schedule_hook', array( 'id' => $this->get_id() ) );

	}


	/**
	 * Unschedule the backup cron.
	 *
	 * @return void
	 */
	public function unschedule() {
		wp_clear_scheduled_hook( 'hmbkp_schedule_hook', array( 'id' => $this->get_id() ) );
	}

	/**
	 * Run the backup
	 *
	 */
	public function run() {

		// Don't run if this schedule is already running
		if ( $this->status->is_started() ) {
			return;
		}

		// Setup our Site Backup Object
		$backup = new Backup( $this->get_backup_filename(), $this->get_database_dump_filename() );
		$backup->set_type( $this->get_type() );
		$backup->set_excludes( $this->get_excludes() );
		$backup->set_status( $this->status );

		$this->do_action( 'hmbkp_backup_started', $backup );

		$this->status->start( $this->get_backup_filename(), __( 'Starting backup...', 'backupwordpress' ) );

		$this->status->set_status( __( 'Deleting old backups...', 'backupwordpress' ) );

		// Delete old backups now in-case we fatal error during the backup process
		$this->delete_old_backups();

		$backup->run();

		$errors = array_merge( $backup->errors, $backup->warnings );
		$notices = array();
		foreach ( $errors as $key => $error ) {
			$key = str_replace( array( __NAMESPACE__ . '\\', '_File_Backup_Engine', '_Database_Backup_Engine' ), array( '', '', '' ), $key );
			$notices[] = $key . ': ' . implode( ', ', $error );
		}
		Notices::get_instance()->set_notices( 'backup_errors', $notices );

		$this->status->set_status( __( 'Deleting old backups...', 'backupwordpress' ) );

		// Delete old backups again
		$this->delete_old_backups();

		$this->do_action( 'hmbkp_backup_complete', $backup );

		$this->status->finish();
		$this->update_average_schedule_run_time( $this->status->get_start_time(), time() );

	}

	public function get_backup_filename() {

		if ( $this->status->is_started() ) {
			$this->backup_filename = $this->status->get_backup_filename();
		}

		return $this->backup_filename;
	}

	public function get_database_dump_filename() {
		return $this->database_dump_filename;
	}

	/**
	 * Hook into the actions fired in the Backup class and set the status
	 *
	 * @param $action
	 */
	public function do_action( $action, Backup $backup ) {

		// Pass the actions to all the services
		// Todo should be decoupled into the service class
		foreach ( Services::get_services( $this ) as $service ) {
			if ( is_wp_error( $service ) ) {
				return $service;
			}
			$service->action( $action, $backup );
		}

	}

	/**
	 * Calculate schedule run time.
	 *
	 * @param int Timestamp $end
	 */
	public function update_average_schedule_run_time( $start, $end ) {

		if ( $end <= $start ) {
			// Something went wrong, ignore.
			return;
		}

		$diff = (int) abs( $end - $start );

		if ( isset( $this->options['duration_total'] ) && isset( $this->options['backup_run_count'] ) ) {

			$this->options['duration_total'] += $diff;
			$this->options['backup_run_count'] ++;

		} else {

			$this->options['duration_total'] = $diff;
			$this->options['backup_run_count'] = 1;

		}

		$this->save();
	}

	/**
	 * Calculates the average run time for this schedule.
	 *
	 * @return string
	 */
	public function get_schedule_average_duration() {

		$duration = 'Unknown';

		if ( ! isset( $this->options['duration_total'] ) || ! isset( $this->options['backup_run_count'] ) ) {
			return $duration;
		}

		if ( 0 === (int) $this->options['backup_run_count'] ) {
			return $duration;
		}

		$average_run_time = (int) $this->options['duration_total'] / (int) $this->options['backup_run_count'];

		if ( $average_run_time < HOUR_IN_SECONDS ) {

			$mins = round( $average_run_time / MINUTE_IN_SECONDS );

			if ( $mins <= 1 ) {
				$mins = 1;
			}

			/* translators: min=minute */
			$duration = sprintf( _n( '%s min', '%s mins', $mins, 'backupwordpress' ), $mins );

		} elseif ( $average_run_time < DAY_IN_SECONDS && $average_run_time >= HOUR_IN_SECONDS ) {

			$hours = round( $average_run_time / HOUR_IN_SECONDS );

			if ( $hours <= 1 ) {
				$hours = 1;
			}

			$duration = sprintf( _n( '%s hour', '%s hours', $hours, 'backupwordpress' ), $hours );
		}

		return $duration;
	}

	/**
	 * Get the backups created by this schedule
	 *
	 * @todo   look into using recursiveDirectoryIterator and recursiveRegexIterator
	 * @return string[] - file paths of the backups
	 */
	public function get_backups() {

		$files = array();

		if ( $handle = @opendir( Path::get_path() ) ) {

			while ( false !== ( $file = readdir( $handle ) ) ) {

				if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'zip' && strpos( $file, $this->get_id() ) !== false && ( isset( $this->status ) && $this->get_backup_filename() !== $file ) ) {
					$files[ @filemtime( trailingslashit( Path::get_path() ) . $file ) ] = trailingslashit( Path::get_path() ) . $file;
				}

			}

			closedir( $handle );

		}

		krsort( $files );

		return $files;

	}

	/**
	 * Delete old backups
	 *
	 * @access private
	 */
	public function delete_old_backups() {

		if ( count( $this->get_backups() ) <= $this->get_max_backups() ) {
			return;
		}

		array_map( array( $this, 'delete_backup' ), array_slice( $this->get_backups(), $this->get_max_backups() ) );

	}

	/**
	 * Delete a specific back up file created by this schedule
	 *
	 * @param string $filepath
	 *
	 * @return \WP_Error|boolean
	 */
	public function delete_backup( $filepath ) {

		// Check that it's a valid filepath
		if ( empty( $filepath ) || ! is_string( $filepath ) ) {
			return new \WP_Error( 'hmbkp_empty_string_error', sprintf( __( 'Argument 1 for %s must be a non-empty string', 'backupwordpress' ), __METHOD__ ) );
		}

		// Make sure it exists
		if ( ! file_exists( $filepath ) ) {
			return new \WP_Error( 'hmbkp_file_error', sprintf( __( '%s doesn\'t exist', 'backupwordpress' ), $filepath ) );
		}

		// Make sure it was created by this schedule
		if ( strpos( $filepath, $this->get_id() ) === false ) {
			return new \WP_Error( 'hmbkp_backup_error', __( 'That backup wasn\'t created by this schedule', 'backupwordpress' ) );
		}

		unlink( $filepath );

		return true;

	}

	/**
	 * Delete all back up files created by this schedule
	 *
	 */
	public function delete_backups() {
		array_map( array( $this, 'delete_backup' ), $this->get_backups() );
	}

	/**
	 * Save the schedules options.
	 *
	 */
	public function save() {

		// Only save them if they have changed
		if ( $this->options !== get_option( 'hmbkp_schedule_' . $this->get_id() ) ) {
			update_option( 'hmbkp_schedule_' . $this->get_id(), $this->options );
		}

	}

	/**
	 * Cancel this schedule
	 *
	 * Cancels the cron job, removes the schedules options
	 * and optionally deletes all backups created by
	 * this schedule.
	 *
	 */
	public function cancel( $delete_backups = false ) {

		// Delete the schedule options
		delete_option( 'hmbkp_schedule_' . $this->get_id() );

		// Clear any existing schedules
		$this->unschedule();

		// Delete it's backups
		if ( $delete_backups ) {
			$this->delete_backups();
		}

	}

}
