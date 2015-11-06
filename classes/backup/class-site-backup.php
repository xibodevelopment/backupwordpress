<?php

namespace HM\BackUpWordPress;

class Site_Backup {

	private $type = '';

	public function __construct( Backup_Director $file_backup_director, Backup_Director $database_backup_director ) {

		$this->file_backup = $file_backup_director;
		$this->database_backup = $database_backup_director;

	}

	public function get_type() {

		if ( empty( $this->type ) ) {
			$this->set_type( 'complete' );
		}

		return $this->type;

	}

	public function set_type( $type ) {

		if ( ! is_string( $type ) || ! in_array( $type, array( 'file', 'database', 'complete' ) ) ) {
			return new \WP_Error( 'invalid_backup_type', sprintf( __( 'Invalid backup type <code>%s</code> must be one of (string) file, database or complete', 'backupwordpress' ), $type ) );
		}

		$this->type = $type;

	}

	public function backup() {

		// Backup database
		if ( $this->get_type() !== 'file' && $this->database_backup_director ) {
			$database_backup_director->backup();
		}

		// somehow pass the database into the file backup

		// Zip everything up
		$this->file_backup_director->backup();

	}

}
