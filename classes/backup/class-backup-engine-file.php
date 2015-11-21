<?php

namespace HM\BackUpWordPress;
use Symfony\Component\Finder\Finder as Finder;

abstract class File_Backup_Engine extends Backup_Engine {

	protected $excludes = array();

	public function __construct() {

		$this->set_backup_filename( implode( '-', array(
			str_ireplace( array( 'http://', 'https://', 'www' ), '', home_url() ),
			'backup',
			current_time( 'Y-m-d-H-i-s' )
		) ) . '.zip' );

	}

	public function set_excludes( $excludes ) {
		$this->excludes = $excludes;
	}

	public function get_files() {

		$finder = new Finder();

		$finder->followLinks( true );
		$finder->ignoreDotFiles( false );
		$finder->ignoreVCS( true );
		$finder->ignoreUnreadableDirs( true );

		// Skip unreadable files too
		$finder->filter(
			function ( \SplFileInfo $file ) {
				if ( ! $file->isReadable() ) {
					return false;
				}
			}
		);

		$excludes = new Excludes;
		$excludes->set_excludes( $this->excludes );
		$exclude_rules = $excludes->get_excludes_for_regex();

		// Skips folders/files that match default exclude patterns
		foreach ( $exclude_rules as $exclude ) {
			$finder->notPath( $exclude );
		}

		return $finder->in( Path::get_root() );

	}

	public function verify_backup() {

		// If there are errors delete the backup file.
		if ( $this->get_errors( __CLASS__ ) && file_exists( $this->get_backup_filepath() ) ) {
			unlink( $this->get_backup_filepath() );
		}

		// If the archive file still exists assume it's good
		if ( ! file_exists( $this->get_backup_filepath() ) ) {
			return false;
		}

		return true;

	}

}
