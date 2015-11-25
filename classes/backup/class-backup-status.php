<?php

namespace HM\BackUpWordPress;

/**
 * Manages status and progress of a backup
 */
class Backup_Status {

  public function __construct( $backup_filename ) {
    $this->backup_filename = $backup_filename;
  }

  public function start() {
      do_action( 'hmbkp_backup_started' );
      $this->set_status( __( 'Starting Backup', 'backupwordpress' ) );
  }

  public function is_started() {
    return (bool) $this->get_status_filepath();
  }

  public function finish() {

    // Delete the backup running file
    if ( file_exists( $this->get_status_filepath() ) ) {
      unlink( $this->get_status_filepath() );
    }

    do_action( 'hmbkp_backup_complete' );

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
		return Path::get_path() . '/.backup-' . $this->get_backup_filename . '-running';
	}

}
