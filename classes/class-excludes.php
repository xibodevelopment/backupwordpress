<?php

namespace HM\BackUpWordPress;

/**
 * Class Notices
 */
class Excludes {

	private $excludes = array();

	/**
	 *
	 * @todo Some of these are two generic
	 */
	private $default_excludes = array(
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
		'backupwordpress-*-backups'
	);

	public function __construct() {

		// Some properties can be overridden with defines
		if ( defined( 'HMBKP_EXCLUDE' ) && HMBKP_EXCLUDE ) {
			$this->set_excludes( HMBKP_EXCLUDE );
		}

	}

	public function set_excludes( $excludes ) {

		if ( is_string( $excludes ) ) {
			$excludes = explode( ',', $excludes );
		}

		$this->excludes = $excludes;

	}

	public function get_excludes() {
 		return array_merge( $this->get_default_excludes(), $this->get_user_excludes() );
	}

	public function get_excludes_for_regex() {

		$excludes = $this->get_excludes();

		// Prepare the exclude rules
		foreach ( $excludes as &$exclude ) {

			// Convert WildCards to regex
			if ( strpos( $exclude, '*' ) !== false ) {

				// Escape slashes
				$exclude = str_replace( '/', '\/', $exclude );
				$exclude = str_replace( '*', '[\s\S]*?', $exclude );

				// Wrap in slashes
				$exclude = '/' . $exclude . '/';
			}

		}

		return $excludes;

	}

	public function get_user_excludes() {

		$excludes = $this->excludes;

		// If path() is inside root(), exclude it
		if ( strpos( Path::get_path(), Path::get_root() ) !== false && Path::get_root() !== Path::get_path() ) {
			array_unshift( $excludes, trailingslashit( Path::get_path() ) );
		}

		return $this->normalize( $excludes );
	}

	public function get_default_excludes() {

		$excludes = $this->default_excludes;

		$excludes = apply_filters( 'hmbkp_default_excludes', $excludes );

		return $this->normalize( $excludes );

	}

	public function normalize( $excludes ) {

		// Convert absolute paths to relative
		$excludes = array_map( function( $exclude ) {

			$exclude = str_replace( PATH::get_root(), '', wp_normalize_path( $exclude ) );

			$exclude = ltrim( $exclude, '/' );
			$exclude = untrailingslashit( $exclude );
			$exclude = trim( $exclude );

			return $exclude;

		}, $excludes );

		$excludes = array_unique( $excludes );
		$excludes = array_filter( $excludes );

		return array_unique( $excludes );

	}

}
