<?php

namespace HM\BackUpWordPress;

class Site_Backup {

	private $excludes = array();
	private $backup_filename = '';
	private $database_dump_filename = '';
	private $type = 'complete';

	public function backup() {

		$this->prepare_backup_directors();

		do_action( 'hmbkp_backup_started' );

		if ( $this->type !== 'file' ) {

			// Dump the database
			$this->database_backup_director->backup();
			$this->database_backup_filepath = $this->database_backup_director->get_backup_filepath();

			// Zip up the database dump
			$root = Path::get_root();
			Path::get_instance()->set_root( Path::get_path() );
			$this->file_backup_director->set_excludes( array( '*.zip', 'index.html', '.htaccess' ) );
			$this->file_backup_director->backup();
			Path::get_instance()->set_root( $root );

			// Delete the Database Backup now that we've zipped it up
			if ( file_exists( $this->database_backup_filepath ) ) {
				unlink( $this->database_backup_filepath );
			}

		}

		if ( $this->type !== 'database' ) {

			if ( $this->excludes ) {
				$this->file_backup_director->set_excludes( $this->excludes );
			}

			$this->backup_filepath = $this->file_backup_director->backup();

		}

		$this->backup_filepath = $this->file_backup_director->get_backup_filepath();

		do_action( 'hmbkp_backup_complete' );

	}

	public function prepare_backup_directors() {

		$this->file_backup_director = new Backup_Director( apply_filters( 'hmbkp_file_backup_engines', array(
			__NAMESPACE__ . '\\Zip_File_Backup_Engine',
			__NAMESPACE__ . '\\Zip_Archive_File_Backup_Engine'
		) ) );

		if ( $this->backup_filename ) {
			$this->file_backup_director->set_backup_filename( $this->backup_filename );
		}

		$this->database_backup_director = new Backup_Director( apply_filters( 'hmbkp_database_backup_engines', array(
			__NAMESPACE__ . '\\Mysqldump_Database_Backup_Engine',
			__NAMESPACE__ . '\\IMysqldump_Database_Backup_Engine'
		) ) );

		if ( $this->backup_filename ) {
			$this->database_backup_director->set_backup_filename( $this->database_dump_filename );
		}

	}

	public function set_type( $type ) {
		$this->type = $type;
	}

	public function set_excludes( $excludes ) {
		$this->excludes = $excludes;
	}

	public function set_database_dump_filename( $filename ) {
		$this->database_dump_filename = $filename;
	}

	public function set_backup_filename( $filename ) {
		$this->backup_filename = $filename;
	}

	public function set_existing_backup_filepath( $filepath ) {
		$this->existing_backup_filepath = $filepath;
	}

	public function get_database_backup_filepath() {
		return $this->database_backup_filepath;
	}

	public function get_backup_filepath() {
		return $this->backup_filepath;
	}

}
