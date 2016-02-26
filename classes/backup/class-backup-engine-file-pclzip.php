<?php

namespace HM\BackUpWordPress;

/**
 * Perform a file backup using the PclZip PHP fallback library
 */
class Pclzip_File_Backup_Engine extends File_Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function load_pclzip() {

		if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) ) {
			define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( PATH::get_path() ) );
		}

		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

	}

	public function backup() {

		$this->load_pclzip();

		if ( ! class_exists( 'PclZip' ) ) {
			return false;
		}

		global $_hmbkp_exclude_string;
		$_hmbkp_exclude_string = $this->get_excludes_regex();

		$zip = new \PclZip( $this->get_backup_filepath() );

		$result = $zip->create( Path::get_root(), \PCLZIP_OPT_REMOVE_PATH, Path::get_root(), \PCLZIP_CB_PRE_ADD, 'HM\BackUpWordPress\hmbkp_pclzip_callback' );

		if ( 0 === $result ) {
			$this->error( __CLASS__, $zip->errorInfo( true ) );
		}

		unset( $GLOBALS['_hmbkp_exclude_string'] );

		return $this->verify_backup();

	}

	/**
	 * Generate the exclude params for pclzip
	 *
	 * Takes the exclude rules and formats them as regex
	 *
	 * @return string
	 */
	public function get_excludes_regex() {

		$wildcard  = '([\s\S]*?)';
		$separator = '|';

		$excludes = $this->excludes->get_excludes();

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

			$rule = str_replace( '.', '\.', $rule );

			// Convert any existing wildcards
			if ( '*' !== $wildcard && false !== strpos( $rule, '*' ) ) {
				$rule = str_replace( '*', $wildcard, $rule );
			}

			// Add a start carrot to absolute urls for pclzip
			if ( $absolute ) {
				$rule = '^' . $rule;
			}
		}

		return implode( $separator, $excludes );
	}
}

function hmbkp_pclzip_callback( $event, $file ) {

	global $_hmbkp_exclude_string;

	// Don't try to add unreadable files.
	if ( ! is_readable( $file['filename'] ) || ! file_exists( $file['filename'] ) ) {
		return false;
	}

	// Match everything else past the exclude list
	if ( $_hmbkp_exclude_string && preg_match( '(' . $_hmbkp_exclude_string . ')', $file['filename'] ) ) {
		return false;
	}

	return true;

}
