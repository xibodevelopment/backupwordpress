<?php

require_once( HMBKP_PLUGIN_PATH . 'classes/class-hmbkp-db-restore.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-hmbkp-files-restore.php' );

class HMBKP_Restore {

	protected $backup_archive;

	protected $type;

	public function __construct( $type, $backup_archive ) {

		$this->backup_archive = $backup_archive;

		$this->type = $type;

	}

	public function do_restoration() {

		// Extract the DB dump from the zip archive
		$extracted = $this->extract_backup_archive();

		if ( is_wp_error( $extracted ) )
			return $extracted;

		switch ( $this->type ) {

			case 'database':
				$this->restore_database();
				break;

			case 'file':
				$this->restore_files();
				break;

			case 'complete':
				$this->restore_db_files();
				break;

			default:
				break;

		}

	}

	public function extract_backup_archive() {

		$parts = pathinfo( $this->backup_archive );

		WP_Filesystem();

		return unzip_file( $this->backup_archive, $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] );

	}

	public function restore_database() {

		$parts = pathinfo( $this->backup_archive );

		$dump_file_path = trailingslashit( $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] ) . $parts['filename'] . '.sql';

		$hmbkp_db_restore = new HMBKP_DB_Restore( $dump_file_path );

		$hmbkp_db_restore->restore_database();

	}

	public function restore_files() {

		$parts = pathinfo( $this->backup_archive );

		$restore_from_path = $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'];

		$hmbkp_files_restore = new HMBKP_Files_Restore( $restore_from_path );

		$hmbkp_files_restore->restore_files();

	}

	public function restore_db_files() {

		$this->restore_database();
		$this->restore_files();

	}
}