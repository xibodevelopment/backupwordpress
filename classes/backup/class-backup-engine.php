<?php

namespace HM\BackUpWordPress;

/**
 * The base Backup Engine
 *
 * Base Backup Engine types should extend this class and call parent::__construct in
 * there constructor.
 *
 * Defines base functionality shared across all types of backups
 */
abstract class Backup_Engine {

	/**
	 * The filename of the backup
	 *
	 * @var string
	 */
	private $backup_filename = '';

	/**
	 * An array of backup errors.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * An array of backup warnings.
	 *
	 * @var array
	 */
	private $warnings = array();

	public function __construct() {

		/**
		 * Raise the `memory_limit` and `max_execution time`
		 *
		 * Respects the WP_MAX_MEMORY_LIMIT Constant and the `admin_memory_limit`
		 * filter.
		 */
		@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
		@set_time_limit( 0 );

		// Set a custom error handler so we can track errors
		set_error_handler( array( $this, 'error_handler' ) );

	}

	/**
	 * Backup Engine Types should always implement the `verify_backup` method.
	 *
	 * @return bool Whether the backup completed successfully or not.
	 */
	abstract public function verify_backup();

	/**
	 * Get the full filepath to the backup file.
	 *
	 * @return string The backup filepath.
	 */
	public function get_backup_filepath() {
		return trailingslashit( Path::get_path() ) . $this->get_backup_filename();
	}

	/**
	 * Get the filename of the backup.
	 *
	 * @return string The backup filename.
	 */
	public function get_backup_filename() {
		return $this->backup_filename;
	}

	/**
	 * Set the filename of the backup.
	 *
	 * @param string $filename The backup filename.
	 */
	public function set_backup_filename( $filename ) {
		$this->backup_filename = strtolower( sanitize_file_name( remove_accents( $filename ) ) );
	}

	/**
	 * Get the array of errors encountered during the backup process.
	 *
	 * @param  string|null $context The context for the error, usually the Backup
	 *                              Engine that encountered the error.
	 *
	 * @return array                The array of errors.
	 */
	public function get_errors( $context = null ) {

		// Only return a specific contexts errors.
		if ( ! empty( $context ) ) {
			return isset( $this->errors[ $context ] ) ? $this->errors[ $context ] : array();
		}

		return $this->errors;

	}

	/**
	 * Add an error to the errors array.
	 *
	 * An error is always treat as fatal and should only be used for unrecoverable
	 * issues with the backup process.
	 *
	 * @param  string $context The context for the error.
	 * @param  string $error   The error that was encountered.
	 */
	public function error( $context, $error ) {

		if ( empty( $context ) || empty( $error ) ) {
			return;
		}

		// Ensure we don't store duplicate errors by md5'ing the error as the key
		$this->errors[ $context ][ md5( implode( ':', (array) $error ) ) ] = $error;

	}

	/**
	 * Get the array of warnings encountered during the backup process.
	 *
	 * @param  string|null $context The context for the warning, usually the Backup
	 *                              Engine that encountered the warning.
	 *
	 * @return array                The array of warnings.
	 */
	public function get_warnings( $context = null ) {

		// Only return a specific contexts errors.
		if ( ! empty( $context ) ) {
			return isset( $this->warnings[ $context ] ) ? $this->warnings[ $context ] : array();
		}

		return $this->warnings;

	}

	/**
	 * Add an warning to the errors warnings.
	 *
	 * A warning is always treat as non-fatal and should only be used for recoverable
	 * issues with the backup process.
	 *
	 * @param  string $context The context for the warning.
	 * @param  string $warning The warning that was encountered.
	 */
	public function warning( $context, $warning ) {

		if ( empty( $context ) || empty( $warning ) ) {
			return;
		}

		// Ensure we don't store duplicate warnings by md5'ing the error as the key
		$this->warnings[ $context ][ md5( implode( ':', (array) $warning ) ) ] = $warning;

	}

	/**
	 * Hooked into `set_error_handler` to catch any PHP errors that happen during
	 * the backup process.
	 *
	 * PHP errors are always treat as warnings rather than errors.
	 *
	 * @param  int $type The level of error raised.
	 *
	 * @return boolean   Return false to pass the error back to PHP so it can
	 *                   be handled natively.
	 */
	public function error_handler( $type ) {

		// Skip strict & deprecated warnings.
		if ( ( defined( 'E_DEPRECATED' ) && E_DEPRECATED === $type ) || ( defined( 'E_STRICT' ) && E_STRICT === $type ) || 0 === error_reporting() ) {
			return false;
		}

		/**
		 * Get the details of the error.
		 *
		 * These are:
		 *
		 * @param int    $errorno   The error level expressed as an integer.
		 * @param string $errstr    The error message.
		 * @param string $errfile   The file that the error raised in.
		 * @param string $errorline The line number the error was raised on.
		 */
		$args = func_get_args();

		// Strip the error level
		array_shift( $args );

		// Fire a warning for the PHP error passing the message, file and line number.
		$this->warning( 'php', implode( ', ', array_splice( $args, 0, 3 ) ) );

		return false;

	}
}
