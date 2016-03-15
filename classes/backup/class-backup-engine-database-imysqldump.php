<?php

namespace HM\BackUpWordPress;

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Perform a database backup using the mysqldump-php library
 *
 * @see https://github.com/ifsnop/mysqldump-php
 */
class IMysqldump_Database_Backup_Engine extends Database_Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Perform the database backupwordpress
	 *
	 * @return bool True if the backup completed successfully, else false.
	 */
	public function backup() {

		try {

			$dump = new IMysqldump\Mysqldump( $this->get_dsn(), $this->get_user(), $this->get_password(), $this->get_dump_settings() );
			$dump->start( $this->get_backup_filepath() );

		} catch ( \Exception $e ) {
			$this->error( __CLASS__, $e->getMessage() );
		}

		return $this->verify_backup();

	}

	/**
	 * Get the settings for the database bump.
	 *
	 * @return array The array of database dump settings.
	 */
	public function get_dump_settings() {

		/**
		 * Allow additional settings to be added.
		 *
		 * @param string[] $settings The array of settings.
		 * @todo can these be standardised across all database backup engines
		 */
		return apply_filters( 'hmbkp_imysqldump_command', array(
			'default-character-set' => $this->get_charset(),
			'hex-blob'              => true,
			'single-transaction'    => defined( 'HMBKP_MYSQLDUMP_SINGLE_TRANSACTION' ) && HMBKP_MYSQLDUMP_SINGLE_TRANSACTION,
		) );

	}

	/**
	 * Correctly calculates the DSN string for the various mysql
	 * connection variations including simplt hostname, non-standard ports
	 * and socket connections.
	 *
	 * @return string  The DSN connection string
	 */
	public function get_dsn() {

		$dsn = 'mysql:host=' . $this->get_host() . ';dbname=' . $this->get_name();

		if ( $this->get_host() && $this->get_port() ) {
			$dsn = 'mysql:host=' . $this->get_host() . ';port=' . $this->get_port() . ';dbname=' . $this->get_name();
		} elseif ( $this->get_socket() ) {
			$dsn = 'mysql:unix_socket=' . $this->get_socket() . ';dbname=' . $this->get_name();
		}

		return $dsn;

	}
}
