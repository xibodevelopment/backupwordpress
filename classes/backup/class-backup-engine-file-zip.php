<?php

namespace HM\BackUpWordPress;

class Zip_File_Backup_Engine extends File_Backup_Engine {

	private $zip_executable_path = '';

	public function __construct() {
		parent::__construct();
	}

	private function get_zip_executable_path() {

		// Check shell_exec is available
		if ( ! self::is_exec_available() ) {
			return false;
		}

		if ( defined( 'HMBKP_ZIP_PATH' ) ) {
			return HMBKP_ZIP_PATH;
		}

		$this->zip_executable_path = apply_filters( 'hmbkp_zip_executable_path', '' );

		if ( ! $this->zip_executable_path ) {

			// List of possible zip locations
			$paths = array(
				'zip',
				'/usr/bin/zip',
				'/opt/local/bin/zip'
			);

			$this->zip_executable_path = $this->get_executable_path( $paths );

		}

		return $this->zip_executable_path;

	}

	public function backup() {

		// TODO Add the database dump to the archive
		// if ( 'file' !== $this->get_type() && file_exists( $this->get_database_dump_filepath() ) ) {
		// 	$stderr = shell_exec( 'cd ' . escapeshellarg( $this->get_path() ) . ' && ' . escapeshellcmd( $this->get_zip_command_path() ) . ' -q ' . escapeshellarg( $this->get_archive_filepath() ) . ' ' . escapeshellarg( $this->get_database_dump_filename() ) . ' 2>&1' );

		// 	if ( ! empty ( $stderr ) ) {
		// 		$this->warning( $this->get_archive_method(), $stderr );
		// 	}
		// }

		// Zip up $this->root

		// cd to the site root
		$command[] = 'cd ' . escapeshellarg( $this->get_root() );

		// Run the zip command with the recursive and quiet flags
		$command[] = '&& ' . escapeshellcmd( $this->get_zip_executable_path() ) . ' -rq';

		// TODO bring this back
		//if ( defined( 'HMBKP_ENABLE_SYNC' ) && HMBKP_ENABLE_SYNC ) {
		//
		//	// If the destination zip file already exists then let's just add changed files to save time
		//	if ( file_exists( $this->get_archive_filepath() ) && $this->get_existing_archive_filepath() ) {
		//		$command .= ' -FS ';
		//	}
		//
		//}

		// Save the zip file to the correct path
		$command[] = escapeshellarg( $this->get_backup_filepath() ) . ' ./';

		// Pass exclude rules in if we have them
		if ( $this->get_exclude_string() ) {
			$command[] = ' -x ' . $this->get_exclude_string();
		}

		// Push all output to STDERR
		$command[] = ' 2>&1';

		$command = implode( ' ', $command );

		exec( $command, $stderr, $return_status );

		if ( ! empty( $stderr ) ) {
			$this->error( __CLASS__, $stderr );
		}

		return $this->verify_backup();

	}

	// TODO maybe switch back to just excluding whole filepaths
	public function get_exclude_string( $context = 'zip' ) {

		$excludes = $this->get_excludes();

		foreach ( $excludes as $key => &$rule ) {

			$file = $absolute = $fragment = false;

			// Files don't end with /
			if ( ! in_array( substr( $rule, - 1 ), array( '\\', '/' ) ) ) {
				$file = true;
			}

			// If rule starts with a / then treat as absolute path
			elseif ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) ) {
				$absolute = true;
			}

			// Otherwise treat as dir fragment
			else {
				$fragment = true;
			}

			$rule = str_ireplace( $this->get_root(), '', untrailingslashit( wp_normalize_path( $rule ) ) );

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

		return implode( ' -x', $excludes );

	}

}