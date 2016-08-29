<?php

namespace HM\BackUpWordPress;

use Symfony\Component\Filesystem\LockHandler;

/**
 * Manages status and progress of a backup
 */
class Backup_Status {

	/**
	 * The filename for the backup file we are the tracking status of
	 *
	 * @var string
	 */
	private $filename = '';

	/**
	 * [$lock_handler description]
	 *
	 * @var LockHandler
	 */
	private $lock_handler = '';

	private $callback;

	/**
	 * @param string $id The unique id for the backup job
	 */
	public function __construct( $id ) {
		$this->id = (string) $id;
		$this->cleanup_after_crash();
	}

	/**
	 * Start the tracking a backup process.
	 *
	 * This creates a backup running file and issues a file lock. This prevents duplicate
	 * instances of this backup process running concurrently and allows us to detect if
	 * the PHP thread running the process is killed as that will clear the lock.
	 *
	 * @param  string $backup_filename The filename for the backup file that we're tracking
	 * @param  string $status_message  The initial status for the backup process
	 *
	 * @return boolean                  Whether the backup process was success marked as started
	 */
	public function start( $backup_filename, $status_message ) {
		$this->filename = $backup_filename;

		// Clear any errors from previous backup runs
		Notices::get_instance()->clear_notice_context( 'backup_failed' );

		add_action( 'shutdown', array( $this, 'catch_fatals' ), 10 );

		if ( ! defined( 'HMBKP_DISABLE_FILE_LOCKING' ) || ! HMBKP_DISABLE_FILE_LOCKING ) {
			$this->lock_handler = new LockHandler( basename( $this->get_status_filepath() ), Path::get_path() );

			if ( ! $this->lock_handler->lock() || $this->get_status() ) {
			    return false;
			}
		}

		return $this->set_status( $status_message );
	}

	/**
	 * Mark a backup process as finished.
	 *
	 * This removes the file lock and deletes the running file.
	 */
	public function finish() {

		if ( ! defined( 'HMBKP_DISABLE_FILE_LOCKING' ) || ! HMBKP_DISABLE_FILE_LOCKING ) {
			if ( isset( $this->lock_handler ) && is_a( $this->lock_handler, 'LockHandler' ) ) {
				$this->lock_handler->release();
			}
		}

		// Delete the backup running file
		if ( file_exists( $this->get_status_filepath() ) ) {
			return unlink( $this->get_status_filepath() );
		}

		return false;
	}

	/**
	 * Check if the backup has been started by checking if the running file
	 * exists.
	 *
	 * @return boolean Whether the backup process has been started
	 */
	public function is_started() {
		return (bool) file_exists( $this->get_status_filepath() );
	}

	public function is_running() {

		if ( ! $this->is_started() ) {
			return false;
		}

		if ( ! defined( 'HMBKP_DISABLE_FILE_LOCKING' ) || ! HMBKP_DISABLE_FILE_LOCKING ) {

			// If we're in the same thread then we know we must be running if the running file exists
			if ( is_a( $this->lock_handler, 'LockHandler' ) ) {
				return $this->is_started();
			}

			$lock_handler = new LockHandler( basename( $this->get_status_filepath() ), Path::get_path() );

			return ! $lock_handler->lock();
		}

		// If the backup is started and we don't support file locks then we have to assume we're still running
		return true;
	}

	/**
	 * If the running file exists but isn't locked then the thread that
	 * the backup process is running in must have been killed.
	 *
	 * You should only be running this command from a separate thread
	 *
	 * @return boolean Whether the backup process has crashed or not
	 */
	public function has_crashed() {
		return ( $this->is_started() && ! $this->is_running() );
	}

	/**
	 * Handle a process that's previouly crashed.
	 *
	 * Delete the partially created backup if it exists and then run the standard
	 * cleanup tasks and set an error message for the user.
	 *
	 * @return bool Whether the crash was handled or not
	 */
	public function cleanup_after_crash() {

		if ( ! $this->has_crashed() ) {
			return false;
		}

		if ( file_exists( trailingslashit( Path::get_path() ) . $this->get_backup_filename() ) ) {
			unlink( trailingslashit( Path::get_path() ) . $this->get_backup_filename() );
		}

		$this->finish();

		// If we already have a fatal error then let's not stomp on it.
		if ( Notices::get_instance()->get_notices( 'backup_failed' ) ) {
			$message = __( 'Your last backup failed. The backup process was killed before it could complete. Please contact your host for assistance or try excluding more files.', 'backupwordpress' );
			Notices::get_instance()->set_notices( 'backup_failed', array( $message ), true );
		}

		return true;

	}

	/**
	 * Catch fatal errors and react accordingly.
	 *
	 * Hooked into the shutdown action. If we've shutdown because of a Fatal error
	 * then we cleanup and set an error message for the user.
	 */
	public function catch_fatals() {

		$error = error_get_last();

		if ( empty( $error ) ) {
			return;
		}

		if ( ! isset( $error['type'] ) || ! defined( 'E_ERROR' ) || E_ERROR !== $error['type'] ) {
			return;
		}

		if ( file_exists( trailingslashit( Path::get_path() ) . $this->get_backup_filename() ) ) {
			unlink( trailingslashit( Path::get_path() ) . $this->get_backup_filename() );
		}

		$this->finish();

		$message = sprintf( __( 'Your last backup failed. The backup process encountered an error before it could complete. The error was %s. Please contact your host for assistance or try excluding more files.', 'backupwordpress' ), '<code>' . esc_html( $error['message'] ) . '</code>' );
		Notices::get_instance()->set_notices( 'backup_failed', array( $message ), true );

	}

	/**
	 * Get the filepath for the backup file we're tracking
	 *
	 * @return string The path to the backup file
	 */
	public function get_backup_filename() {

		if ( $this->is_started() ) {
			$status = json_decode( file_get_contents( $this->get_status_filepath() ) );

			if ( ! empty( $status->filename ) ) {
				$this->filename = $status->filename;
			}
		}

		return $this->filename;
	}

	/**
	 * Get the status of the running backup.
	 *
	 * @return string
	 */
	public function get_status() {

		if ( ! file_exists( $this->get_status_filepath() ) ) {
			return false;
		}

		$status = json_decode( file_get_contents( $this->get_status_filepath() ) );

		if ( ! empty( $status->status ) ) {
			return $status->status;
		}

		return false;

	}

	/**
	 * Set the status of the running backup
	 *
	 * @param string $message
	 *
	 * @return null
	 */
	public function set_status( $message ) {

		if ( is_callable( $this->callback ) ) {
			call_user_func( $this->callback, $message );
		}

		// If start hasn't been called yet then we wont' have a backup filename
		if ( ! $this->filename ) {
			return false;
		}

		$status = json_encode( (object) array(
			'filename' => $this->filename,
			'started'  => $this->get_start_time(),
			'status'   => $message,
		) );

		return (bool) file_put_contents( $this->get_status_filepath(), $status );

	}

	/**
	 * Get the time that the current running backup was started
	 *
	 * @return int $timestamp
	 */
	public function get_start_time() {

		if ( ! file_exists( $this->get_status_filepath() ) ) {
			return 0;
		}

		$status = json_decode( file_get_contents( $this->get_status_filepath() ) );

		if ( ! empty( $status->started ) && (int) (string) $status->started === $status->started ) {
			return $status->started;
		}

		return time();

	}

	/**
	 * Get the path to the backup running file that stores the running backup status
	 *
	 * @return string
	 */
	public function get_status_filepath() {
		return Path::get_path() . '/.backup-' . $this->id . '-running';
	}

	public function set_status_callback( $callback ) {
		$this->callback = $callback;
	}
}
