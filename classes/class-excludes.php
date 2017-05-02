<?php

namespace HM\BackUpWordPress;

/**
 * Manages exclude rules
 */
class Excludes {

	/**
	 * The array of exclude rules.
	 *
	 * @var array
	 */
	private $excludes;

	/**
	 * The array of default exclude rules.
	 *
	 * @var array
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
		'backupwp',
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

	public function __construct( $excludes = array() ) {
		$this->set_excludes( $excludes );
	}

	/**
	 * Set the exclude rules.
	 *
	 * Excludes rules should be a complete or partial directory or file path.
	 * Wildcards can be specified with the * character.
	 *
	 * @param string|array $excludes The list of exclude rules, accepts either
	 *                               a comma separated list or an array.
	 */
	public function set_excludes( $excludes ) {

		if ( is_string( $excludes ) ) {
			$excludes = explode( ',', $excludes );
		}

		$this->excludes = $excludes;
	}

	/**
	 * Get the excludes.
	 *
	 * Returns any user set excludes as well as the default list.
	 *
	 * @return array The array of exclude rules.
	 */
	public function get_excludes() {
		return array_merge( $this->get_default_excludes(), $this->get_user_excludes() );
	}

	/**
	 * Get the excludes prepared for use with regex.
	 *
	 * The primary difference being that any wildcard (*) rules are converted to the regex
	 * fragment `[\s\S]*?`.
	 *
	 * @return array The array of exclude rules.
	 */
	public function get_excludes_for_regex() {

		$excludes = $this->get_excludes();

		// Prepare the exclude rules.
		foreach ( $excludes as &$exclude ) {

			if ( strpos( $exclude, '*' ) !== false ) {

				// Escape slashes.
				$exclude = str_replace( '/', '\/', $exclude );

				// Convert WildCards to regex.
				$exclude = str_replace( '*', '[\s\S]*?', $exclude );

				// Wrap in slashes.
				$exclude = '/' . $exclude . '/';

			}
		}

		return $excludes;
	}

	/**
	 * Get the user defined excludes.
	 *
	 * @return array The array of excludes.
	 */
	public function get_user_excludes() {

		$excludes = $this->excludes;

		// If path() is inside root(), exclude it.
		if ( strpos( Path::get_path(), Path::get_root() ) !== false && Path::get_root() !== Path::get_path() ) {
			array_unshift( $excludes, trailingslashit( Path::get_path() ) );
		}

		return $this->normalize( $excludes );
	}

	/**
	 * Get the array of default excludes.
	 *
	 * @return array The array of excludes.
	 */
	public function get_default_excludes() {

		$excludes = array();

		// Back compat with the old constant.
		if ( defined( 'HMBKP_EXCLUDE' ) && HMBKP_EXCLUDE ) {
			$excludes = explode( ',', implode( ',', (array) HMBKP_EXCLUDE ) );
		}

		$excludes = array_merge( $this->default_excludes, $excludes );

		/**
		 * Allow the default excludes list to be modified.
		 *
		 * @param $excludes The array of exclude rules.
		 */
		$excludes = apply_filters( 'hmbkp_default_excludes', $excludes );

		return $this->normalize( $excludes );
	}

	/**
	 * Normalise the exclude rules so they are ready to work with.
	 *
	 * @param array $excludes The array of exclude rules to normalise.
	 *
	 * @return array          The array of normalised rules.
	 */
	public function normalize( $excludes ) {

		$excludes = array_map(
			function( $exclude ) {

				// Convert absolute paths to relative.
				$exclude = str_replace( PATH::get_root(), '', wp_normalize_path( $exclude ) );

				// Trim the slashes.
				$exclude = trim( $exclude );
				$exclude = ltrim( $exclude, '/' );
				$exclude = untrailingslashit( $exclude );

				return $exclude;
			},
			$excludes
		);

		// Remove duplicate or empty rules.
		$excludes = array_unique( $excludes );
		$excludes = array_filter( $excludes );

		return $excludes;
	}

	/**
	 * Check if a file is excluded,
	 * i.e. excluded directly or is in an excluded folder.
	 *
	 * @param \SplFileInfo $file File to check if it's excluded.
	 *
	 * @return bool|null         True if file is excluded, false otherwise.
	 *                           Null - if it's not a file.
	 */
	public function is_file_excluded( \SplFileInfo $file ) {

		$exclude_string    = implode( '|', $this->get_excludes_for_regex() );
		$file_path_no_root = str_ireplace(
			trailingslashit( Path::get_root() ),
			'',
			wp_normalize_path( $file->getPathname() )
		);

		if ( $exclude_string && preg_match( '(' . $exclude_string . ')', $file_path_no_root ) ) {
			return true;
		}

		return false;
	}
}
