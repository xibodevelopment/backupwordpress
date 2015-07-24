<?php

namespace HM\BackUpWordPress {
	use Symfony\Component\Finder\Finder;

	/**
	 * Generic file and database backup class
	 *
	 * @version 2.3
	 */
	class Backup {

		/**
		 * The backup type, must be either complete, file or database
		 *
		 * @string
		 */
		private $type = '';

		/**
		 * The filename of the backup file
		 *
		 * @string
		 */
		private $archive_filename = '';

		/**
		 * The filename of the database dump
		 *
		 * @string
		 */
		private $database_dump_filename = '';

		/**
		 * The path to the zip command
		 *
		 * @string
		 */
		private $zip_command_path;

		/**
		 * The path to the mysqldump command
		 *
		 * @string
		 */
		private $mysqldump_command_path;

		/**
		 * The filename of the existing backup file
		 *
		 * @string
		 */
		private $existing_archive_filepath = '';

		/**
		 * An array of exclude rules
		 *
		 * @array
		 */
		private $excludes = array();

		/**
		 * The path that should be backed up
		 *
		 * @var string
		 */
		private $root = '';

		/**
		 * Holds the current db connection
		 *
		 * @var resource
		 */
		private $db;

		/**
		 * An array of all the files in root
		 * excluding excludes and unreadable files
		 *
		 * @var array
		 */
		private $files = array();

		/**
		 * An array of all the files in root
		 * that match the exclude rules
		 *
		 * @var array
		 */
		private $excluded_files = array();

		/**
		 * An array of all the files in root
		 * that are unreadable
		 *
		 * @var array
		 */
		private $unreadable_files = array();

		/**
		 * An array of all the files in root
		 * that will be included in the backup
		 *
		 * @var array
		 */
		protected $included_files = array();

		/**
		 * Contains an array of errors
		 *
		 * @var mixed
		 */
		private $errors = array();

		/**
		 * Contains an array of warnings
		 *
		 * @var mixed
		 */
		private $warnings = array();

		/**
		 * The archive method used
		 *
		 * @var string
		 */
		private $archive_method = '';

		/**
		 * The mysqldump method used
		 *
		 * @var string
		 */
		private $mysqldump_method = '';

		/**
		 * @var bool
		 */
		protected $mysqldump_verified = false;

		/**
		 * @var bool
		 */
		protected $archive_verified = false;

		/**
		 * Hacky way to force a fallback to PclZip
		 *
		 * @todo re-factor out of this mess
		 * @var bool
		 */
		public $skip_zip_archive = false;

		/**
		 * @var string
		 */
		protected $action_callback = '';

		/**
		 * List of patterns we want to exclude by default.
		 * @var array
		 */
		protected $default_excludes = array(
			'.git/',
			'.svn/',
			'.DS_Store',
			'.idea/',
			'backwpup-*',
			'updraft',
			'wp-snapshots',
			'backupbuddy_backups',
			'pb_backupbuddy',
			'backup-db',
			'Envato-backups',
			'managewp',
			'backupwordpress-*-backups'
		);

		/**
		 * Returns a filterable array of excluded directories and files.
		 *
		 * @return mixed|void
		 */
		public function default_excludes() {
			return apply_filters( 'hmbkp_default_excludes', $this->default_excludes );
		}

		/**
		 * Check whether safe mode is active or not
		 *
		 * @param string $ini_get_callback
		 *
		 * @return bool
		 */
		public static function is_safe_mode_active( $ini_get_callback = 'ini_get' ) {

			$safe_mode = @call_user_func( $ini_get_callback, 'safe_mode' );

			if ( $safe_mode && strtolower( $safe_mode ) != 'off' ) {
				return true;
			}

			return false;

		}

		/**
		 * Check whether shell_exec has been disabled.
		 *
		 * @return bool
		 */
		public static function is_shell_exec_available() {

			// Are we in Safe Mode
			if ( self::is_safe_mode_active() ) {
				return false;
			}

			// Is shell_exec or escapeshellcmd or escapeshellarg disabled?
			if ( self::is_function_disabled( 'suhosin.executor.func.blacklist' ) ) {
				return false;
			}

			// Functions can also be disabled via suhosin
			if ( self::is_function_disabled( 'disable_functions' ) ) {
				return false;
			}

			// Can we issue a simple echo command?
			if ( ! @shell_exec( 'echo backupwordpress' ) ) {
				return false;
			}

			return true;

		}

		protected static function is_function_disabled( $ini_setting ) {

			if ( array_intersect( array(
				'shell_exec',
				'escapeshellarg',
				'escapeshellcmd'
			), array_map( 'trim', explode( ',', @ini_get( $ini_setting ) ) ) ) ) {
				return false;
			}

		}


		/**
		 * Attempt to work out the root directory of the site, that
		 * is, the path equivelant of home_url().
		 *
		 * @return string $home_path
		 */
		public static function get_home_path() {

			if ( defined( 'HMBKP_ROOT' ) && HMBKP_ROOT ) {
				return self::conform_dir( HMBKP_ROOT );
			}

			$home_url = home_url();
			$site_url = site_url();

			$home_path = ABSPATH;

			// If site_url contains home_url and they differ then assume WordPress is installed in a sub directory
			if ( $home_url !== $site_url && strpos( $site_url, $home_url ) === 0 ) {
				$home_path = trailingslashit( substr( self::conform_dir( ABSPATH ), 0, strrpos( self::conform_dir( ABSPATH ), str_replace( $home_url, '', $site_url ) ) ) );
			}

			return self::conform_dir( $home_path );

		}


		/**
		 * Sanitize a directory path
		 *
		 * @param string $dir
		 * @param bool $recursive
		 *
		 * @return string
		 */
		public static function conform_dir( $dir = '/', $recursive = false ) {

			// Replace single forward slash (looks like double slash because we have to escape it)
			$dir = str_replace( '\\', '/', $dir );
			$dir = str_replace( '//', '/', $dir );

			// Remove the trailing slash
			if ( $dir !== '/' ) {
				$dir = untrailingslashit( $dir );
			}

			// Carry on until completely normalized
			if ( ! $recursive && self::conform_dir( $dir, true ) != $dir ) {
				return self::conform_dir( $dir );
			}

			return (string) $dir;

		}

		/**
		 * Sets up the default properties
		 */
		public function __construct() {

			// Raise the memory limit and max_execution time
			@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
			@set_time_limit( 0 );

			// Set a custom error handler so we can track errors
			set_error_handler( array( $this, 'error_handler' ) );

			// Some properties can be overridden with defines
			if ( defined( 'HMBKP_EXCLUDE' ) && HMBKP_EXCLUDE ) {
				$this->set_excludes( HMBKP_EXCLUDE, true );
			}

			if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) {
				$this->set_mysqldump_command_path( HMBKP_MYSQLDUMP_PATH );
			}

			if ( defined( 'HMBKP_ZIP_PATH' ) ) {
				$this->set_zip_command_path( HMBKP_ZIP_PATH );
			}

			if ( defined( 'HMBKP_ZIP_PATH' ) && HMBKP_ZIP_PATH === 'PclZip' && $this->skip_zip_archive = true ) {
				$this->set_zip_command_path( false );
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
		 * Get the full filepath to the archive file
		 *
		 * @return string
		 */
		public function get_archive_filepath() {
			return trailingslashit( $this->get_path() ) . $this->get_archive_filename();
		}

		/**
		 * Get the filename of the archive file
		 *
		 * @return string
		 */
		public function get_archive_filename() {

			if ( empty( $this->archive_filename ) ) {
				$this->set_archive_filename( implode( '-', array(
						sanitize_title( str_ireplace( array(
							'http://',
							'https://',
							'www'
						), '', home_url() ) ),
						'backup',
						current_time( 'Y-m-d-H-i-s' )
					) ) . '.zip' );
			}

			return $this->archive_filename;

		}

		/**
		 * Set the filename of the archive file
		 *
		 * @param string $filename
		 *
		 * @return \WP_Error|null
		 */
		public function set_archive_filename( $filename ) {

			if ( empty( $filename ) || ! is_string( $filename ) ) {
				return new \WP_Error( 'invalid_file_name', __( 'archive filename must be a non empty string', 'backupwordpress' ) );
			}

			if ( pathinfo( $filename, PATHINFO_EXTENSION ) !== 'zip' ) {
				return new \WP_Error( 'invalid_file_extension', sprintf( __( 'invalid file extension for archive filename <code>%s</code>', 'backupwordpress' ), $filename ) );
			}

			$this->archive_filename = strtolower( sanitize_file_name( remove_accents( $filename ) ) );

		}

		/**
		 * Get the full filepath to the database dump file.
		 *
		 * @return string
		 */
		public function get_database_dump_filepath() {
			return trailingslashit( $this->get_path() ) . $this->get_database_dump_filename();
		}

		/**
		 * Get the filename of the database dump file
		 *
		 * @return string
		 */
		public function get_database_dump_filename() {

			if ( empty( $this->database_dump_filename ) ) {
				$this->set_database_dump_filename( 'database_' . DB_NAME . '.sql' );
			}

			return $this->database_dump_filename;

		}

		/**
		 * Set the filename of the database dump file
		 *
		 * @param string $filename
		 *
		 * @return \WP_Error|null
		 */
		public function set_database_dump_filename( $filename ) {

			if ( empty( $filename ) || ! is_string( $filename ) ) {
				return new \WP_Error( 'invalid_file_name', __( 'database dump filename must be a non empty string', 'backupwordpress' ) );
			}

			if ( pathinfo( $filename, PATHINFO_EXTENSION ) !== 'sql' ) {
				return new \WP_Error( 'invalid_file_extension', sprintf( __( 'invalid file extension for database dump filename <code>%s</code>', 'backupwordpress' ), $filename ) );
			}

			$this->database_dump_filename = strtolower( sanitize_file_name( remove_accents( $filename ) ) );

		}

		/**
		 * Get the root directory to backup from
		 *
		 * Defaults to the root of the path equivalent of your home_url
		 *
		 * @return string
		 */
		public function get_root() {

			if ( empty( $this->root ) ) {
				$this->set_root( self::conform_dir( self::get_home_path() ) );
			}

			return $this->root;

		}

		/**
		 * Set the root directory to backup from
		 *
		 * @param string $path
		 *
		 * @return \WP_Error|null
		 */
		public function set_root( $path ) {

			if ( empty( $path ) || ! is_string( $path ) || ! is_dir( $path ) ) {
				return new \WP_Error( 'invalid_directory_path', sprintf( __( 'Invalid root path <code>%s</code> must be a valid directory path', 'backupwordpress' ), $path ) );
			}

			$this->root = self::conform_dir( $path );

		}

		/**
		 * Get the filepath for the existing archive
		 *
		 * @return string
		 */
		public function get_existing_archive_filepath() {
			return $this->existing_archive_filepath;
		}

		/**
		 * Set the filepath for the existing archive
		 *
		 * @param string $existing_archive_filepath
		 *
		 * @return null
		 */
		public function set_existing_archive_filepath( $existing_archive_filepath ) {

			if ( empty( $existing_archive_filepath ) || ! is_string( $existing_archive_filepath ) ) {
				return new \WP_Error( 'invalid_existing_archive_filepath', sprintf( __( 'Invalid existing archive filepath <code>%s</code> must be a non empty (string)', 'backupwordpress' ), $existing_archive_filepath ) );
			}

			$this->existing_archive_filepath = self::conform_dir( $existing_archive_filepath );

		}

		/**
		 * Get the archive method that was used for the backup
		 *
		 * Will be either zip, ZipArchive or PclZip
		 *
		 */
		public function get_archive_method() {
			return $this->archive_method;
		}

		/**
		 * Get the database dump method that was used for the backup
		 *
		 * Will be either mysqldump or mysqldump_fallback
		 *
		 */
		public function get_mysqldump_method() {
			return $this->mysqldump_method;
		}

		/**
		 * Get the backup type
		 *
		 * Defaults to complete
		 *
		 */
		public function get_type() {

			if ( empty( $this->type ) ) {
				$this->set_type( 'complete' );
			}

			return $this->type;

		}

		/**
		 * Set the backup type
		 *
		 * $type must be one of complete, database or file
		 *
		 * @param string $type
		 *
		 * @return \WP_Error|null
		 */
		public function set_type( $type ) {

			if ( ! is_string( $type ) || ! in_array( $type, array( 'file', 'database', 'complete' ) ) ) {
				return new \WP_Error( 'invalid_backup_type', sprintf( __( 'Invalid backup type <code>%s</code> must be one of (string) file, database or complete', 'backupwordpress' ), $type ) );
			}

			$this->type = $type;

		}

		/**
		 * Get the path to the mysqldump bin
		 *
		 * If not explicitly set will attempt to work
		 * it out by checking common locations
		 *
		 * @return string
		 */
		public function get_mysqldump_command_path() {

			// Check shell_exec is available
			if ( ! self::is_shell_exec_available() ) {
				return '';
			}

			// Return now if it's already been set
			if ( isset( $this->mysqldump_command_path ) ) {
				return $this->mysqldump_command_path;
			}

			$this->mysqldump_command_path = '';

			// Does mysqldump work
			if ( is_null( shell_exec( 'hash mysqldump 2>&1' ) ) ) {

				// If so store it for later
				$this->set_mysqldump_command_path( 'mysqldump' );

				// And return now
				return $this->mysqldump_command_path;

			}

			// List of possible mysqldump locations
			$mysqldump_locations = array(
				'/usr/local/bin/mysqldump',
				'/usr/local/mysql/bin/mysqldump',
				'/usr/mysql/bin/mysqldump',
				'/usr/bin/mysqldump',
				'/opt/local/lib/mysql6/bin/mysqldump',
				'/opt/local/lib/mysql5/bin/mysqldump',
				'/opt/local/lib/mysql4/bin/mysqldump',
				'/xampp/mysql/bin/mysqldump',
				'/Program Files/xampp/mysql/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 6.0/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.7/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.6/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.5/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.4/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.1/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.0/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 4.1/bin/mysqldump',
				'/opt/local/bin/mysqldump'
			);

			// Find the first one which works
			foreach ( $mysqldump_locations as $location ) {
				if ( (is_null( shell_exec( 'hash ' . self::conform_dir( $location ) . ' 2>&1' ) ) ) && @is_executable( self::conform_dir( $location ) ) ) {
					$this->set_mysqldump_command_path( $location );
					break;  // Found one
				}
			}

			return $this->mysqldump_command_path;

		}

		/**
		 * Set the path to the mysqldump bin
		 *
		 * Setting the path to false will cause the database
		 * dump to use the php fallback
		 *
		 * @param mixed $path
		 */
		public function set_mysqldump_command_path( $path ) {
			$this->mysqldump_command_path = $path;
		}

		/**
		 * Get the path to the zip bin
		 *
		 * If not explicitly set will attempt to work
		 * it out by checking common locations
		 *
		 * @return string
		 */
		public function get_zip_command_path() {

			// Check shell_exec is available
			if ( ! self::is_shell_exec_available() ) {
				return '';
			}

			// Return now if it's already been set
			if ( isset( $this->zip_command_path ) ) {
				return $this->zip_command_path;
			}

			$this->zip_command_path = '';

			// Does zip work
			if ( is_null( shell_exec( 'hash zip 2>&1' ) ) ) {

				// If so store it for later
				$this->set_zip_command_path( 'zip' );

				// And return now
				return $this->zip_command_path;

			}

			// List of possible zip locations
			$zip_locations = array(
				'/usr/bin/zip',
				'/opt/local/bin/zip'
			);

			// Find the first one which works
			foreach ( $zip_locations as $location ) {
				if ( @is_executable( self::conform_dir( $location ) ) ) {
					$this->set_zip_command_path( $location );
					break;  // Found one
				}
			}

			return $this->zip_command_path;

		}

		/**
		 * Set the path to the zip bin
		 *
		 * Setting the path to false will cause the database
		 * dump to use the php fallback
		 *
		 * @param mixed $path
		 */
		public function set_zip_command_path( $path ) {
			$this->zip_command_path = $path;
		}

		/**
		 * Fire actions for the various backup stages
		 *
		 * Callers can register callbacks to be called using `set_action_callback`
		 * Both the action and the instance on Backup are then passed to the callback function
		 *
		 * @see set_action_callback
		 *
		 * @param string $action The event to fire
		 */
		protected function do_action( $action ) {

			// If we have any callbacks then let's fire them
			if ( ! empty( $this->action_callback ) ) {

				// Order them by priority, lowest priority first
				ksort( $this->action_callback );

				foreach ( $this->action_callback as $priority ) {
					foreach ( $priority as $callback ) {
						call_user_func( $callback, $action, $this );
					}
				}

			}

			// Also fire a global WordPress action
			do_action( $action, $this );

		}

		/**
		 * Allow the caller to set a callback function that will be invoked whenever
		 * an action fires
		 *
		 * @see do_action
		 * @see /do_action
		 *
		 * @param callable $callback The function or method to be called
		 * @param int $priority The priority of the callback
		 */
		public function set_action_callback( $callback, $priority = 10 ) {
			$this->action_callback[ $priority ][] = $callback;
		}

		/**
		 * Kick off a backup
		 *
		 * @todo should be renamed so it's not same as class
		 * @return null
		 */
		public function backup() {

			$this->do_action( 'hmbkp_backup_started' );

			// Backup database
			if ( $this->get_type() !== 'file' ) {
				$this->dump_database();
			}

			// Zip everything up
			$this->archive();

			$this->do_action( 'hmbkp_backup_complete' );

		}

		/**
		 * Create the mysql backup
		 *
		 * Uses mysqldump if available, falls back to PHP
		 * if not.
		 *
		 */
		public function dump_database() {

			// If we cannot run mysqldump via CLI, fallback to PHP
			if ( ! ( self::is_shell_exec_available() ) || is_wp_error( $this->user_can_connect() ) ) {
				$this->mysqldump_fallback();
			} else {
				// Attempt mysqldump command
				if ( $this->get_mysqldump_command_path() ) {
					$this->mysqldump();
				}

				if ( empty( $this->mysqldump_verified ) ) {
					$this->mysqldump_fallback();
				}
			}

			$this->do_action( 'hmbkp_mysqldump_finished' );

		}

		/**
		 * Export the database to an .sql file via the command line with mysqldump
		 */
		public function mysqldump() {

			$this->mysqldump_method = 'mysqldump';

			$this->do_action( 'hmbkp_mysqldump_started' );

			// Guess port or socket connection type
			$port_or_socket = strstr( DB_HOST, ':' );

			$host = DB_HOST;

			if ( ! empty( $port_or_socket ) ) {

				$host = substr( DB_HOST, 0, strpos( DB_HOST, ':' ) );

				$port_or_socket = substr( $port_or_socket, 1 );

				if ( 0 !== strpos( $port_or_socket, '/' ) ) {

					$port = intval( $port_or_socket );

					$maybe_socket = strstr( $port_or_socket, ':' );

					if ( ! empty( $maybe_socket ) ) {

						$socket = substr( $maybe_socket, 1 );

					}

				} else {

					$socket = $port_or_socket;

				}
			}

			// Path to the mysqldump executable
			$cmd = escapeshellarg( $this->get_mysqldump_command_path() );

			// We don't want to create a new DB
			$cmd .= ' --no-create-db';

			// Allow lock-tables to be overridden
			if ( ! defined( 'HMBKP_MYSQLDUMP_SINGLE_TRANSACTION' ) || false !== HMBKP_MYSQLDUMP_SINGLE_TRANSACTION  ) {
				$cmd .= ' --single-transaction';
			}

			// Make sure binary data is exported properly
			$cmd .= ' --hex-blob';

			// Username
			$cmd .= ' -u ' . escapeshellarg( DB_USER );

			// Don't pass the password if it's blank
			if ( DB_PASSWORD ) {
				$cmd .= ' -p' . escapeshellarg( DB_PASSWORD );
			}

			// Set the host
			$cmd .= ' -h ' . escapeshellarg( $host );

			// Set the port if it was set
			if ( ! empty( $port ) && is_numeric( $port ) ) {
				$cmd .= ' -P ' . $port;
			}

			// Set the socket path
			if ( ! empty( $socket ) && ! is_numeric( $socket ) ) {
				$cmd .= ' --protocol=socket -S ' . $socket;
			}

			// The file we're saving too
			$cmd .= ' -r ' . escapeshellarg( $this->get_database_dump_filepath() );

			// The database we're dumping
			$cmd .= ' ' . escapeshellarg( DB_NAME );

			// Pipe STDERR to STDOUT
			$cmd .= ' 2>&1';

			// Store any returned data in an error
			$stderr = shell_exec( $cmd );

			// Skip the new password warning that is output in mysql > 5.6 (@see http://bugs.mysql.com/bug.php?id=66546)
			if ( 'Warning: Using a password on the command line interface can be insecure.' === trim( $stderr ) ) {
				$stderr = '';
				}

			if ( $stderr ) {
				$this->error( $this->get_mysqldump_method(), $stderr );
			}

			$this->verify_mysqldump();

		}

		/**
		 * PHP mysqldump fallback functions, exports the database to a .sql file
		 *
		 */
		public function mysqldump_fallback() {

			$this->errors_to_warnings( $this->get_mysqldump_method() );

			$this->mysqldump_method = 'mysqldump_fallback';

			$this->do_action( 'hmbkp_mysqldump_started' );

			$this->db = @mysql_pconnect( DB_HOST, DB_USER, DB_PASSWORD );

			if ( ! $this->db ) {
				$this->db = mysql_connect( DB_HOST, DB_USER, DB_PASSWORD );
			}

			if ( ! $this->db ) {
				return;
			}

			mysql_select_db( DB_NAME, $this->db );

			if ( function_exists( 'mysql_set_charset' ) ) {
				mysql_set_charset( DB_CHARSET, $this->db );
			}

			// Begin new backup of MySql
			$tables = mysql_query( 'SHOW TABLES' );

			$sql_file = "# WordPress : " . get_bloginfo( 'url' ) . " MySQL database backup\n";
			$sql_file .= "#\n";
			$sql_file .= "# Generated: " . date( 'l j. F Y H:i T' ) . "\n";
			$sql_file .= "# Hostname: " . DB_HOST . "\n";
			$sql_file .= "# Database: " . $this->sql_backquote( DB_NAME ) . "\n";
			$sql_file .= "# --------------------------------------------------------\n";

			for ( $i = 0; $i < mysql_num_rows( $tables ); $i ++ ) {

				$curr_table = mysql_tablename( $tables, $i );

				// Create the SQL statements
				$sql_file .= "# --------------------------------------------------------\n";
				$sql_file .= "# Table: " . $this->sql_backquote( $curr_table ) . "\n";
				$sql_file .= "# --------------------------------------------------------\n";

				$this->make_sql( $sql_file, $curr_table );

			}

		}

		/**
		 * Zip up all the files.
		 *
		 * Attempts to use the shell zip command, if
		 * thats not available then it falls back to
		 * PHP ZipArchive and finally PclZip.
		 *
		 */
		public function archive() {

			// Do we have the path to the zip command
			if ( ( defined( 'HMBKP_FORCE_ZIP_METHOD' ) && ( 'zip' === HMBKP_FORCE_ZIP_METHOD ) ) || $this->get_zip_command_path() ) {
				$this->zip();
			}

			// If not or if the shell zip failed then use ZipArchive
			if ( ( defined( 'HMBKP_FORCE_ZIP_METHOD' ) && ( 'ziparchive' === HMBKP_FORCE_ZIP_METHOD ) ) || ( empty( $this->archive_verified ) && class_exists( 'ZipArchive' ) && empty( $this->skip_zip_archive ) ) ) {
				$this->zip_archive();
			}

			// If ZipArchive is unavailable or one of the above failed
			if ( ( defined( 'HMBKP_FORCE_ZIP_METHOD' ) && ( 'pclzip' === HMBKP_FORCE_ZIP_METHOD ) ) || empty( $this->archive_verified ) ) {
				$this->pcl_zip();
			}

			// Delete the database dump file
			if ( file_exists( $this->get_database_dump_filepath() ) ) {
				unlink( $this->get_database_dump_filepath() );
			}

			$this->do_action( 'hmbkp_archive_finished' );

		}

		/**
		 * Zip using the native zip command
		 */
		public function zip() {

			$this->archive_method = 'zip';

			$this->do_action( 'hmbkp_archive_started' );

			// Add the database dump to the archive
			if ( 'file' !== $this->get_type() && file_exists( $this->get_database_dump_filepath() ) ) {
				$stderr = shell_exec( 'cd ' . escapeshellarg( $this->get_path() ) . ' && ' . escapeshellcmd( $this->get_zip_command_path() ) . ' -q ' . escapeshellarg( $this->get_archive_filepath() ) . ' ' . escapeshellarg( $this->get_database_dump_filename() ) . ' 2>&1' );

				if ( ! empty ( $stderr ) ) {
					$this->warning( $this->get_archive_method(), $stderr );
				}
			}

			// Zip up $this->root
			if ( 'database' !== $this->get_type() ) {

				// cd to the site root
				$command = 'cd ' . escapeshellarg( $this->get_root() );

				// Run the zip command with the recursive and quiet flags
				$command .= ' && ' . escapeshellcmd( $this->get_zip_command_path() ) . ' -rq ';

				if ( defined( 'HMBKP_ENABLE_SYNC' ) && HMBKP_ENABLE_SYNC ) {

					// If the destination zip file already exists then let's just add changed files to save time
					if ( file_exists( $this->get_archive_filepath() ) && $this->get_existing_archive_filepath() ) {
						$command .= ' -FS ';
					}

				}

				// Save the zip file to the correct path
				$command .= escapeshellarg( $this->get_archive_filepath() ) . ' ./';

				// Pass exclude rules in if we have them
				if ( $this->exclude_string( 'zip' ) ) {
					$command .= ' -x ' . $this->exclude_string( 'zip' );
				}

				// Push all output to STDERR
				$command .= ' 2>&1';

				$stderr = shell_exec( $command );

				if ( ! empty ( $stderr ) ) {
					$this->warning( $this->get_archive_method(), $stderr );
				}

			}

			$this->verify_archive();

		}

		/**
		 * Fallback for creating zip archives if zip command is
		 * unavailable.
		 */
		public function zip_archive() {

			$this->errors_to_warnings( $this->get_archive_method() );
			$this->archive_method = 'ziparchive';

			$this->do_action( 'hmbkp_archive_started' );

			$zip = new \ZipArchive();

			if ( ! class_exists( 'ZipArchive' ) || ! $zip->open( $this->get_archive_filepath(), \ZIPARCHIVE::CREATE ) ) {
				return;
			}

			$excludes = $this->exclude_string( 'regex' );

			// Add the database
			if ( $this->get_type() !== 'file' && file_exists( $this->get_database_dump_filepath() ) ) {
				$zip->addFile( $this->get_database_dump_filepath(), $this->get_database_dump_filename() );
			}

			if ( $this->get_type() !== 'database' ) {

				$files_added = 0;

				foreach ( $this->get_files() as $file ) {

					// Skip dot files, they should only exist on versions of PHP between 5.2.11 -> 5.3
					if ( method_exists( $file, 'isDot' ) && $file->isDot() ) {
						continue;
					}

					// Skip unreadable files
					if ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) {
						continue;
					}

					// Excludes
					if ( $excludes && preg_match( '(' . $excludes . ')', str_ireplace( trailingslashit( $this->get_root() ), '', self::conform_dir( $file->getPathname() ) ) ) ) {
						continue;
					}

					if ( $file->isDir() ) {
						$zip->addEmptyDir( trailingslashit( str_ireplace( trailingslashit( $this->get_root() ), '', self::conform_dir( $file->getPathname() ) ) ) );
					} elseif ( $file->isFile() ) {
						$zip->addFile( $file->getPathname(), str_ireplace( trailingslashit( $this->get_root() ), '', self::conform_dir( $file->getPathname() ) ) );
					}

					if ( ++ $files_added % 500 === 0 ) {
						if ( ! $zip->close() || ! $zip->open( $this->get_archive_filepath(), \ZIPARCHIVE::CREATE ) ) {
							return;
						}
					}

				}

			}

			if ( $zip->status ) {
				$this->warning( $this->get_archive_method(), $zip->status );
			}

			if ( $zip->statusSys ) {
				$this->warning( $this->get_archive_method(), $zip->statusSys );
			}

			$zip->close();

			$this->verify_archive();

		}

		/**
		 * Fallback for creating zip archives if zip command and ZipArchive are
		 * unavailable.
		 *
		 * Uses the PclZip library that ships with WordPress
		 */
		public function pcl_zip() {

			$this->errors_to_warnings( $this->get_archive_method() );
			$this->archive_method = 'pclzip';

			$this->do_action( 'hmbkp_archive_started' );

			global $_hmbkp_exclude_string;

			$_hmbkp_exclude_string = $this->exclude_string( 'regex' );

			$this->load_pclzip();

			$archive = new \PclZip( $this->get_archive_filepath() );

			// Add the database
			if ( $this->get_type() !== 'file' && file_exists( $this->get_database_dump_filepath() ) ) {
				if ( ! $archive->add( $this->get_database_dump_filepath(), \PCLZIP_OPT_REMOVE_PATH, $this->get_path() ) ) {
					$this->warning( $this->get_archive_method(), $archive->errorInfo( true ) );
				}
			}

			// Zip up everything
			if ( $this->get_type() !== 'database' ) {
				if ( ! $archive->add( $this->get_root(), \PCLZIP_OPT_REMOVE_PATH, $this->get_root(), \PCLZIP_CB_PRE_ADD, 'hmbkp_pclzip_callback' ) ) {
					$this->warning( $this->get_archive_method(), $archive->errorInfo( true ) );
				}
			}

			unset( $GLOBALS['_hmbkp_exclude_string'] );

			$this->verify_archive();

		}

		public function verify_mysqldump() {

			$this->do_action( 'hmbkp_mysqldump_verify_started' );

			// If we've already passed then no need to check again
			if ( ! empty( $this->mysqldump_verified ) ) {
				return true;
			}

			// If there are mysqldump errors delete the database dump file as mysqldump will still have written one
			if ( $this->get_errors( $this->get_mysqldump_method() ) && file_exists( $this->get_database_dump_filepath() ) ) {
				unlink( $this->get_database_dump_filepath() );
			}

			// If we have an empty file delete it
			if ( @filesize( $this->get_database_dump_filepath() ) === 0 ) {
				unlink( $this->get_database_dump_filepath() );
			}

			// If the file still exists then it must be good
			if ( file_exists( $this->get_database_dump_filepath() ) ) {
				return $this->mysqldump_verified = true;
			}

			return false;

		}

		/**
		 * Verify that the archive is valid and contains all the files it should contain.
		 *
		 * @return bool
		 */
		public function verify_archive() {

			$this->do_action( 'hmbkp_archive_verify_started' );

			// If we've already passed then no need to check again
			if ( ! empty( $this->archive_verified ) ) {
				return true;
			}

			// If there are errors delete the backup file.
			if ( $this->get_errors( $this->get_archive_method() ) && file_exists( $this->get_archive_filepath() ) ) {
				unlink( $this->get_archive_filepath() );
			}

			// If the archive file still exists assume it's good
			if ( file_exists( $this->get_archive_filepath() ) ) {
				return $this->archive_verified = true;
			}

			return false;

		}

		/**
		 * Return an array of all files in the filesystem.
		 *
		 * @param bool $ignore_default_exclude_rules If true then will return all files under root. Otherwise returns all files except those matching default exclude rules.
		 *
		 * @return array
		 */
		public function get_files( $ignore_default_exclude_rules = false ) {

			$found = array();

			if ( ! empty( $this->files ) ) {
				return $this->files;
			}

			$finder = new Finder();
			$finder->followLinks();
			$finder->ignoreDotFiles( false );
			$finder->ignoreUnreadableDirs();

			if ( ! $ignore_default_exclude_rules ) {
				// Skips folders/files that match default exclude patterns
				foreach ( $this->default_excludes() as $exclude ) {
					$finder->notPath( $exclude );
				}
			}

			foreach ( $finder->in( $this->get_root() ) as $entry ) {
				$this->files[] = $entry;
			}

			return $this->files;

		}

		/**
		 * Returns an array of files that will be included in the backup.
		 *
		 * @return array
		 */
		public function get_included_files() {

			if ( ! empty( $this->included_files ) ) {
				return $this->included_files;
			}

			$this->included_files = array();

			$excludes = $this->exclude_string( 'regex' );

			foreach ( $this->get_files( true ) as $file ) {

				// Skip dot files, they should only exist on versions of PHP between 5.2.11 -> 5.3
				if ( method_exists( $file, 'isDot' ) && $file->isDot() ) {
					continue;
				}

				// Skip unreadable files
				if ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) {
					continue;
				}

				// Excludes
				if ( $excludes && preg_match( '(' . $excludes . ')', str_ireplace( trailingslashit( $this->get_root() ), '', self::conform_dir( $file->getPathname() ) ) ) ) {
					continue;
				}

				$this->included_files[] = $file;

			}

			return $this->included_files;

		}

		/**
		 * Returns an array of files that match the exclude rules.
		 *
		 * @return array
		 */
		public function get_excluded_files() {

			if ( ! empty( $this->excluded_files ) ) {
				return $this->excluded_files;
			}

			$this->excluded_files = array();

			$excludes = $this->exclude_string( 'regex' );

			foreach ( $this->get_files( true ) as $file ) {

				// Skip dot files, they should only exist on versions of PHP between 5.2.11 -> 5.3
				if ( method_exists( $file, 'isDot' ) && $file->isDot() ) {
					continue;
				}

				// Skip unreadable files
				if ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) {
					continue;
				}

				// Excludes
				if ( $excludes && preg_match( '(' . $excludes . ')', str_ireplace( trailingslashit( $this->get_root() ), '', self::conform_dir( $file->getPathname() ) ) ) ) {
					$this->excluded_files[] = $file;
				}

			}

			return $this->excluded_files;

		}

		/**
		 * Returns an array of unreadable files.
		 *
		 * @return array
		 */
		public function get_unreadable_files() {

			if ( ! empty( $this->unreadable_files ) ) {
				return $this->unreadable_files;
			}

			$this->unreadable_files = array();

			foreach ( $this->get_files( true ) as $file ) {

				// Skip dot files, they should only exist on versions of PHP between 5.2.11 -> 5.3
				if ( method_exists( $file, 'isDot' ) && $file->isDot() ) {
					continue;
				}

				if ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) {
					$this->unreadable_files[] = $file;
				}

			}

			return $this->unreadable_files;

		}

		private function load_pclzip() {

			// Load PclZip
			if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) ) {
				define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( $this->get_path() ) );
			}

			require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

		}

		/**
		 * Get an array of exclude rules
		 *
		 * The backup path is automatically excluded
		 *
		 * @return array
		 */
		public function get_excludes() {

			$excludes = array();

			if ( isset( $this->excludes ) ) {
				$excludes = $this->excludes;
			}

			// If path() is inside root(), exclude it
			if ( strpos( $this->get_path(), $this->get_root() ) !== false ) {
				array_unshift( $excludes, trailingslashit( $this->get_path() ) );
			}

			return array_unique( $excludes );

		}

		/**
		 * Set the excludes, expects and array
		 *
		 * @param  Array $excludes
		 * @param Bool $append
		 */
		public function set_excludes( $excludes, $append = false ) {

			if ( is_string( $excludes ) ) {
				$excludes = explode( ',', $excludes );
			}

			if ( $append ) {
				$excludes = array_merge( $this->excludes, $excludes );
			}

			$this->excludes = array_filter( array_unique( array_map( 'trim', $excludes ) ) );

		}

		/**
		 * Generate the exclude param string for the zip backup
		 *
		 * Takes the exclude rules and formats them for use with either
		 * the shell zip command or pclzip
		 *
		 * @param string $context . (default: 'zip')
		 *
		 * @return string
		 */
		public function exclude_string( $context = 'zip' ) {

			// Return a comma separated list by default
			$separator = ', ';
			$wildcard  = '';

			// The zip command
			if ( $context === 'zip' ) {
				$wildcard  = '*';
				$separator = ' -x ';

				// The PclZip fallback library
			} elseif ( $context === 'regex' ) {
				$wildcard  = '([\s\S]*?)';
				$separator = '|';
			}

			$excludes = $this->get_excludes();

			foreach ( $excludes as $key => &$rule ) {

				$file = $absolute = $fragment = false;

				// Files don't end with /
				if ( ! in_array( substr( $rule, - 1 ), array( '\\', '/' ) ) ) {
					$file = true;
				} // If rule starts with a / then treat as absolute path
				elseif ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) ) {
					$absolute = true;
				} // Otherwise treat as dir fragment
				else {
					$fragment = true;
				}

				// Strip $this->root and conform
				$rule = str_ireplace( $this->get_root(), '', untrailingslashit( self::conform_dir( $rule ) ) );

				// Strip the preceeding slash
				if ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) ) {
					$rule = substr( $rule, 1 );
				}

				// Escape string for regex
				if ( $context === 'regex' ) {
					$rule = str_replace( '.', '\.', $rule );
				}

				// Convert any existing wildcards
				if ( $wildcard !== '*' && false !== strpos( $rule, '*' ) ) {
					$rule = str_replace( '*', $wildcard, $rule );
				}

				// Wrap directory fragments and files in wildcards for zip
				if ( 'zip' === $context && ( $fragment || $file ) ) {
					$rule = $wildcard . $rule . $wildcard;
				}

				// Add a wildcard to the end of absolute url for zips
				if ( 'zip' === $context && $absolute ) {
					$rule .= $wildcard;
				}

				// Add and end carrot to files for pclzip but only if it doesn't end in a wildcard
				if ( $file && 'regex' === $context ) {
					$rule .= '$';
				}

				// Add a start carrot to absolute urls for pclzip
				if ( $absolute && 'regex' === $context ) {
					$rule = '^' . $rule;
				}

			}

			// Escape shell args for zip command
			if ( $context === 'zip' ) {
				$excludes = array_map( 'escapeshellarg', array_unique( $excludes ) );
			}

			return implode( $separator, $excludes );

		}

		/**
		 * Add backquotes to tables and db-names in SQL queries. Taken from phpMyAdmin.
		 *
		 * @param mixed $a_name
		 *
		 * @return array|string
		 */
		private function sql_backquote( $a_name ) {

			if ( ! empty( $a_name ) && $a_name !== '*' ) {

				if ( is_array( $a_name ) ) {

					$result = array();

					reset( $a_name );

					while ( list( $key, $val ) = each( $a_name ) ) {
						$result[ $key ] = '`' . $val . '`';
					}

					return $result;

				} else {
					return '`' . $a_name . '`';
				}

			} else {
				return $a_name;
			}

		}

		/**
		 * Reads the Database table in $table and creates
		 * SQL Statements for recreating structure and data
		 * Taken partially from phpMyAdmin and partially from
		 * Alain Wolf, Zurich - Switzerland
		 * Website: http://restkultur.ch/personal/wolf/scripts/db_backup/
		 *
		 * @param string $sql_file
		 * @param string $table
		 */
		private function make_sql( $sql_file, $table ) {

			// Add SQL statement to drop existing table
			$sql_file .= "\n";
			$sql_file .= "\n";
			$sql_file .= "#\n";
			$sql_file .= "# Delete any existing table " . $this->sql_backquote( $table ) . "\n";
			$sql_file .= "#\n";
			$sql_file .= "\n";
			$sql_file .= "DROP TABLE IF EXISTS " . $this->sql_backquote( $table ) . ";\n";

			/* Table Structure */

			// Comment in SQL-file
			$sql_file .= "\n";
			$sql_file .= "\n";
			$sql_file .= "#\n";
			$sql_file .= "# Table structure of table " . $this->sql_backquote( $table ) . "\n";
			$sql_file .= "#\n";
			$sql_file .= "\n";

			// Get table structure
			$query  = 'SHOW CREATE TABLE ' . $this->sql_backquote( $table );
			$result = mysql_query( $query, $this->db );

			if ( $result ) {

				if ( mysql_num_rows( $result ) > 0 ) {
					$sql_create_arr = mysql_fetch_array( $result );
					$sql_file .= $sql_create_arr[1];
				}

				mysql_free_result( $result );
				$sql_file .= ' ;';

			}

			/* Table Contents */

			// Get table contents
			$query  = 'SELECT * FROM ' . $this->sql_backquote( $table );
			$result = mysql_query( $query, $this->db );

			$fields_cnt = 0;
			$rows_cnt   = 0;

			if ( $result ) {
				$fields_cnt = mysql_num_fields( $result );
				$rows_cnt   = mysql_num_rows( $result );
			}

			// Comment in SQL-file
			$sql_file .= "\n";
			$sql_file .= "\n";
			$sql_file .= "#\n";
			$sql_file .= "# Data contents of table " . $table . " (" . $rows_cnt . " records)\n";
			$sql_file .= "#\n";

			$field_set = $field_num = array();

			// Checks whether the field is an integer or not
			for ( $j = 0; $j < $fields_cnt; $j ++ ) {

				$field_set[ $j ] = $this->sql_backquote( mysql_field_name( $result, $j ) );
				$type            = mysql_field_type( $result, $j );

				if ( $type === 'tinyint' || $type === 'smallint' || $type === 'mediumint' || $type === 'int' || $type === 'bigint' ) {
					$field_num[ $j ] = true;
				} else {
					$field_num[ $j ] = false;
				}

			}

			// Sets the scheme
			$entries     = 'INSERT INTO ' . $this->sql_backquote( $table ) . ' VALUES (';
			$search      = array( '\x00', '\x0a', '\x0d', '\x1a' ); //\x08\\x09, not required
			$replace     = array( '\0', '\n', '\r', '\Z' );
			$current_row = 0;
			$batch_write = 0;

			$values = array();

			while ( $row = mysql_fetch_row( $result ) ) {

				$current_row ++;

				// build the statement
				for ( $j = 0; $j < $fields_cnt; $j ++ ) {

					if ( ! isset( $row[ $j ] ) ) {
						$values[] = 'NULL';

					} elseif ( $row[ $j ] === '0' || $row[ $j ] !== '' ) {

						// a number
						if ( $field_num[ $j ] ) {
							$values[] = $row[ $j ];
						} else {
							$values[] = "'" . str_replace( $search, $replace, $this->sql_addslashes( $row[ $j ] ) ) . "'";
						}

					} else {
						$values[] = "''";
					}

				}

				$sql_file .= " \n" . $entries . implode( ', ', $values ) . ") ;";

				// write the rows in batches of 100
				if ( $batch_write === 100 ) {
					$batch_write = 0;
					$this->write_sql( $sql_file );
					$sql_file = '';
				}

				$batch_write ++;

				unset( $values );

			}

			mysql_free_result( $result );

			// Create footer/closing comment in SQL-file
			$sql_file .= "\n";
			$sql_file .= "#\n";
			$sql_file .= "# End of data contents of table " . $table . "\n";
			$sql_file .= "# --------------------------------------------------------\n";
			$sql_file .= "\n";

			$this->write_sql( $sql_file );

		}

		/**
		 * Better addslashes for SQL queries.
		 * Taken from phpMyAdmin.
		 *
		 * @param string $a_string (default: '')
		 * @param bool $is_like (default: false)
		 *
		 * @return mixed
		 */
		private function sql_addslashes( $a_string = '', $is_like = false ) {

			if ( $is_like ) {
				$a_string = str_replace( '\\', '\\\\\\\\', $a_string );
			} else {
				$a_string = str_replace( '\\', '\\\\', $a_string );
			}

			$a_string = str_replace( '\'', '\\\'', $a_string );

			return $a_string;
		}

		/**
		 * Write the SQL file
		 *
		 * @param string $sql
		 *
		 * @return null|boolean
		 */
		private function write_sql( $sql ) {

			$sqlname = $this->get_database_dump_filepath();

			// Actually write the sql file
			if ( is_writable( $sqlname ) || ! file_exists( $sqlname ) ) {

				if ( ! $handle = @fopen( $sqlname, 'a' ) ) {
					return;
				}

				if ( ! fwrite( $handle, $sql ) ) {
					return;
				}

				fclose( $handle );

				return true;

			}

		}

		/**
		 * Get the errors
		 *
		 */
		public function get_errors( $context = null ) {

			if ( ! empty( $context ) ) {
				return isset( $this->errors[ $context ] ) ? $this->errors[ $context ] : array();
			}

			return $this->errors;

		}

		/**
		 * Add an error to the errors stack
		 *
		 * @param string $context
		 * @param mixed $error
		 */
		public function error( $context, $error ) {

			if ( empty( $context ) || empty( $error ) ) {
				return;
			}

			$this->do_action( 'hmbkp_error' );

			$this->errors[ $context ][ $_key = md5( implode( ':', (array) $error ) ) ] = $error;

		}

		/**
		 * Migrate errors to warnings
		 *
		 * @param null $context
		 */
		private function errors_to_warnings( $context = null ) {

			$errors = empty( $context ) ? $this->get_errors() : array( $context => $this->get_errors( $context ) );

			if ( empty( $errors ) ) {
				return;
			}

			foreach ( $errors as $error_context => $context_errors ) {
				foreach ( $context_errors as $error ) {
					$this->warning( $error_context, $error );
				}
			}

			if ( $context ) {
				unset( $this->errors[ $context ] );
			} else {
				$this->errors = array();
			}

		}

		/**
		 * Get the warnings
		 *
		 */
		public function get_warnings( $context = null ) {

			if ( ! empty( $context ) ) {
				return isset( $this->warnings[ $context ] ) ? $this->warnings[ $context ] : array();
			}

			return $this->warnings;

		}

		/**
		 * Add an warning to the warnings stack
		 *
		 * @param string $context
		 * @param mixed $warning
		 */
		private function warning( $context, $warning ) {

			if ( empty( $context ) || empty( $warning ) ) {
				return;
			}

			$this->do_action( 'hmbkp_warning' );

			$this->warnings[ $context ][ $_key = md5( implode( ':', (array) $warning ) ) ] = $warning;

		}

		/**
		 * Custom error handler for catching php errors
		 *
		 * @param $type
		 *
		 * @return bool
		 */
		public function error_handler( $type ) {

			// Skip strict & deprecated warnings
			if ( ( defined( 'E_DEPRECATED' ) && $type === E_DEPRECATED ) || ( defined( 'E_STRICT' ) && $type === E_STRICT ) || error_reporting() === 0 ) {
				return false;
			}

			$args = func_get_args();

			array_shift( $args );

			$this->warning( 'php', implode( ', ', array_splice( $args, 0, 3 ) ) );

			return false;

		}

		/**
		 * Determine if user can connect via the CLI
		 *
		 * @return \WP_Error
		 */
		public function user_can_connect() {

			// mysql --host=localhost --user=myname --password=mypass mydb

			// Guess port or socket connection type
			$port_or_socket = strstr( DB_HOST, ':' );

			$host = DB_HOST;

			if ( ! empty( $port_or_socket ) ) {

				$host = substr( DB_HOST, 0, strpos( DB_HOST, ':' ) );

				$port_or_socket = substr( $port_or_socket, 1 );

				if ( 0 !== strpos( $port_or_socket, '/' ) ) {

					$port = intval( $port_or_socket );

					$maybe_socket = strstr( $port_or_socket, ':' );

					if ( ! empty( $maybe_socket ) ) {

						$socket = substr( $maybe_socket, 1 );

					}

				} else {

					$socket = $port_or_socket;

				}
			}

			// Path to the mysqldump executable
			$cmd = 'mysql ';

			// Username
			$cmd .= ' -u ' . escapeshellarg( DB_USER );

			// Don't pass the password if it's blank
			if ( DB_PASSWORD ) {
				$cmd .= ' -p' . escapeshellarg( DB_PASSWORD );
			}

			// Set the host
			$cmd .= ' -h ' . escapeshellarg( $host );

			// Set the port if it was set
			if ( ! empty( $port ) && is_numeric( $port ) ) {
				$cmd .= ' -P ' . $port;
			}

			// Set the socket path
			if ( ! empty( $socket ) && ! is_numeric( $socket ) ) {
				$cmd .= ' --protocol=socket -S ' . $socket;
			}

			// The database we're dumping
			$cmd .= ' ' . escapeshellarg( DB_NAME );

			// Quit immediately
			$cmd .= ' --execute="quit"';

			// Pipe STDERR to STDOUT
			$cmd .= ' 2>&1';

			// Store any returned data in an error
			$stderr = shell_exec( $cmd );

			// Skip the new password warning that is output in mysql > 5.6 (@see http://bugs.mysql.com/bug.php?id=66546)
			if ( 'Warning: Using a password on the command line interface can be insecure.' === trim( $stderr ) ) {
				$stderr = '';
			}

			if ( $stderr ) {
				return new \WP_Error( 'mysql-cli-connect-error', __( 'Could not connect to mysql', 'backupwordpress' ) );
			}
		}

	}

}

// We need this function to exist in the global namespace
// TODO obviously this should be fixed at some point
namespace {

	/**
	 * Add file callback for PclZip, excludes files
	 * and sets the database dump to be stored in the root
	 * of the zip
	 *
	 * @param string $event
	 * @param array $file
	 *
	 * @return bool
	 */
	function hmbkp_pclzip_callback( $event, $file ) {

		global $_hmbkp_exclude_string;

		// Don't try to add unreadable files.
		if ( ! is_readable( $file['filename'] ) || ! file_exists( $file['filename'] ) ) {
			return false;
		} // Match everything else past the exclude list
		elseif ( $_hmbkp_exclude_string && preg_match( '(' . $_hmbkp_exclude_string . ')', $file['stored_filename'] ) ) {
			return false;
		}

		return true;

	}

}
