<?php

namespace HM\BackUpWordPress;

abstract class Database_Backup_Engine extends Backup_Engine {

	public $backup_filename = '';

	private $host = '';
	private $socket = '';
	private $port = 0;

	public function __construct() {

		$this->parse_db_host_constant();

		$this->set_backup_filename( 'database-' . $this->get_name() . '.sql' );

		parent::__construct();

	}

	abstract public function backup();

	public function get_charset() {

		global $wpdb;
		return $wpdb->charset;

	}

	public function get_collate() {

		global $wpdb;
		return $wpdb->collate;

	}


	public function get_name() {

		global $wpdb;
		return $wpdb->dbname;

	}

	public function get_user() {

		global $wpdb;
		return $wpdb->dbuser;

	}

	public function get_password() {

		global $wpdb;
		return $wpdb->dbpassword;

	}

	public function get_host() {
		return $this->host;
	}

	public function get_port() {
		return $this->port;
	}

	public function get_socket() {
		return $this->socket;
	}

	public function parse_db_host_constant( $constant = 'DB_HOST' ) {

		if ( defined( $constant ) ) {
			$constant = constant( $constant );
		}

		$this->host = (string) $constant;
		$port_or_socket = strstr( $constant, ':' );

		if ( $port_or_socket ) {

			$this->host = substr( $constant, 0, strpos( $constant, ':' ) );
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

	public function verify_backup() {

		// If there are mysqldump errors delete the database dump file as mysqldump will still have written one
		if ( $this->get_errors( __CLASS__ ) && file_exists( $this->get_backup_filepath() ) ) {
			unlink( $this->get_backup_filepath() );
		}

		// If we have an empty file delete it
		if ( @filesize( $this->get_backup_filepath() ) === 0 ) {
			unlink( $this->get_backup_filepath() );
		}

		if ( ! file_exists( $this->get_backup_filepath() ) ) {
			return false;
		}

		return true;

	}

}