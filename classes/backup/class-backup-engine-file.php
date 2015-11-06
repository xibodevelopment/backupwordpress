<?php

namespace HM\BackUpWordPress;
use Symfony\Component\Finder\Finder as Finder;

class File_Backup_Engine extends Backup_Engine {

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

	public static function get_home_path( $home_url = null, $site_url = null, $home_path = ABSPATH ) {

		if ( defined( 'HMBKP_ROOT' ) && HMBKP_ROOT ) {
			return wp_normalize_path( HMBKP_ROOT );
		}

		if ( is_null( $home_url ) ) {
			$home_url = home_url();
		}

		if ( is_null( $site_url ) ) {
			$site_url = site_url();
		}

		// If site_url contains home_url and they differ then assume WordPress is installed in a sub directory
		if ( $home_url !== $site_url && strpos( $site_url, $home_url ) === 0 ) {
			$home_path = trailingslashit( substr( wp_normalize_path( ABSPATH ), 0, strrpos( wp_normalize_path( ABSPATH ), str_replace( $home_url, '', $site_url ) ) ) );
		}

		return wp_normalize_path( $home_path );

	}

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

	public function get_root() {

		if ( empty( $this->root ) ) {
			$this->set_root( self::get_home_path() );
		}

		return $this->root;

	}

	public function set_root( $path ) {

		if ( empty( $path ) || ! is_string( $path ) || ! is_dir( $path ) ) {
			return new \WP_Error( 'invalid_directory_path', sprintf( __( 'Invalid root path <code>%s</code> must be a valid directory path', 'backupwordpress' ), $path ) );
		}

		$this->root = wp_normalize_path( $path );

	}

	public function get_files() {

		$finder = new Finder();

		$finder->followLinks( true );
		$finder->ignoreDotFiles( false );
		$finder->ignoreVCS( true );
		$finder->ignoreUnreadableDirs( true );

		$excludes = $this->get_excludes();

		// Skips folders/files that match default exclude patterns
		foreach ( $excludes as $exclude ) {
			$finder->notPath( $exclude );
		}

		return $finder->in( $this->get_root() );

	}

	public function get_excludes() {

		$excludes = array_merge( $this->get_default_excludes(), $this->excludes );

		// If path() is inside root(), exclude it
		if ( strpos( $this->get_path(), $this->get_root() ) !== false ) {
			array_unshift( $excludes, trailingslashit( $this->get_path() ) );
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