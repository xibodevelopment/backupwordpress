<?php

namespace HM\BackUpWordPress;

class Site_Backup {

	private $excludes;
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

		if ( $this->type !== 'file' ) {
			$this->backup_database();
		}

		if ( $this->type !== 'database' ) {
			$this->backup_files();
		}

	}

	public function backup_database() {

		do_action( 'hmbkp_database_dump_started' );
		if ( $this->status ) {
			$this->status->set_status( __( 'Backing up database...', 'backupwordpress' ) );
		}

		$database_backup_engines = apply_filters( 'hmbkp_database_backup_engines', array(
			new Mysqldump_Database_Backup_Engine,
			new IMysqldump_Database_Backup_Engine
		) );

		// Set the file backup engine settings
		if (  $this->database_dump_filename ) {
			foreach( $database_backup_engines as &$backup_engine ) {
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
			new Zip_Archive_File_Backup_Engine
		) );

		// Set the file backup engine settings
		foreach( $file_backup_engines as &$backup_engine ) {
			$backup_engine->set_backup_filename( $this->backup_filename );
			$backup_engine->set_excludes( new Excludes( array( '*.zip', 'index.html', '.htaccess', 'schedule-*' ) ) );
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

		do_action( 'hmbkp_archive_started' );
		if ( $this->status ) {
			$this->status->set_status( __( 'Backing up files...', 'backupwordpress' ) );
		}

		// Fire up the file backup engines
		$backup_engines = apply_filters( 'hmbkp_file_backup_engines', array(
			new Zip_File_Backup_Engine,
			new Zip_Archive_File_Backup_Engine
		) );

		// Set the file backup engine settings
		foreach( $backup_engines as &$backup_engine ) {
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
	public function perform_backup( Array $backup_engines ) {

		foreach ( $backup_engines as $backup_engine ) {
			if ( $backup_engine->backup() ) {
				return $backup_engine;
			}
		}

		return false;

	}

	public function get_database_backup_filepath() {
		return $this->database_dump_filepath;
	}

	public function get_backup_filepath() {
		return $this->backup_filepath;
	}

}
