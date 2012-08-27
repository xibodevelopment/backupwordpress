<?php

/**
 * Extend HM Backup with scheduling and backup file management
 *
 * @extends HM_Backup
 */
class HMBKP_Scheduled_Backup extends HM_Backup {

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
	private $options = array();

	/**
	 * The unique hook name for this schedule
	 *
	 * @var string
	 * @access private
	 */
	private $schedule_hook = '';

	/**
	 * The filepath for the .running file which
	 * is used to track whether a backup is currently in
	 * progress
	 */
	private $schedule_running_filepath = '';

	/**
	 * The schedule start time
	 *
	 * (default value: current_time( 'timestamp' ))
	 *
	 * @var mixed
	 * @access private
	 */
	private $schedule_start_time = 0;

	/**
	 * Take a file size and return a human readable
	 * version
	 *
	 * @access public
	 * @static
	 * @param int $size
	 * @param string $unit. (default: null)
	 * @param string $format. (default: '%01.2f %s')
	 * @param bool $si. (default: true)
	 * @return int
	 */
	public static function human_filesize( $size, $unit = null, $format = '%01.2f %s', $si = true ) {

		// Units
		if ( $si === true ) :
			$sizes = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB' );
			$mod   = 1000;

		else :
			$sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
			$mod   = 1024;

		endif;

		$ii = count( $sizes ) - 1;

		// Max unit
		$unit = array_search( (string) $unit, $sizes );

		if ( is_null( $unit ) || $unit === false )
			$unit = $ii;

		// Loop
		$i = 0;

		while ( $unit != $i && $size >= 1024 && $i < $ii ) {
			$size /= $mod;
			$i++;
		}

		return sprintf( $format, $size, $sizes[$i] );

	}

	/**
	 * Setup the schedule object
	 *
	 * Loads the options from the database and populates properties
	 *
	 * @access public
	 * @param string $id
	 */
	public function __construct( $id ) {

		// Verify the schedule id
		if ( ! is_string( $id ) || ! trim( $id ) || ! is_string( $id ) )
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a non empty string' );

		// Setup HM Backup
		parent::__construct();

		// Store id for later
		$this->id = $id;

		// Load the options
		$this->options = array_filter( (array) get_option( 'hmbkp_schedule_' . $this->get_id() ) );

		// Setup the schedule hook
		$this->schedule_hook = 'hmbkp_schedule_' . $this->get_id() . '_hook';

		$this->schedule_running_filepath = $this->get_path() . '/.schedule-' . $this->get_id() . '-running';

		// Some properties can be overridden with a defines
		if ( defined( 'HMBKP_ROOT' ) && HMBKP_ROOT )
			$this->set_root( HMBKP_ROOT );

		if ( defined( 'HMBKP_EXCLUDES' ) && HMBKP_EXCLUDES )
			$this->set_excludes( HMBKP_EXCLUDES );

		if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) )
			$this->set_mysqldump_command_path( HMBKP_MYSQLDUMP_PATH );

		if ( defined( 'HMBKP_ZIP_PATH' ) )
			$this->set_zip_command_path( HMBKP_ZIP_PATH );

		if ( defined( 'HMBKP_ZIP_PATH' ) && HMBKP_ZIP_PATH === 'PclZip' )
			$this->skip_zip_archive = true;

		// Pass type and excludes up to HM Backup
		parent::set_type( $this->get_type() );
		parent::set_excludes( $this->get_excludes() );

		// Set the path - TODO remove external function dependancy
		$this->set_path( hmbkp_path() );

		// Set the archive filename to site name + schedule slug + date
		$this->set_archive_filename( strtolower( sanitize_file_name( implode( '-', array( get_bloginfo( 'name' ), $this->get_id(), $this->get_type(), date( 'Y-m-d-H-i-s', current_time( 'timestamp' ) ) ) ) ) ) . '.zip' );

	}

	/**
	 * Get the id for this schedule
	 *
	 * @access public
	 */
	public function get_id() {
		return esc_attr( $this->id );
	}

	/**
	 * Get a slugified version of name
	 *
	 * @access public
	 */
	public function get_slug() {

		// We cache slug in $this to save expensive calls to sanitize_title
		if ( isset( $this->slug ) )
			return $this->slug;

		return $this->slug = sanitize_title( $this->get_name() );

	}

	/**
	 * Get the name of this backup schedule
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {

		return ucwords( $this->get_type() ) . ' ' . $this->get_reoccurrence();

	}

	/**
	 * Get the type of backup
	 *
	 * @access public
	 * @return string
	 */
	public function get_type() {

		if ( empty( $this->options['type'] ) )
			$this->set_type( 'complete' );

		return esc_attr( $this->options['type'] );

	}

	/**
	 * Set the type of backup
	 *
	 * @access public
	 * @param string $type
	 */
	public function set_type( $type ) {

		if ( parent::set_type( $type ) !== false )
			$this->options['type'] = $type;

	}

	/**
	 * Get the exclude rules
	 *
	 * @access public
	 * @return array
	 */
	public function get_excludes() {

		if ( ! empty( $this->options['excludes'] ) )
			parent::set_excludes( $this->options['excludes'] );

		return parent::get_excludes();

	}

	/**
	 * Set the exclude rules
	 *
	 * @access public
	 * @param mixed $excludes A comma separated list or array of exclude rules
	 * @param bool $append Whether to replace or append to existing rules
	 * @return string
	 */
	public function set_excludes( $excludes, $append = false ) {

		if ( parent::set_excludes( $excludes, $append ) !== false )
			$this->options['excludes'] = parent::get_excludes();

	}

	/**
	 * Get the maximum number of backups to keep
	 *
	 * @access public
	 */
	public function get_max_backups() {

		if ( empty( $this->options['max_backups'] ) )
			$this->set_max_backups( 10 );

		return (int) esc_attr( $this->options['max_backups'] );

	}

	/**
	 * Set the maximum number of backups to keep
	 *
	 * @access public
	 * @param int $max
	 */
	public function set_max_backups( $max ) {

		if ( empty( $max ) || ! is_int( $max ) )
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a valid integer' );

		$this->options['max_backups'] = $max;

	}

	/**
	 * Get the array of services options for this schedule
	 *
	 * @access public
	 * @return array
	 */
	public function get_service_options( $service, $option = null ) {

		if ( ! is_null( $option ) && isset( $this->options[$service][$option] ) )
			return $this->options[$service][$option];

		if ( isset( $this->options[$service] ) )
			return $this->options[$service];

		return array();

	}

	/**
	 * Set the service options for this schedule
	 *
	 * @access public
	 */
	public function set_service_options( $service, Array $options ) {

		$this->options[$service] = $options;

	}

	/**
	 * Calculate the size of the backup
	 *
	 * Doesn't account for
	 * compression
	 *
	 * @access public
	 * @param bool $cached Whether to return from cache
	 * @return string
	 */
	public function get_filesize( $cached = true ) {

		if ( ! $cached || ! $filesize = get_transient( 'hmbkp_schedule_' . $this->get_id() . '_filesize' ) ) {

			$filesize = 0;

	    	// Don't include database if file only
			if ( $this->get_type() != 'file' ) {

	    		global $wpdb;

	    		$res = $wpdb->get_results( 'SHOW TABLE STATUS FROM ' . DB_NAME, ARRAY_A );

	    		foreach ( $res as $r )
	    			$filesize += (float) $r['Data_length'];

	    	}

	    	// Don't include files if database only
	   		if ( $this->get_type() != 'database' ) {

	    		// Get rid of any cached filesizes
	    		clearstatcache();

				foreach ( $this->get_files() as $file )
					$filesize += (float) $file->getSize();

			}

			// Cache for a day
			set_transient( time() + 60 * 60 * 24, 'hmbkp_schedule_' . $this->get_id() . '_filesize', $filesize );

		}

	    return self::human_filesize( $filesize, null, '%01u %s' );

	}

	public function get_schedule_start_time() {

		if ( ! $this->schedule_start_time )
			$this->set_schedule_start_time( current_time( 'timestamp' ) );

		return $this->schedule_start_time;

	}

	public function set_schedule_start_time( $timestamp ) {

		if ( (string) (int) $timestamp !== (string) $timestamp )
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a valid UNIX timestamp' );

		$this->schedule_start_time = $timestamp;

	}

	/**
	 * Get the schedule reoccurrence
	 *
	 * @access public
	 */
	public function get_reoccurrence() {

		if ( empty( $this->options['reoccurrence'] ) )
			$this->set_reoccurrence( 'weekly' );

		return esc_attr( $this->options['reoccurrence'] );

	}

	/**
	 * Set the schedule reoccurrence
	 *
	 * @access public
	 * @param string $reoccurrence
	 */
	public function set_reoccurrence( $reoccurrence ) {

		// Check it's valid
		if ( ! is_string( $reoccurrence ) || ! trim( $reoccurrence ) || ! in_array( $reoccurrence, array_keys( wp_get_schedules() ) ) )
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a valid cron reoccurrence' );

		if ( isset( $this->options['reoccurrence'] ) && $this->options['reoccurrence'] === $reoccurrence )
			return;

		$this->options['reoccurrence'] = $reoccurrence;

		$this->schedule();

	}

	/**
	 * Get the interval between backups
	 *
	 * @access public
	 * @return int
	 */
	public function get_interval() {

		$schedules = wp_get_schedules();

		return $schedules[$this->get_reoccurrence()]['interval'];

	}

	/**
	 * Get the next occurrence of this scheduled backup
	 *
	 * @access public
	 */
	public function get_next_occurrence() {

		return wp_next_scheduled( $this->schedule_hook );

	}

	/**
	 * Schedule the cron
	 *
	 * @access public
	 */
	public function schedule() {

		// Clear any existing hooks
		wp_clear_scheduled_hook( $this->schedule_hook );

		wp_schedule_event( $this->get_schedule_start_time() + $this->get_interval(), $this->get_reoccurrence(), $this->schedule_hook );

		// Hook the backu into the schedule hook
		add_action( $this->schedule_hook, array( $this, 'run' ) );

	}

	/**
	 * Run the backup
	 *
	 * @access public
	 */
	public function run() {

		// Mark the backup as started
		$this->set_status( __( 'Backup started', 'hmbkp' ) );

		$this->backup();

		// Delete the backup running file
		if ( file_exists( $this->schedule_running_filepath ) )
			unlink( $this->schedule_running_filepath );

		$this->delete_old_backups();

	}

	public function get_status() {

		if ( ! file_exists( $this->schedule_running_filepath ) )
			return '';

		return end( explode( '::', file_get_contents( $this->schedule_running_filepath ) ) );

	}

	public function get_running_backup_filename() {

		if ( ! file_exists( $this->schedule_running_filepath ) )
			return '';

		return reset( explode( '::', file_get_contents( $this->schedule_running_filepath ) ) );
	}

	public function set_status( $message ) {

		if ( ! $handle = fopen( $this->schedule_running_filepath, 'w' ) )
			return;

		fwrite( $handle, $this->get_archive_filename() . '::' . $message );

		fclose( $handle );

	}

	/**
	 * Hook into the actions fired in HM Backup and set the status
	 *
	 * @return null
	 */
	protected function do_action( $action ) {

		switch ( $action ) :

			case 'hmbkp_archive_started' :

	    		$this->set_status( __( 'Creating zip archive', 'hmbkp' ) );

	    	break;

	    	case 'hmbkp_mysqldump_started' :

	    		$this->set_status( __( 'Dumping database', 'hmbkp' ) );

	    	break;

	    	case 'hmbkp_backup_complete' :

				if ( $this->get_errors() ) {

			    	$file = $this->get_path() . '/.backup_errors';

					if ( file_exists( $file ) )
						unlink( $file );

			    	if ( ! $handle = @fopen( $file, 'w' ) )
			    		return;

					fwrite( $handle, json_encode( $this->get_errors() ) );

			    	fclose( $handle );

			    }

			    if ( $this->get_warnings() ) {

					$file = $this->get_path() . '/.backup_warnings';

					if ( file_exists( $file ) )
			  			unlink( $file );

					if ( ! $handle = @fopen( $file, 'w' ) )
			  	  		return;

			  		fwrite( $handle, json_encode( $this->get_warnings() ) );

					fclose( $handle );

				}

	    	break;

	    endswitch;

	    // Pass the actions to all the services
	    foreach ( HMBKP_Services::get_services( $this ) as $service )
	    	$service->action( $action );

	    // Fire the parent function as well
	    parent::do_action( $action );

	}

	/**
	 * Get the backups created by this schedule
	 *
	 * @todo look into using recursiveDirectoryIterator and recursiveRegexIterator
	 * @access public
	 */
	public function get_backups() {

		$files = array();

		if ( $handle = @opendir( $this->get_path() ) ) {

			while ( false !== ( $file = readdir( $handle ) ) )
				if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'zip' && strpos( $file, $this->get_id() ) !== false && $this->get_running_backup_filename() != $file )
		     		$files[@filemtime( trailingslashit( $this->get_path() ) . $file )] = trailingslashit( $this->get_path() ) . $file;

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

		if ( count( $this->get_backups() ) <= $this->get_max_backups() )
	   		return;

		array_map( array( $this, 'delete_backup' ), array_slice( $this->get_backups(), $this->get_max_backups() ) );

	}

	/**
	 * Delete a specific back up file created by this schedule
	 *
	 * @access public
	 * @param string $filepath
	 */
	public function delete_backup( $filepath ) {

		// Check that it's a valid filepath
		if ( empty( $filepath ) || ! is_string( $filepath ) )
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a non empty string' );

		// Make sure it exists
		if ( ! file_exists( $filepath ) )
			throw new Exception( $filepath . ' doesn\'t exist' );

		// Make sure it was created by this schedule
		if ( strpos( $filepath, $this->get_id() ) === false )
			throw new Exception( 'That backup wasn\'t created by this schedule' );

		unlink( $filepath );

	}

	/**
	 * Delete all back up files created by this schedule
	 *
	 * @access public
	 */
	public function delete_backups() {

		array_map( array( $this, 'delete_backup' ), $this->get_backups() );

	}

	/**
	 * Save the schedules options.
	 *
	 * @access public
	 */
	public function save() {

		// Only save them if they have changed
		if ( $this->options !== get_option( 'hmbkp_schedule_' . $this->get_id() ) )
			update_option( 'hmbkp_schedule_' . $this->get_id(), $this->options );

	}

	/**
	 * Cancel this schedule
	 *
	 * Cancels the cron job, removes the schedules options
	 * and optionally deletes all backups crated by
	 * this schedule.
	 *
	 * @access public
	 */
	public function cancel() {

		// Delete the schedule optoins
		delete_option( 'hmbkp_schedule_' . $this->get_id() );

		// Clear any existing schedules
		wp_clear_scheduled_hook( $this->schedule_hook );

		// Delete it's backups
		$this->delete_backups();

	}

}