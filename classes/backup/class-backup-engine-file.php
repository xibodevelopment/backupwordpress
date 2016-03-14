<?php

namespace HM\BackUpWordPress;

use Symfony\Component\Finder\Finder as Finder;

/**
 * The File Backup Engine type
 *
 * All File Backup Engine implementations should extend this class
 */
abstract class File_Backup_Engine extends Backup_Engine {

	/**
	 * The array of excludes rules
	 *
	 * @var array
	 */
	protected $excludes;

	/**
	 * Set the default backup filename.
	 */
	public function __construct() {

		parent::__construct();

		$this->set_backup_filename( implode( '-', array(
			str_ireplace( array( 'http://', 'https://', 'www' ), '', home_url() ),
			'backup',
			current_time( 'Y-m-d-H-i-s' ),
		) ) . '.zip' );

		$this->excludes = new Excludes;

	}

	/**
	 * Set the excludes rules for the backup.
	 *
	 * @param array $excludes The exclude rules.
	 */
	public function set_excludes( Excludes $excludes ) {
		$this->excludes = $excludes;
	}

	/**
	 * Returns a Finder instance for the files that will be included in the
	 * backup.
	 *
	 * By default we ignore unreadable files and directories as well as, common
	 * version control folders / files, "Dot" files and anything matching the
	 * exclude rules.
	 *
	 * @uses Finder
	 * @return Finder The Finder iterator of all files to be included
	 */
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

		// Finder expects exclude rules to be in a regex format
		$exclude_rules = $this->excludes->get_excludes_for_regex();

		// Skips folders/files that match default exclude patterns
		foreach ( $exclude_rules as $exclude ) {
			$finder->notPath( $exclude );
		}

		return $finder->in( Path::get_root() );

	}

	/**
	 * Verify that the file backup completed successfully.
	 *
	 * This should be called from backup method of any final file backup engine
	 * implementations.
	 *
	 * @return bool Whether the backup completed successfully.
	 */
	public function verify_backup() {

		// If there are errors delete the backup file.
		if ( $this->get_errors( get_called_class() ) && file_exists( $this->get_backup_filepath() ) ) {
			unlink( $this->get_backup_filepath() );
		}

		// If the backup doesn't exist then we must have failed.
		if ( ! file_exists( $this->get_backup_filepath() ) ) {
			return false;
		}

		return true;

	}
}
