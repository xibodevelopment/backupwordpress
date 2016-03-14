<?php

namespace HM\BackUpWordPress;

/**
 * Manages status and progress of a backup
 */
class Backup_Status {

	private $filename = '';

	public function __construct( $id ) {
		$this->id = $id;
	}

	public function start( $backup_filename, $status_message ) {
		$this->filename = $backup_filename;
		$this->set_status( $status_message );
	}

	public function get_backup_filename() {

		if ( $this->is_started() ) {
			$status = json_decode( file_get_contents( $this->get_status_filepath() ) );

			if ( ! empty( $status->filename ) ) {
				$this->filename = $status->filename;
			}
		}

		return $this->filename;
	}

	public function is_started() {
		return (bool) file_exists( $this->get_status_filepath() );
	}

	public function finish() {
		// Delete the backup running file
		if ( file_exists( $this->get_status_filepath() ) ) {
			unlink( $this->get_status_filepath() );
		}
	}

	/**
	 * Get the status of the running backup.
	 *
	 * @return string
	 */
	public function get_status() {

		if ( ! file_exists( $this->get_status_filepath() ) ) {
			return '';
		}

		$status = json_decode( file_get_contents( $this->get_status_filepath() ) );

		if ( ! empty( $status->status ) ) {
			return $status->status;
		}

		return '';

	}

	/**
	 * Set the status of the running backup
	 *
	 * @param string $message
	 *
	 * @return null
	 */
	public function set_status( $message ) {

		// If start hasn't been called yet then we wont' have a backup filename
		if ( ! $this->filename ) {
			return '';
		}

		$status = json_encode( (object) array(
			'filename' => $this->filename,
			'started'  => $this->get_start_time(),
			'status'   => $message,
		) );

		file_put_contents( $this->get_status_filepath(), $status );

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
}
