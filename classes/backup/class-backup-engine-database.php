<?php

namespace HM\BackUpWordPress;

/**
 * The Database Backup Engine type
 *
 * All Database Backup Engine implementations should extend this class
 */
abstract class Database_Backup_Engine extends Backup_Engine {

	/**
	 * The filename for the resulting Backup
	 *
	 * @var string
	 */
	public $backup_filename = '';

	/**
	 * The database host string, typically the value of
	 * the `DB_HOST` Constant.
	 *
	 * @var string
	 */
	private $host = '';

	/**
	 * The database socket, if it's using a socket connection
	 *
	 * @var string
	 */
	private $socket = '';

	/**
	 * The database port, if a custom one is set
	 *
	 * @var integer
	 */
	private $port = 0;

	/**
	 * Individual Database Backup Engine implementations must include
	 * a backup method at a minimum.
	 *
	 * @return [type] [description]
	 */
	abstract public function backup();

	/**
	 * Setup some general database backup settings
	 *
	 * Child classes must call `parent::__construct` in their own constructor.
	 */
	public function __construct() {

		parent::__construct();

		$this->parse_db_host_constant();

		// Set a default backup filename
		$this->set_backup_filename( 'database-' . $this->get_name() . '.sql' );

	}

	/**
	 * Get the database charset setting.
	 *
	 * @return [string The database charset.
	 */
	public function get_charset() {
		global $wpdb;
		return $wpdb->charset;
	}

	/**
	 * Get the database collate setting.
	 *
	 * @return string The database collage setting.
	 */
	public function get_collate() {
		global $wpdb;
		return $wpdb->collate;
	}

	/**
	 * Get the database name.
	 *
	 * @return string The database name.
	 */
	public function get_name() {
		global $wpdb;
		return $wpdb->dbname;
	}

	/**
	 * Get the database user.
	 *
	 * @return string The database user.
	 */
	public function get_user() {
		global $wpdb;
		return $wpdb->dbuser;
	}

	/**
	 * Get the database password.
	 *
	 * @return string The database password.
	 */
	public function get_password() {
		global $wpdb;
		return $wpdb->dbpassword;
	}

	/**
	 * Get the database hostname.
	 *
	 * @return string The database hostname.
	 */
	public function get_host() {
		return $this->host;
	}

	/**
	 * Get the database port.
	 *
	 * @return int The database port.
	 */
	public function get_port() {
		return $this->port;
	}

	/**
	 * Get the database socket.
	 *
	 * @return string The database socket.
	 */
	public function get_socket() {
		return $this->socket;
	}

	/**
	 * Parse the `DB_HOST` constant.
	 *
	 * The `DB_HOST` constant potentially contains the hostname, port or socket.
	 * We need to parse it to figure out the type of mysql connection to make.
	 *
	 * @param  string $constant The Constant to parse. If the string isn't a
	 *                          defined Constant then it will be parsed directly.
	 */
	public function parse_db_host_constant( $constant = 'DB_HOST' ) {

		// If we've been passed a Constant then grab it's contents
		if ( defined( $constant ) ) {
			$constant = constant( $constant );
		}

		// If we weren't passed a Constant then just parse the string directly.
		$this->host = (string) $constant;

		// Grab the part after :, it could either be a port or a socket
		$port_or_socket = strstr( $constant, ':' );

		if ( $port_or_socket ) {

			// The host is the bit up to the :
			$this->host = substr( $constant, 0, strpos( $constant, ':' ) );

			// Strip the :
			$port_or_socket = substr( $port_or_socket, 1 );

			if ( 0 !== strpos( $port_or_socket, '/' ) ) {

				$this->port = intval( $port_or_socket );
				$maybe_socket = strstr( $port_or_socket, ':' );

				if ( ! empty( $maybe_socket ) ) {
					$this->socket = substr( $maybe_socket, 1 );
				}
			} else {
				$this->socket = $port_or_socket;
			}
		}
	}

	/**
	 * Verify that the database backup was successful.
	 *
	 * It's important this function is performant as it's called after every
	 * backup.
	 *
	 * @return bool Whether the backup completed successfully
	 */
	public function verify_backup() {

		// If there are errors delete the database dump file
		if ( $this->get_errors( get_called_class() ) && file_exists( $this->get_backup_filepath() ) ) {
			unlink( $this->get_backup_filepath() );
		}

		// If we have an empty file delete it
		if ( @filesize( $this->get_backup_filepath() ) === 0 ) {
			unlink( $this->get_backup_filepath() );
		}

		// If the database backup doesn't exist then the backup must have failed
		if ( ! file_exists( $this->get_backup_filepath() ) ) {
			return false;
		}

		return true;

	}
}
