<?php

namespace HM\BackUpWordPress;
use Symfony\Component\Finder\Finder as Finder;

abstract class File_Backup_Engine extends Backup_Engine {

	protected $root = '';
	protected $excludes = array();
	protected $files = array();
	protected $archive_filename = '';

	/**
	 *
	 * @todo Some of these are two generic
	 */
	protected $default_excludes = array(
		'.svn',
		'_svn',
		'CVS',
		'_darcs',
		'.arch-params',
		'.monotone',
		'.bzr',
		'.git',
		'.hg',
		'backwpup-*',
		'updraft',
		'wp-snapshots',
		'backupbuddy_backups',
		'pb_backupbuddy',
		'backup-db',
		'Envato-backups',
		'managewp',
		'backupwordpress-*-backups',
	);

	public function __construct() {

		// Some properties can be overridden with defines
		if ( defined( 'HMBKP_EXCLUDE' ) && HMBKP_EXCLUDE ) {
			$this->set_excludes( HMBKP_EXCLUDE, true );
		}

		$this->set_backup_filename( implode( '-', array(
			str_ireplace( array( 'http://', 'https://', 'www' ), '', home_url() ),
			'backup',
			current_time( 'Y-m-d-H-i-s' )
		) ) . '.zip' );

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

		$excludes = $this->get_excludes();

		// Skips folders/files that match default exclude patterns
		foreach ( $excludes as $exclude ) {
			$finder->notPath( $exclude );
		}

		return $finder->in( Path::get_root() );

	}

	public function get_excludes() {

		$excludes = array_merge( $this->get_default_excludes(), $this->excludes );

		// If path() is inside root(), exclude it
		if ( strpos( Path::get_path(), Path::get_root() ) !== false ) {
			array_unshift( $excludes, trailingslashit( Path::get_path() ) );
		}

		return array_unique( $excludes );

	}

	public function get_default_excludes() {
		return array_filter( array_unique( array_map( 'trim', apply_filters( 'hmbkp_default_excludes', array_merge( $this->default_excludes ) ) ) ) );
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