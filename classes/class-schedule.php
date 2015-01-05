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
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a non empty string' );
		}

		// Setup HM Backup
		parent::__construct();

		// Store id for later
		$this->id = $id;

		// Load the options
		$this->options = array_filter( (array) get_option( 'hmbkp_schedule_' . $this->get_id() ) );

		// Some properties can be overridden with defines
		if ( defined( 'HMBKP_ROOT' ) && HMBKP_ROOT ) {
			$this->set_root( HMBKP_ROOT );
		}

		if ( defined( 'HMBKP_EXCLUDE' ) && HMBKP_EXCLUDE ) {
			parent::set_excludes( HMBKP_EXCLUDE, true );
		}

		parent::set_excludes( $this->default_excludes(), true );

		if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) {
			$this->set_mysqldump_command_path( HMBKP_MYSQLDUMP_PATH );
		}

		if ( defined( 'HMBKP_ZIP_PATH' ) ) {
			$this->set_zip_command_path( HMBKP_ZIP_PATH );
		}

		if ( defined( 'HMBKP_ZIP_PATH' ) && HMBKP_ZIP_PATH === 'PclZip' && $this->skip_zip_archive = true ) {
			$this->set_zip_command_path( false );
		}

		if ( defined( 'HMBKP_SCHEDULE_START_TIME' ) && strtotime( 'HMBKP_SCHEDULE_START_TIME' ) ) {
			$this->set_schedule_start_time( strtotime( 'HMBKP_SCHEDULE_START_TIME' ) );
		}

		// Set the path - TODO remove external function dependancy
		$this->set_path( hmbkp_path() );

		// Set the archive filename to site name + schedule slug + date
		$this->set_archive_filename( implode( '-', array( sanitize_title( str_ireplace( array( 'http://', 'https://', 'www' ), '', home_url() ) ), $this->get_id(), $this->get_type(), date( 'Y-m-d-H-i-s', current_time( 'timestamp' ) ) ) ) . '.zip' );
		$this->set_database_dump_filename( implode( '-', array( sanitize_title( str_ireplace( array( 'http://', 'https://', 'www' ), '', home_url() ) ), $this->get_id(), $this->get_type(), date( 'Y-m-d-H-i-s', current_time( 'timestamp' ) ) ) ) . '.sql' );

		// Setup the schedule if it isn't set
		if ( ( ! $this->is_cron_scheduled() && $this->get_reoccurrence() !== 'manually' ) ) {
			$this->schedule();
		}

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
		if ( ! empty( $this->slug ) ) {
			return $this->slug;
		}

		return $this->slug = sanitize_title( $this->get_name() );

	}

	/**
	 * Returns the given option value or WP_Error if it doesn't exist
	 *
	 * @param $option_name
	 *
	 * @return WP_Error
	 */
	public function get_schedule_option( $option_name ) {

		if ( isset( $this->options[ $option_name ] ) ) {
			return $this->options[ $option_name ];
		} else {
			return new WP_Error( 'invalid_option_name', __( 'Invalid Option Name', 'backupwordpress' ) );
		}
	}

	/**
	 * Get the name of this backup schedule
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {

		$recurrence = ( 'manually' === $this->get_reoccurrence() ) ? $this->get_reoccurrence() : substr( $this->get_reoccurrence(), 6 );

		return ucwords( $this->get_type() ) . ' ' . $recurrence;

	}

	/**
	 * Get the type of backup
	 *
	 * @access public
	 * @return string
	 */
	public function get_type() {

		if ( empty( $this->options['type'] ) ) {
			$this->set_type( 'complete' );
		}

		return $this->options['type'];

	}

	/**
	 * Set the type of backup
	 *
	 * @access public
	 *
	 * @param string $type
	 */
	public function set_type( $type ) {

		if ( isset( $this->options['type'] ) && $this->options['type'] === $type ) {
			return;
		}

		parent::set_type( $type );

		$this->options['type'] = $type;

	}

	/**
	 * Get the exclude rules
	 *
	 * @access public
	 * @return array
	 */
	public function get_excludes() {

		if ( ! empty( $this->options['excludes'] ) ) {
			parent::set_excludes( $this->options['excludes'] );
		}

		return parent::get_excludes();

	}

	/**
	 * Set the exclude rules
	 *
	 * @access public
	 *
	 * @param mixed $excludes A comma separated list or array of exclude rules
	 * @param bool $append Whether to replace or append to existing rules
	 *
	 * @return string
	 */
	public function set_excludes( $excludes, $append = false ) {

		// Use the validation from HM_Backup::set_excludes
		parent::set_excludes( $excludes, $append );

		// If these are valid excludes and they are different save them
		if ( parent::get_excludes() && ( empty( $this->options['excludes'] ) || $this->options['excludes'] !== parent::get_excludes() ) ) {

			$this->options['excludes'] = $append && ! empty( $this->options['excludes'] ) ? array_merge( (array) $this->options['excludes'], parent::get_excludes() ) : parent::get_excludes();;

			parent::set_excludes( $this->options['excludes'] );

		}

	}

	/**
	 * Get the maximum number of backups to keep
	 *
	 * @access public
	 */
	public function get_max_backups() {

		if ( empty( $this->options['max_backups'] ) ) {
			$this->set_max_backups( 3 );
		}

		return (int) esc_attr( $this->options['max_backups'] );

	}

	/**
	 * Set the maximum number of backups to keep
	 *
	 * @param int $max
	 *
	 * @return bool|WP_Error
	 */
	public function set_max_backups( $max ) {

		if ( empty( $max ) || ! is_int( $max ) ) {
			return new WP_Error( 'hmbkp_invalid_type_error', sprintf( __( 'Argument 1 for %s must be a valid integer', 'backupwordpress' ), __METHOD__ ) );
		}

		$this->options['max_backups'] = $max;

		return true;
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
	 * @access public
	 */
	public function set_service_options( $service, Array $options ) {

		$this->options[ $service ] = $options;

	}

	/**
	 * Calculate the size total size of the database + files
	 *
	 * Doesn't account for compression
	 *
	 * @access public
	 * @return string
	 */
	public function get_site_size() {

		$size = 0;

		// Don't include database if file only
		if ( 'file' !== $this->get_type() ) {

			global $wpdb;

			$tables = $wpdb->get_results( 'SHOW TABLE STATUS FROM `' . DB_NAME . '`', ARRAY_A );

			foreach ( $tables as $table ) {
				$size += (float) $table['Data_length'];
			}
		}

		// Don't include files if database only
		if ( 'database' !== $this->get_type() ) {

			$root = new SplFileInfo( $this->get_root() );

			$size += $this->filesize( $root, true );

		}

		return $size;

	}

	/**
	 * Convenience function to format the file size
	 *
	 * @param bool $cached
	 *
	 * @return bool|string
	 */
	public function get_formatted_site_size() {
		return size_format( $this->get_site_size() );
	}

	/**
	 * Whether the total filesize is being calculated
	 *
	 * @return int            The total of the file or directory
	 */
	function is_site_size_being_calculated() {
		return false !== get_transient( 'hmbkp_directory_filesizes_running' );
	}

	/**
	 * Whether the total filesize is being calculated
	 *
	 * @return bool The total of the file or directory
	 */
	function is_site_size_cached() {
		return false !== get_transient( 'hmbkp_directory_filesizes' );
	}

	/**
	 * Return the single depth list of files and subdirectories in $directory ordered by total filesize
	 *
	 * Will schedule background threads to recursively calculate the filesize of subdirectories.
	 * The total filesize of each directory and subdirectory is cached in a transient for 1 week.
	 *
	 * @param string $directory The directory to scan
	 *
	 * @return array returns an array of files ordered by filesize
	 */
	public function list_directory_by_total_filesize( $directory ) {

		$files = $files_with_no_size = $empty_files = $files_with_size = $unreadable_files = array();

		if ( ! is_dir( $directory ) ) {
			return $files;
		}

		$handle = opendir( $directory );

		if ( ! $handle ) {
			return $files;
		}

		while ( $file_handle = readdir( $handle ) ) {

			// Ignore current dir and containing dir
			if ( '.' === $file_handle || '..' === $file_handle ) {
				continue;
			}

			$file = new SplFileInfo( HM_Backup::conform_dir( trailingslashit( $directory ) . $file_handle ) );

			// Unreadable files are moved to the bottom
			if ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) {
				$unreadable_files[] = $file;
				continue;
			}

			// Get the total filesize for each file and directory
			$filesize = $this->filesize( $file );

			if ( $filesize ) {

				// If there is already a file with exactly the same filesize then let's keep increasing the filesize of this one until we don't have a clash
				while ( array_key_exists( $filesize, $files_with_size ) ) {
					$filesize++;
				}

				$files_with_size[ $filesize ] = $file;

			} elseif ( 0 === $filesize ) {

				$empty_files[] = $file;

			} else {

				$files_with_no_size[] = $file;

			}

		}

		closedir( $handle );

		// Sort files largest first
		krsort( $files_with_size );

		// Add 0 byte files / directories to the bottom
		$files = $files_with_size + array_merge( $empty_files, $unreadable_files );

		// Add directories that are still calculating to the top
		if ( $files_with_no_size ) {

			// We have to loop as merging or concatenating the array would re-flow the keys which we don't want because the filesize is stored in the key
			foreach ( $files_with_no_size as $file ) {
				array_unshift( $files, $file );
			}
		}

		return $files;

	}

	/**
	 * Recursively scans a directory to calculate the total filesize
	 *
	 * Locks should be set by the caller with `set_transient( 'hmbkp_directory_filesizes_running', true, HOUR_IN_SECONDS );`
	 *
	 * @return array $directory_sizes    An array of directory paths => filesize sum of all files in directory
	 */
	public function recursive_filesize_scanner() {

		// Use the cached array directory sizes if available
		$directory_sizes = get_transient( 'hmbkp_directory_filesizes' );

		// If we do have it in cache then let's use it and also clear the lock
		if ( is_array( $directory_sizes ) ) {

			delete_transient( 'hmbkp_directory_filesizes_running' );

			return $directory_sizes;

		}

		$directory_sizes[ $this->get_root() ] = filesize( $this->get_root() );

		$files = $this->get_files();

		foreach ( $files as $file ) {

			if ( $file->isReadable() ) {
				$directory_sizes[ $file->getPathname() ] = $file->getSize();
			} else {
				$directory_sizes[ $file->getPathname() ] = 0;
			}

		}

		set_transient( 'hmbkp_directory_filesizes', $directory_sizes, DAY_IN_SECONDS );

		delete_transient( 'hmbkp_directory_filesizes_running' );

		return $directory_sizes;

	}

	/**
	 * Get the total filesize for a given file or directory
	 *
	 * If $file is a file then just return the result of `filesize()`.
	 * If $file is a directory then schedule a recursive filesize scan.
	 *
	 * @param SplFileInfo $file			The file or directory you want to know the size of
	 * @param bool $skip_excluded_files	Skip excluded files when calculating a directories total size
	 * @return int 						The total of the file or directory
	 */
	public function filesize( SplFileInfo $file, $skip_excluded_files = false ) {

		// Skip missing or unreadable files
		if ( ! file_exists( $file->getPathname() ) || ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) {
			return false;
		}

		// If it's a file then just pass back the filesize
		if ( $file->isFile() && $file->isReadable() ) {
			return $file->getSize();
		}

		// If it's a directory then pull it from the cached filesize array
		if ( $file->isDir() ) {

			// If we haven't calculated the site size yet then kick it off in a thread
			$directory_sizes = get_transient( 'hmbkp_directory_filesizes' );

			if ( ! is_array( $directory_sizes ) ) {

				if ( ! $this->is_site_size_being_calculated() ) {

					// Mark the filesize as being calculated
					set_transient( 'hmbkp_directory_filesizes_running', true, HOUR_IN_SECONDS );

					// Schedule a Backdrop task to trigger a recalculation
					$task = new HM_Backdrop_Task( array( $this, 'recursive_filesize_scanner' ) );
					$task->schedule();

				}

				return;

			}

			$current_pathname = trailingslashit( $file->getPathname() );
			$root = trailingslashit( $this->get_root() );

			foreach ( $directory_sizes as $path => $size ) {

				// Remove any files that aren't part of the current tree
				if ( false === strpos( $path, $current_pathname ) ) {
					unset( $directory_sizes[ $path ] );
				}

			}

			if ( $skip_excluded_files ) {

				$excludes = $this->exclude_string( 'regex' );

				foreach ( $directory_sizes as $path => $size ) {

					// Skip excluded files if we have excludes
					if ( $excludes && preg_match( '(' . $excludes . ')', str_ireplace( $root, '', HM_Backup::conform_dir( $path ) ) ) ) {
						unset( $directory_sizes[ $path ] );
					}

				}

			}

			// Directory size is now just a sum of all files across all sub directories
			return array_sum( $directory_sizes );

		}

	}

	/**
	 * Get the start time for the schedule
	 *
	 * @access public
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
	 * @param array $args
	 */
	public function set_schedule_start_time( $time ) {

		// Don't allow setting the start time in the past
		if ( (int) $time <= time() ) {
			return new WP_Error( 'hmbkp_invalid_argument_error', sprintf( __( 'Argument 1 for %s must be a valid future timestamp', 'backupwordpress' ), __METHOD__ ) );
		}

		$this->options['schedule_start_time'] = $time;

		$this->schedule();

	}

	/**
	 * Get the schedule reoccurrence
	 *
	 * @access public
	 */
	public function get_reoccurrence() {

		// Default to no reoccurrence
		if ( empty( $this->options['reoccurrence'] ) ) {
			$this->set_reoccurrence( 'manually' );
		}

		return $this->options['reoccurrence'];

	}

	/**
	 * Set the schedule reoccurrence
	 *
	 * @param string $reoccurrence
	 *
	 * @return bool|WP_Error
	 */
	public function set_reoccurrence( $reoccurrence ) {

		$hmbkp_schedules = $this->get_cron_schedules();

		// Check it's valid
		if ( ! is_string( $reoccurrence ) || ! trim( $reoccurrence ) || ( ! in_array( $reoccurrence, array_keys( $hmbkp_schedules ) ) ) && 'manually' !== $reoccurrence ) {
			return new WP_Error( 'hmbkp_invalid_argument_error', sprintf( __( 'Argument 1 for %s must be a valid cron reoccurrence or "manually"', 'backupwordpress' ), __METHOD__ ) );
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
	 * @access public
	 * @return int
	 */
	public function get_interval() {

		$hmbkp_schedules = $this->get_cron_schedules();

		if ( 'manually' === $this->get_reoccurrence() ) {
			return 0;
		}

		return $hmbkp_schedules[ $this->get_reoccurrence() ]['interval'];

	}

	/**
	 * Return an array of BackUpWordPress cron schedules
	 *
	 * @return array
	 */
	public static function get_cron_schedules() {

		$schedules = wp_get_schedules();

		// remove any schedule whose key is not prefixed with 'hmbkp_'
		foreach ( $schedules as $key => $arr ) {
			if ( ! preg_match( '/^hmbkp_/', $key ) ) {
				unset( $schedules[ $key ] );
			}
		}

		return $schedules;
	}

	/**
	 * Get the next occurrence of this scheduled backup
	 *
	 * @access public
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
	 * Get the path to the backup running file that stores the running backup status
	 *
	 * @access public
	 * @return string
	 */
	public function get_schedule_running_path() {
		return $this->get_path() . '/.schedule-' . $this->get_id() . '-running';
	}

	/**
	 * Schedule the backup cron
	 *
	 * @access public
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
	 * @access public
	 * @return void
	 */
	public function unschedule() {
		wp_clear_scheduled_hook( 'hmbkp_schedule_hook', array( 'id' => $this->get_id() ) );
	}

	/**
	 * Run the backup
	 *
	 * @access public
	 */
	public function run() {

		// Don't run if this schedule is already running
		if ( $this->get_running_backup_filename() ) {
			return;
		}

		// Mark the backup as started
		$this->set_status( __( 'Starting Backup', 'backupwordpress' ) );

		// Delete old backups now in-case we fatal error during the backup process
		$this->delete_old_backups();

		$this->backup();

		// Delete the backup running file
		if ( file_exists( $this->get_schedule_running_path() ) ) {
			unlink( $this->get_schedule_running_path() );
		}

		// Delete old backups again
		$this->delete_old_backups();

	}

	/**
	 * Get the filename that the running status is stored in.
	 *
	 * @access public
	 * @return string
	 */
	public function get_running_backup_filename() {

		if ( ! file_exists( $this->get_schedule_running_path() ) ) {
			return '';
		}

		$status = json_decode( file_get_contents( $this->get_schedule_running_path() ) );

		if ( ! empty( $status->filename ) ) {
			return $status->filename;
		}

		return '';

	}

	/**
	 * Get the status of the running backup.
	 *
	 * @access public
	 * @return string
	 */
	public function get_status() {

		if ( ! file_exists( $this->get_schedule_running_path() ) ) {
			return '';
		}


		$status = json_decode( file_get_contents( $this->get_schedule_running_path() ) );

		if ( ! empty( $status->status ) ) {
			return $status->status;
		}

		return '';

	}

	/**
	 * Set the status of the running backup
	 *
	 * @access public
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	public function set_status( $message ) {

		$status = json_encode( (object) array(
			'filename' => $this->get_archive_filename(),
			'started'  => $this->get_schedule_running_start_time(),
			'status'   => $message,
		) );

		if ( false === @file_put_contents( $this->get_schedule_running_path(), $status ) ) {
			throw new RuntimeException( sprintf( __( 'Error writing to file. (%s)', 'backpwordpress' ), $this->get_schedule_running_path() ) );
		}

	}

	/**
	 * Set the time that the current running backup was started
	 *
	 * @access public
	 * @return int $timestamp
	 */
	public function get_schedule_running_start_time() {

		if ( ! file_exists( $this->get_schedule_running_path() ) ) {
			return 0;
		}

		$status = json_decode( file_get_contents( $this->get_schedule_running_path() ) );

		if ( ! empty( $status->started ) && (int) (string) $status->started === $status->started ) {
			return $status->started;
		}

		return time();

	}

	/**
	 * Hook into the actions fired in HM Backup and set the status
	 *
	 * @param $action
	 */
	protected function do_action( $action ) {

		// Pass the actions to all the services
		foreach ( HMBKP_Services::get_services( $this ) as $service ) {
			$service->action( $action );
		}

		// Fire the parent function as well
		parent::do_action( $action );

		switch ( $action ) :

			case 'hmbkp_mysqldump_started' :

				$this->set_status( sprintf( __( 'Dumping Database %s', 'backupwordpress' ), '(<code>' . $this->get_mysqldump_method() . '</code>)' ) );
				break;

			case 'hmbkp_mysqldump_verify_started' :

				$this->set_status( sprintf( __( 'Verifying Database Dump %s', 'backupwordpress' ), '(<code>' . $this->get_mysqldump_method() . '</code>)' ) );
				break;

			case 'hmbkp_archive_started' :

				$this->set_status( sprintf( __( 'Creating zip archive %s', 'backupwordpress' ), '(<code>' . $this->get_archive_method() . '</code>)' ) );
				break;

			case 'hmbkp_archive_verify_started' :

				$this->set_status( sprintf( __( 'Verifying Zip Archive %s', 'backupwordpress' ), '(<code>' . $this->get_archive_method() . '</code>)' ) );
				break;

			case 'hmbkp_backup_complete' :

				$this->set_status( __( 'Finishing Backup', 'backupwordpress' ) );
				$this->update_average_schedule_run_time( $this->get_schedule_running_start_time(), time() );

				break;

			case 'hmbkp_error' :

				if ( $this->get_errors() ) {

					$file = $this->get_path() . '/.backup_errors';

					if ( file_exists( $file ) ) {
						@unlink( $file );
					}

					if ( ! $handle = @fopen( $file, 'w' ) ) {
						return;
					}

					fwrite( $handle, json_encode( $this->get_errors() ) );

					fclose( $handle );

				}

				break;

			case 'hmbkp_warning' :

				if ( $this->get_warnings() ) {

					$file = $this->get_path() . '/.backup_warnings';

					if ( file_exists( $file ) ) {
						@unlink( $file );
					}

					if ( ! $handle = @fopen( $file, 'w' ) ) {
						return;
					}

					fwrite( $handle, json_encode( $this->get_warnings() ) );

					fclose( $handle );

				}

				break;

		endswitch;

		do_action( 'hmbkp_action_complete', $action, $this );
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

		if ( ! isset( $this->options['duration_total'] ) || ! isset( $this->options['backup_run_count'] )  ) {
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
	 * @access public
	 * @return string[] - file paths of the backups
	 */
	public function get_backups() {

		$files = array();

		if ( $handle = @opendir( $this->get_path() ) ) {

			while ( false !== ( $file = readdir( $handle ) ) ) {

				if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'zip' && strpos( $file, $this->get_id() ) !== false && $this->get_running_backup_filename() !== $file ) {
					$files[ @filemtime( trailingslashit( $this->get_path() ) . $file ) ] = trailingslashit( $this->get_path() ) . $file;
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
	 * @return bool|WP_Error
	 */
	public function delete_backup( $filepath ) {

		// Check that it's a valid filepath
		if ( empty( $filepath ) || ! is_string( $filepath ) ) {
			return new WP_Error( 'hmbkp_empty_string_error', sprintf( __( 'Argument 1 for %s must be a non empty string', 'backupwordpress' ), __METHOD__ ) );
		}

		// Make sure it exists
		if ( ! file_exists( $filepath ) ) {
			return new WP_Error( 'hmbkp_file_error', sprintf( __( '%s doesn\'t exist', 'backupwordpress' ), $filepath ) );
		}

		// Make sure it was created by this schedule
		if ( strpos( $filepath, $this->get_id() ) === false ) {
			return new WP_Error( 'hmbkp_backup_error', __( 'That backup wasn\'t created by this schedule', 'backupwordpress' ) );
		}

		unlink( $filepath );

		return true;

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
		if ( $this->options !== get_option( 'hmbkp_schedule_' . $this->get_id() ) ) {
			update_option( 'hmbkp_schedule_' . $this->get_id(), $this->options );
		}

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

		// Delete the schedule options
		delete_option( 'hmbkp_schedule_' . $this->get_id() );

		// Clear any existing schedules
		$this->unschedule();

		// Delete it's backups
		$this->delete_backups();

	}

	/**
	 * Sets the default excluded folders fr a schedule
	 *
	 * @return Array
	 */
	public function default_excludes() {

		$excluded = array();

		// Leftover backup folders can be either under content dir, or under the uploads dir
		$hmn_upload_dir = wp_upload_dir();

		$backupwp_folders = $this->find_backup_folders( 'backwpup-', $hmn_upload_dir['path'] );

		if ( ! empty( $backupwp_folders ) ) {
			foreach ( $backupwp_folders as $path ) {
				$excluded[] = $path;
			}
		}

		$blacklisted = array(
			'updraft'        => trailingslashit( WP_CONTENT_DIR ) . trailingslashit( 'updraft' ),
			'wponlinebckp'   => trailingslashit( WP_CONTENT_DIR ) . trailingslashit( 'backups' ),
			'duplicator'     => trailingslashit( ABSPATH ) . trailingslashit( 'wp-snapshots' ),
			'backupbuddy'    => trailingslashit( $hmn_upload_dir['path'] ) . trailingslashit( 'backupbuddy_backups' ),
			'pb_backupbuddy' => trailingslashit( $hmn_upload_dir['path'] ) . trailingslashit( 'pb_backupbuddy' ),
			'wpdbmanager'    => trailingslashit( WP_CONTENT_DIR ) . trailingslashit( 'backup-db' ),
			'supercache'     => trailingslashit( WP_CONTENT_DIR ) . trailingslashit( 'cache' ),
		);

		foreach ( $blacklisted as $key => $path ) {
			if ( is_dir( $path ) ) {
				$excluded[] = $path;
			}
		}

		// version control dirs
		// @todo stop excluding these once they are skipped entirely
		$excluded[] = '.svn/';
		$excluded[] = '.git/';

		return apply_filters( 'hmbkp_default_excludes', $excluded );
	}


	/**
	 * Returns an array of other backup folders in the specified directory
	 *
	 * @param $needle
	 * @param $haystack
	 *
	 * @return array
	 */
	protected function find_backup_folders( $needle, $haystack ) {

		$found_folders = array();

		// @todo can be simplified
		$folders_to_search = glob( $haystack . '/*', GLOB_ONLYDIR | GLOB_NOSORT );

		if ( ! empty( $folders_to_search ) ) {

			foreach ( $folders_to_search as $folder ) {

				$pos = strpos( $folder, $needle );

				if ( false !== $pos ) {
					$found_folders[] = trailingslashit( $folder );
				}

			}

		}

		return $found_folders;

	}

}
