<?php

namespace HM\BackUpWordPress;

abstract class Backup_Engine {

	private $errors = array();
	private $warnings = array();

	public function __construct() {

		// Raise the memory limit and max_execution time
		@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
		@set_time_limit( 0 );

		// Set a custom error handler so we can track errors
		set_error_handler( array( $this, 'error_handler' ) );

	}

	abstract public function verify_backup();

	/**
	 * Get the full filepath to the database dump file.
	 *
	 * @return string
	 */
	public function get_backup_filepath() {
		return trailingslashit( Path::get_path() ) . $this->get_backup_filename();
	}

	/**
	 * Get the filename of the database dump file
	 *
	 * @return string
	 */
	public function get_backup_filename() {
		return $this->backup_filename;
	}

	/**
	 * Set the filename of the database dump file
	 *
	 * @param string $filename
	 *
	 * @return null
	 */
	public function set_backup_filename( $filename ) {
		$this->backup_filename = strtolower( sanitize_file_name( remove_accents( $filename ) ) );
	}

	public function get_errors( $context = null ) {

		if ( ! empty( $context ) ) {
			return isset( $this->errors[ $context ] ) ? $this->errors[ $context ] : array();
		}

		return $this->errors;

	}

	public function error( $context, $error ) {

		if ( empty( $context ) || empty( $error ) ) {
			return;
		}

		$this->errors[ $context ][ $_key = md5( implode( ':', (array) $error ) ) ] = $error;

	}

	private function errors_to_warnings( $context = null ) {

		$errors = empty( $context ) ? $this->get_errors() : array( $context => $this->get_errors( $context ) );

		if ( empty( $errors ) ) {
			return;
		}

		foreach ( $errors as $error_context => $context_errors ) {
			foreach ( $context_errors as $error ) {
				$this->warning( $error_context, $error );
			}
		}

		if ( $context ) {
			unset( $this->errors[ $context ] );
		} else {
			$this->errors = array();
		}

	}

	public function get_warnings( $context = null ) {

		if ( ! empty( $context ) ) {
			return isset( $this->warnings[ $context ] ) ? $this->warnings[ $context ] : array();
		}

		return $this->warnings;

	}

	private function warning( $context, $warning ) {

		if ( empty( $context ) || empty( $warning ) ) {
			return;
		}

		$this->warnings[ $context ][ $_key = md5( implode( ':', (array) $warning ) ) ] = $warning;

	}

	public function error_handler( $type ) {

		// Skip strict & deprecated warnings
		if ( ( defined( 'E_DEPRECATED' ) && $type === E_DEPRECATED ) || ( defined( 'E_STRICT' ) && $type === E_STRICT ) || error_reporting() === 0 ) {
			return false;
		}

		$args = func_get_args();

		array_shift( $args );

		$this->warning( 'php', implode( ', ', array_splice( $args, 0, 3 ) ) );

		return false;

	}

}