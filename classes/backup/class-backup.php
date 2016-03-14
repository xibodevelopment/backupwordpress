<?php

namespace HM\BackUpWordPress;

class Backup {

	private $excludes;
	public $warnings = array();
	public $errors = array();
	private $backup_filename;
	private $database_dump_filename;
	private $backup_filepath = '';
	private $database_dump_filepath = '';
	private $status = null;
	private $type = 'complete';

	public function __construct( $backup_filename, $database_dump_filename = null ) {
		$this->backup_filename = $backup_filename;
		$this->database_dump_filename = $database_dump_filename;
	}

	public function set_type( $type ) {
		$this->type = $type;
	}

	public function set_backup_filename( $filename ) {
		$this->backup_filename = $filename;
	}

	public function set_status( Backup_Status $status ) {
		$this->status = $status;
	}

	public function set_excludes( Excludes $excludes ) {
		$this->excludes = $excludes;
	}

	public function run() {

		Path::get_instance()->cleanup();

		if ( 'file' !== $this->type ) {
			$this->backup_database();
		}

		if ( 'database' !== $this->type ) {
			$this->backup_files();
		}

		Path::get_instance()->cleanup();

	}

	public function backup_database() {

		if ( $this->status ) {
			$this->status->set_status( __( 'Backing up database...', 'backupwordpress' ) );
		}

		$database_backup_engines = apply_filters( 'hmbkp_database_backup_engines', array(
			new Mysqldump_Database_Backup_Engine,
			new IMysqldump_Database_Backup_Engine,
		) );

		// Set the file backup engine settings
		if (  $this->database_dump_filename ) {
			foreach ( $database_backup_engines as &$backup_engine ) {
				$backup_engine->set_backup_filename( $this->database_dump_filename );
			}
		}

		// Dump the database
		$database_dump = $this->perform_backup( $database_backup_engines );

		if ( is_a( $database_dump, __NAMESPACE__ . '\\Backup_Engine' ) ) {
			$this->database_dump_filepath = $database_dump->get_backup_filepath();
		}

		// Fire up the file backup engines
		$file_backup_engines = apply_filters( 'hmbkp_file_backup_engines', array(
			new Zip_File_Backup_Engine,
			new Zip_Archive_File_Backup_Engine,
		) );

		// Set the file backup engine settings
		foreach ( $file_backup_engines as &$backup_engine ) {
			$backup_engine->set_backup_filename( $this->backup_filename );
			$backup_engine->set_excludes( new Excludes( array( '*.zip', 'index.html', '.htaccess', '.*-running', '.files' ) ) );
		}

		// Zip up the database dump
		$root = Path::get_root();
		Path::get_instance()->set_root( Path::get_path() );
		$file_backup = $this->perform_backup( $file_backup_engines );
		Path::get_instance()->set_root( $root );

		if ( is_a( $file_backup, __NAMESPACE__ . '\\Backup_Engine' ) ) {
			$this->backup_filepath = $file_backup->get_backup_filepath();
		}

		// Delete the Database Backup now that we've zipped it up
		if ( file_exists( $this->database_dump_filepath ) ) {
			unlink( $this->database_dump_filepath );
		}

	}

	public function backup_files() {

		if ( $this->status ) {
			$this->status->set_status( __( 'Backing up files...', 'backupwordpress' ) );
		}

		// Fire up the file backup engines
		$backup_engines = apply_filters( 'hmbkp_file_backup_engines', array(
			new Zip_File_Backup_Engine,
			new Zip_Archive_File_Backup_Engine,
		) );

		// Set the file backup engine settings
		foreach ( $backup_engines as &$backup_engine ) {
			$backup_engine->set_backup_filename( $this->backup_filename );
			if ( $this->excludes ) {
				$backup_engine->set_excludes( $this->excludes );
			}
		}

		$file_backup = $this->perform_backup( $backup_engines );

		if ( is_a( $file_backup, __NAMESPACE__ . '\\Backup_Engine' ) ) {
			$this->backup_filepath = $file_backup->get_backup_filepath();
		}

	}

	/**
	 * Perform the backup by iterating through each Backup_Engine in turn until
	 * we find one which works. If a backup filename or any excludes have been
	 * set then those are passed to each Backup_Engine.
	 */
	public function perform_backup( array $backup_engines ) {

		foreach ( $backup_engines as $backup_engine ) {

			/**
			 * If the backup_engine completes the backup then we
			 * clear any errors or warnings from previously failed backup_engines
			 * and return the successful one.
			 */
			if ( $backup_engine->backup() ) {
				$this->errors = array();
				$this->warnings = $backup_engine->get_warnings();
				return $backup_engine;
			}

			// Store all the errors and warnings as they are shown if all engines fail
			$this->warnings = array_merge( $this->warnings, $backup_engine->get_warnings() );
			$this->errors   = array_merge( $this->errors, $backup_engine->get_errors() );

		}

		return false;

	}

	public function get_warnings() {
		return $this->warnings;
	}

	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Add an warning to the errors warnings.
	 *
	 * A warning is always treat as non-fatal and should only be used for recoverable
	 * issues with the backup process.
	 *
	 * @param  string $context The context for the warning.
	 * @param  string $error   The warning that was encountered.
	 */
	public function warning( $context, $warning ) {

		if ( empty( $context ) || empty( $warning ) ) {
			return;
		}

		// Ensure we don't store duplicate warnings by md5'ing the error as the key
		$this->warnings[ $context ][ md5( implode( ':', (array) $warning ) ) ] = $warning;

	}

	/**
	 * Back compat with old error method
	 *
	 * Only the backup engines themselves can fire fatal errors
	 *
	 * @deprecated 3.4 Backup->warning( $context, $warning )
	 */
	public function error( $context, $message ) {
		_deprecated_function( __FUNCTION__, '3.4', 'Backup->warning( $context, $warning )' );
		$this->warning( $context, $message );
	}

	public function get_database_backup_filepath() {
		return $this->database_dump_filepath;
	}

	public function get_backup_filepath() {
		return $this->backup_filepath;
	}

	/**
	 * Back compat with old method name
	 *
	 * @see Backup::get_backup_filepath()
	 * @deprecated 3.4 Use Backup::get_backup_filepath()
	 */
	public function get_archive_filepath() {
		_deprecated_function( __FUNCTION__, '3.4', 'get_backup_filepath()' );
		return $this->get_backup_filepath();
	}
}
