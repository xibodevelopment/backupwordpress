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

		$excludes = array_merge( $this->get_default_excludes(), $this->excludes );

		// If path() is inside root(), exclude it
		if ( strpos( Path::get_path(), Path::get_root() ) !== false ) {
			array_unshift( $excludes, trailingslashit( Path::get_path() ) );
		}

		// Prepare the exclude rules
		$excludes = array_map( function( $exclude ) {

			// Convert absolute paths to relative
			$exclude = str_replace( PATH::get_root(), '', wp_normalize_path( $exclude ) );

			// Finder treats strings that are wrapped in slashes as regex strings so we need to string them
			$exclude = ltrim( $exclude, '/' );
			$exclude = untrailingslashit( $exclude );
			$exclude = trim( $exclude );

			// Backwards compatibility with `*` wildcards
			if ( strpos( $exclude, '*' ) !== false ) {

				// Escape slashes
				$exclude = str_replace( '/', '\/', $exclude );
				$exclude = str_replace( '*', '[\s\S]*?', $exclude );

				// Wrap in slashes
				$exclude = '/' . $exclude . '/';
			}

			return $exclude;

		}, $excludes );

		$excludes = array_unique( $excludes );
		$excludes = array_filter( $excludes );

		return array_unique( $excludes );

	}

	/**
	 * Generate the exclude param string for the zip backup
	 *
	 * Takes the exclude rules and formats them for use with either
	 * the shell zip command or pclzip
	 *
	 * @param string $context . (default: 'zip')
	 *
	 * @return string
	 */
	public function exclude_string( $context = 'zip' ) {

		// Return a comma separated list by default
		$separator = ', ';
		$wildcard  = '';

		// The zip command
		if ( $context === 'zip' ) {
			$wildcard  = '*';
			$separator = ' -x ';

			// The PclZip fallback library
		} elseif ( $context === 'regex' ) {
			$wildcard  = '([\s\S]*?)';
			$separator = '|';
		}

		$excludes = $this->get_excludes();

		foreach ( $excludes as $key => &$rule ) {

			$file = $absolute = $fragment = false;

			// Files don't end with /
			if ( ! in_array( substr( $rule, - 1 ), array( '\\', '/' ) ) ) {
				$file = true;
			} // If rule starts with a / then treat as absolute path
			elseif ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) ) {
				$absolute = true;
			} // Otherwise treat as dir fragment
			else {
				$fragment = true;
			}

			// Strip $this->root and conform
			$rule = str_ireplace( Path::get_root(), '', untrailingslashit( wp_normalize_path( $rule ) ) );

			// Strip the preceeding slash
			if ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) ) {
				$rule = substr( $rule, 1 );
			}

			// Escape string for regex
			if ( $context === 'regex' ) {
				$rule = str_replace( '.', '\.', $rule );
			}

			// Convert any existing wildcards
			if ( $wildcard !== '*' && false !== strpos( $rule, '*' ) ) {
				$rule = str_replace( '*', $wildcard, $rule );
			}

			// Wrap directory fragments and files in wildcards for zip
			if ( 'zip' === $context && ( $fragment || $file ) ) {
				$rule = $wildcard . $rule . $wildcard;
			}

			// Add a wildcard to the end of absolute url for zips
			if ( 'zip' === $context && $absolute ) {
				$rule .= $wildcard;
			}

			// Add and end carrot to files for pclzip but only if it doesn't end in a wildcard
			if ( $file && 'regex' === $context ) {
				$rule .= '$';
			}

			// Add a start carrot to absolute urls for pclzip
			if ( $absolute && 'regex' === $context ) {
				$rule = '^' . $rule;
			}

		}

		// Escape shell args for zip command
		if ( $context === 'zip' ) {
			$excludes = array_map( 'escapeshellarg', array_unique( $excludes ) );
		}

		return implode( $separator, $excludes );

	}

	public function get_default_excludes() {
		return apply_filters( 'hmbkp_default_excludes', $this->default_excludes );
	}

}