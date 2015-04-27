<?php

namespace HM\BackUpWordPress;

use Symfony\Component\Finder\Finder;

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
	private $options = array();

	/**
	 * The Backup instance
	 *
	 * @var Backup
	 */
	public $backup;

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
			throw new \Exception( 'Argument 1 for ' . __METHOD__ . ' must be a non empty string' );
		}

		// Store id for later
		$this->id = $id;

		// Load the options
		$this->options = array_filter( (array) get_option( 'hmbkp_schedule_' . $this->get_id() ) );

		// Setup The Backup class
		$this->backup = new Backup();

		// Set the archive filename to site name + schedule slug + date
		$this->backup->set_archive_filename( implode( '-', array(
				sanitize_title( str_ireplace( array(
					'http://',
					'https://',
					'www'
				), '', home_url() ) ),
				$this->get_id(),
				$this->get_type(),
				current_time( 'Y-m-d-H-i-s' )
			) ) . '.zip' );

		$this->backup->set_database_dump_filename( implode( '-', array(
				'database',
				sanitize_title( str_ireplace( array( 'http://', 'https://', 'www' ), '', home_url() ) ),
				$this->get_id()
			) ) . '.sql' );

		$this->backup->set_type( $this->get_type() );
		$this->backup->set_excludes( $this->backup->default_excludes(), true );
		$this->backup->set_excludes( $this->get_excludes() );
		$this->backup->set_action_callback( array( $this, 'do_action' ) );

		if ( defined( 'HMBKP_SCHEDULE_START_TIME' ) && strtotime( 'HMBKP_SCHEDULE_START_TIME' ) ) {
			$this->set_schedule_start_time( strtotime( 'HMBKP_SCHEDULE_START_TIME' ) );
		}

		// Setup the schedule if it isn't set
		if ( ( ! $this->is_cron_scheduled() && $this->get_reoccurrence() !== 'manually' ) ) {
			$this->schedule();
		}

	}

	/**
	 * Simple class wrapper for Path::get_path()
	 *
	 * @return string
	 */
	private function get_path() {
		return Path::get_instance()->get_path();
	}

	/**
	 * Get the id for this schedule
	 *
	 */
	public function get_id() {
		return esc_attr( $this->id );
	}

	/**
	 * Get a slugified version of name
	 *
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
	 * @return string
	 */
	public function get_name() {

		$recurrence = ( 'manually' === $this->get_reoccurrence() ) ? $this->get_reoccurrence() : substr( $this->get_reoccurrence(), 6 );

		return ucwords( $this->get_type() ) . ' ' . $recurrence;

	}

	/**
	 * Get the type of backup
	 *
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
	 * @param string $type
	 */
	public function set_type( $type ) {

		if ( isset( $this->options['type'] ) && $this->options['type'] === $type ) {
			return;
		}

		$this->backup->set_type( $type );

		$this->options['type'] = $type;

	}

	/**
	 * Get the exclude rules
	 *
	 * @return array
	 */
	public function get_excludes() {

		if ( ! empty( $this->options['excludes'] ) ) {
			$this->backup->set_excludes( $this->options['excludes'] );
		}

		return $this->backup->get_excludes();

	}

	/**
	 * Set the exclude rules
	 *
	 * @param mixed $excludes A comma separated list or array of exclude rules
	 * @param bool $append Whether to replace or append to existing rules
	 *
	 * @return string
	 */
	public function set_excludes( $excludes, $append = false ) {

		// Use the validation from Backup::set_excludes
		$this->backup->set_excludes( $excludes, $append );

		// If these are valid excludes and they are different save them
		if ( $this->backup->get_excludes() && ( empty( $this->options['excludes'] ) || $this->options['excludes'] !== $this->backup->get_excludes() ) ) {

			$this->options['excludes'] = $append && ! empty( $this->options['excludes'] ) ? array_merge( (array) $this->options['excludes'], $this->backup->get_excludes() ) : $this->backup->get_excludes();;

			$this->backup->set_excludes( $this->options['excludes'] );

		}

	}

	/**
	 * Get the maximum number of backups to keep
	 *
	 * @return int
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
	 * @return WP_Error|boolean
	 */
	public function set_max_backups( $max ) {

		if ( empty( $max ) || ! is_int( $max ) ) {
			return new \WP_Error( 'hmbkp_invalid_type_error', sprintf( __( 'Argument 1 for %s must be a valid integer', 'backupwordpress' ), __METHOD__ ) );
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
	 */
	public function set_service_options( $service, Array $options ) {
		$this->options[ $service ] = $options;
	}

	/**
	 * Calculate the size total size of the database + files
	 *
	 * Doesn't account for compression
	 *
	 * @return string
	 */
	public function get_site_size() {

		$size = 0;

		// Include database size except for file only schedule.
		if ( 'file' !== $this->get_type() ) {

			global $wpdb;

			$tables = $wpdb->get_results( 'SHOW TABLE STATUS FROM `' . DB_NAME . '`', ARRAY_A );

			foreach ( $tables as $table ) {
				$size += (float) $table['Data_length'];
			}
		}

		// Include total size of dirs/files except for database only schedule.
		if ( 'database' !== $this->get_type() ) {

			$root = new \SplFileInfo( $this->backup->get_root() );

			$size += $this->filesize( $root );

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

		$found = array();

		if ( ! empty( $this->files ) ) {
			return $this->files;
		}

		$default_excludes = $this->backup->default_excludes();

		$finder = new Finder();
		$finder->ignoreDotFiles( false );
		$finder->ignoreUnreadableDirs();
		$finder->followLinks();
		$finder->depth( '== 0' );

		foreach ( $default_excludes as $exclude ) {
			$finder->notPath( $exclude );
		}

		foreach ( $finder->in( $directory ) as $entry ) {
			$files[] = $entry;
			// Get the total filesize for each file and directory
			$filesize = $this->filesize( $entry );

			if ( $filesize ) {

				// If there is already a file with exactly the same filesize then let's keep increasing the filesize of this one until we don't have a clash
				while ( array_key_exists( $filesize, $files_with_size ) ) {
					$filesize ++;
				}

				$files_with_size[ $filesize ] = $entry;

			} elseif ( 0 === $filesize ) {

				$empty_files[] = $entry;

			} else {

				$files_with_no_size[] = $entry;

			}
		}

		// Add 0 byte files / directories to the bottom
		$files = $files_with_size + array_merge( $empty_files, $unreadable_files );

		// Add directories that are still calculating to the top
		if ( $files_with_no_size ) {

			// We have to loop as merging or concatenating the array would re-flow the keys which we don't want because the filesize is stored in the key
			foreach ( $files_with_no_size as $entry ) {
				array_unshift( $files, $entry );
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

		$files = $this->backup->get_files();

		foreach ( $files as $file ) {

			if ( $file->isReadable() ) {
				$directory_sizes[ Backup::conform_dir( $file->getRealpath() ) ] = $file->getSize();
			} else {
				$directory_sizes[ Backup::conform_dir( $file->getRealpath() ) ] = 0;
			}

		}

		// This will be the total size of the included folders MINUS default excludes.
		$directory_sizes[ $this->backup->get_root() ] = array_sum( $directory_sizes );

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
	 * @param \SplFileInfo $file The file or directory you want to know the size of
	 * @param bool $skip_excluded_files Skip excluded files when calculating a directories total size
	 *
	 * @return int                        The total of the file or directory
	 */
	public function filesize( \SplFileInfo $file, $skip_excluded_files = false ) {

		// Skip missing or unreadable files
		if ( ! file_exists( $file->getPathname() ) || ! $file->getRealpath() || ! $file->isReadable() ) {
			return 0;
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
					$task = new \HM\Backdrop\Task( array( $this, 'recursive_filesize_scanner' ) );
					$task->schedule();

				}

				return 0;

			}

			if ( $this->backup->get_root() === $file->getPathname() ) {
				return $directory_sizes[ $file->getPathname() ];
			}

			$current_pathname = trailingslashit( $file->getPathname() );
			$root             = trailingslashit( $this->backup->get_root() );

			foreach ( $directory_sizes as $path => $size ) {

				// Remove any files that aren't part of the current tree
				if ( false === strpos( $path, $current_pathname ) ) {
					unset( $directory_sizes[ $path ] );
				}

			}

			if ( $skip_excluded_files ) {

				$excludes = $this->backup->exclude_string( 'regex' );

				foreach ( $directory_sizes as $path => $size ) {

					// Skip excluded files if we have excludes
					if ( $excludes && preg_match( '(' . $excludes . ')', str_ireplace( $root, '', Backup::conform_dir( $path ) ) ) ) {
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
			return new \WP_Error( 'hmbkp_invalid_argument_error', sprintf( __( 'Argument 1 for %s must be a valid future timestamp', 'backupwordpress' ), __METHOD__ ) );
		}

		$this->options['schedule_start_time'] = $time;

		$this->schedule();

	}

	/**
	 * Get the schedule reoccurrence
	 *
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
	 * @return \WP_Error|null|boolean
	 */
	public function set_reoccurrence( $reoccurrence ) {

		$hmbkp_schedules = $this->get_cron_schedules();

		// Check it's valid
		if ( ! is_string( $reoccurrence ) || ! trim( $reoccurrence ) || ( ! in_array( $reoccurrence, array_keys( $hmbkp_schedules ) ) ) && 'manually' !== $reoccurrence ) {
			return new \WP_Error( 'hmbkp_invalid_argument_error', sprintf( __( 'Argument 1 for %s must be a valid cron reoccurrence or "manually"', 'backupwordpress' ), __METHOD__ ) );
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
	 * @return string
	 */
	public function get_schedule_running_path() {
		return $this->get_path() . '/.schedule-' . $this->get_id() . '-running';
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
		if ( $this->get_running_backup_filename() ) {
			return;
		}

		// Mark the backup as started
		$this->set_status( __( 'Starting Backup', 'backupwordpress' ) );

		// Delete old backups now in-case we fatal error during the backup process
		$this->delete_old_backups();

		if ( $this->get_backups() ) {

			// If we already have a previous backup then pass it in so it can be re-used
			list( $existing_backup ) = array_values( $this->get_backups() );

			if ( $existing_backup && file_exists( $existing_backup ) ) {
				$this->backup->set_existing_archive_filepath( $existing_backup );
			}

		}

		$this->backup->backup();

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
	 * @param string $message
	 *
	 * @return null
	 */
	public function set_status( $message ) {

		$status = json_encode( (object) array(
			'filename' => $this->backup->get_archive_filename(),
			'started'  => $this->get_schedule_running_start_time(),
			'status'   => $message,
		) );

		if ( false === @file_put_contents( $this->get_schedule_running_path(), $status ) ) {
			throw new \RuntimeException( sprintf( __( 'Error writing to file. (%s)', 'backpwordpress' ), $this->get_schedule_running_path() ) );
		}

	}

	/**
	 * Set the time that the current running backup was started
	 *
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

		switch ( $action ) :

			case 'hmbkp_backup_started':
			case 'hmbkp_mysqldump_finished':
			case 'hmbkp_archive_finished':
				break;

			case 'hmbkp_mysqldump_started' :

				$this->set_status( sprintf( __( 'Dumping Database %s', 'backupwordpress' ), '(<code>' . $this->backup->get_mysqldump_method() . '</code>)' ) );
				break;

			case 'hmbkp_mysqldump_verify_started' :

				$this->set_status( sprintf( __( 'Verifying Database Dump %s', 'backupwordpress' ), '(<code>' . $this->backup->get_mysqldump_method() . '</code>)' ) );
				break;

			case 'hmbkp_archive_started' :

				$this->set_status( sprintf( __( 'Creating zip archive %s', 'backupwordpress' ), '(<code>' . $this->backup->get_archive_method() . '</code>)' ) );
				break;

			case 'hmbkp_archive_verify_started' :

				$this->set_status( sprintf( __( 'Verifying Zip Archive %s', 'backupwordpress' ), '(<code>' . $this->backup->get_archive_method() . '</code>)' ) );
				break;

			case 'hmbkp_backup_complete' :

				$this->set_status( __( 'Finishing Backup', 'backupwordpress' ) );
				$this->update_average_schedule_run_time( $this->get_schedule_running_start_time(), time() );

				break;

			case 'hmbkp_error' :

				if ( $this->backup->get_errors() ) {

					$file = $this->get_path() . '/.backup_errors';

					if ( file_exists( $file ) ) {
						@unlink( $file );
					}

					if ( ! $handle = @fopen( $file, 'w' ) ) {
						return;
					}

					fwrite( $handle, json_encode( $this->backup->get_errors() ) );

					fclose( $handle );

				}

				break;

			case 'hmbkp_warning' :

				if ( $this->backup->get_warnings() ) {

					$file = $this->get_path() . '/.backup_warnings';

					if ( file_exists( $file ) ) {
						@unlink( $file );
					}

					if ( ! $handle = @fopen( $file, 'w' ) ) {
						return;
					}

					fwrite( $handle, json_encode( $this->backup->get_warnings() ) );

					fclose( $handle );

				}

				break;

			default:

				return new \WP_Error( 'unexpected-error', __( 'An unexpected error occured', 'backupwordpress' ) );

		endswitch;

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
	 * @return \WP_Error|boolean
	 */
	public function delete_backup( $filepath ) {

		// Check that it's a valid filepath
		if ( empty( $filepath ) || ! is_string( $filepath ) ) {
			return new \WP_Error( 'hmbkp_empty_string_error', sprintf( __( 'Argument 1 for %s must be a non empty string', 'backupwordpress' ), __METHOD__ ) );
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

