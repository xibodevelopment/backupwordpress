<?php

namespace HM\BackUpWordPress;

use Symfony\Component\Process\Process as Process;

/**
 * Perform a file backup using the zip cli command
 */
class Zip_File_Backup_Engine extends File_Backup_Engine {

	/**
	 * The path to the zip executable
	 *
	 * @var string|false
	 */
	private $zip_executable_path = '';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Calculate the path to the zip executable.
	 *
	 * The executable path can be overridden using either the `HMBKP_ZIP_PATH`
	 * Constant or the `hmbkp_zip_executable_path` filter.
	 *
	 * If neither of those are set then we fallback to checking a number of
	 * common locations.
	 *
	 * @return string|false The path to the executable or false.
	 */
	public function get_zip_executable_path() {

		if ( defined( 'HMBKP_ZIP_PATH' ) ) {
			return HMBKP_ZIP_PATH;
		}

		/**
		 * Allow the executable path to be set via a filter
		 *
		 * @param string The path to the zip executable
		 */
		$this->zip_executable_path = apply_filters( 'hmbkp_zip_executable_path', '' );

		if ( ! $this->zip_executable_path ) {

			// List of possible zip locations
			$paths = array(
				'zip',
				'/usr/bin/zip',
				'/usr/local/bin/zip',
				'/opt/local/bin/zip',
			);

			$this->zip_executable_path = Backup_Utilities::get_executable_path( $paths );

		}

		return $this->zip_executable_path;

	}

	/**
	 * Perform the file backup.
	 *
	 * @return bool Whether the backup completed successfully or not.
	 */
	public function backup() {

		if ( ! $this->get_zip_executable_path() ) {
			return false;
		}

		$command = array();

		// cd to the site root
		$command[] = 'cd ' . escapeshellarg( Path::get_root() );

		// Run the zip command with the recursive and quiet flags
		$command[] = '&& ' . escapeshellcmd( $this->get_zip_executable_path() ) . ' -rq';

		// Save the zip file to the correct path
		$command[] = escapeshellarg( $this->get_backup_filepath() ) . ' ./';

		// Pass exclude rules in if we have them
		if ( $this->get_exclude_string() ) {
			$command[] = '-x ' . $this->get_exclude_string();
		}

		$command = implode( ' ', $command );

		$process = new Process( $command );
		$process->setTimeout( HOUR_IN_SECONDS );

		try {
			$process->run();
		} catch ( \Exception $e ) {
			$this->error( __CLASS__, $e->getMessage() );
		}

		if ( ! $process->isSuccessful() ) {

			/**
			 * Exit Code 18 is returned when an unreadable file is encountered during the zip process.
			 *
			 * Given the zip process still completes correctly and the unreadable file is simple skipped
			 * we don't want to treat 18 as an actual error.
			 */
			if ( $process->getExitCode() !== 18 ) {
				$this->error( __CLASS__, $process->getErrorOutput() );
			}
		}

		return $this->verify_backup();

	}

	/**
	 * Convert the exclude rules to a format zip accepts
	 *
	 * @return string The exclude string ready to pass to `zip -x`
	 */
	public function get_exclude_string() {

		if ( empty( $this->excludes ) ) {
			return '';
		}

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

			$rule = str_ireplace( Path::get_root(), '', untrailingslashit( wp_normalize_path( $rule ) ) );

			// Strip the preceeding slash
			if ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) ) {
				$rule = substr( $rule, 1 );
			}

			// Wrap directory fragments and files in wildcards for zip
			if ( $fragment || $file ) {
				$rule = '*' . $rule . '*';
			}

			// Add a wildcard to the end of absolute url for zips
			if ( $absolute ) {
				$rule .= '*';
			}
		}

		// Escape shell args for zip command
		$excludes = array_map( 'escapeshellarg', array_unique( $excludes ) );

		return implode( ' -x ', $excludes );

	}
}
