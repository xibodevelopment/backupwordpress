<?php

namespace HM\BackUpWordPress;

class Site_Backup {

	private $excludes;
	private $backup_filename;
	private $database_dump_filename;
	private $status;
	private $type = 'complete';

	public function __construct( $backup_filename, $database_dump_filename = null ) {
		$this->backup_filename = $backup_filename;
		$this->database_dump_filename = $database_dump_filename;
	}

	public function set_type( $type ) {
		$this->type = $type;
	}

	public function set_status( Backup_Status $status ) {
		$this->status = $status;
	}

	public function set_excludes( Excludes $excludes ) {
		$this->excludes = $excludes;
	}

	public function run() {

		$this->status->start();

		if ( $this->type !== 'file' ) {
			$this->backup_database();
		}

		if ( $this->type !== 'database' ) {
			$this->backup_files();
		}

		$this->status->finish();

	}

	public function backup_database() {

		do_action( 'hmbkp_database_dump_started' );

		$database_backup_engines = new Backup_Director( apply_filters( 'hmbkp_database_backup_engines', array(
			new __NAMESPACE__\Mysqldump_Database_Backup_Engine,
			new __NAMESPACE__\IMysqldump_Database_Backup_Engine
		) );

		$excludes = new Excludes( array( '*.zip', 'index.html', '.htaccess', 'schedule-*' ) );

		// Set the file backup engine settings
		foreach( $database_backup_engines as &$backup_engine ) {
			$backup_engine->set_backup_filename( $this->database_dump_filename );
		}

		// Dump the database
		$this->perform_backup( $database_backup_engines );

		// Fire up the file backup engines
		$file_backup_engines = apply_filters( 'hmbkp_file_backup_engines', array(
			new __NAMESPACE__\Zip_File_Backup_Engine,
			new __NAMESPACE__\Zip_Archive_File_Backup_Engine
		) );

		// Set the file backup engine settings
		foreach( $file_backup_engines as &$backup_engine ) {
			$backup_engine->set_backup_filename( $this->backup_filename );
			$backup_engine->set_excludes( $this->excludes );
		}

		$file_backup_director = new Backup_Director( $file_backup_engines );

		// Zip up the database dump
		$root = Path::get_root();
		Path::get_instance()->set_root( Path::get_path() );
		$this->perform_backup( $file_backup_engines );
		Path::get_instance()->set_root( $root );

		// Delete the Database Backup now that we've zipped it up
		if ( file_exists( $this->database_backup_director->get_backup_filepath() ) ) {
			unlink( $this->database_backup_director->get_backup_filepath() );
		}

	}

	public function backup_files() {

		do_action( 'hmbkp_archive_started' );

		// Fire up the file backup engines
		$backup_engines = apply_filters( 'hmbkp_file_backup_engines', array(
			new __NAMESPACE__\Zip_File_Backup_Engine,
			new __NAMESPACE__\Zip_Archive_File_Backup_Engine
		) );

		// Set the file backup engine settings
		foreach( $backup_engines as &$backup_engine ) {
			$backup_engine->set_backup_filename( $this->backup_filename );
			$backup_engine->set_excludes( $this->excludes );
			$backup_engine->set_status( $this->status );
		}

		$this->perform_backup( $backup_engines );

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

	}

	public function get_database_backup_filepath() {
		return $this->database_backup_director->get_backup_filepath();
	}

	public function get_backup_filepath() {
		return $this->file_backup_director->get_backup_filepath();;
	}

}
