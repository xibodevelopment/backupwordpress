<?php

namespace HM\BackUpWordPress;

use Symfony\Component\Process\Process as Process;

/**
 * Perform a database backup using the mysqldump cli command
 */
class Mysqldump_Database_Backup_Engine extends Database_Backup_Engine {

	/**
	 * The path to the mysqldump executable
	 *
	 * @var string|false
	 */
	private $mysqldump_executable_path = '';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Calculate the path to the mysqldump executable.
	 *
	 * The executable path can be overridden using either the `HMBKP_MYSQLDUMP_PATH`
	 * Constant or the `hmbkp_mysqldump_executable_path` filter.
	 *
	 * If neither of those are set then we fallback to checking a number of
	 * common locations.
	 *
	 * @return string|false The path to the executable or false.
	 */
	public function get_mysqldump_executable_path() {

		// Return now if it's set in a Constant
		if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) && HMBKP_MYSQLDUMP_PATH ) {
			$this->mysqldump_executable_path = HMBKP_MYSQLDUMP_PATH;
		}

		/**
		 * Allow the executable path to be set via a filter
		 *
		 * @param string The path to the mysqldump executable
		 */
		$this->mysqldump_executable_path = apply_filters( 'hmbkp_mysqldump_executable_path', '' );

		if ( ! $this->mysqldump_executable_path ) {

			// List of possible mysqldump locations
			$paths = array(
				'mysqldump',
				'/usr/local/bin/mysqldump',
				'/usr/local/mysql/bin/mysqldump',
				'/usr/mysql/bin/mysqldump',
				'/usr/bin/mysqldump',
				'/opt/local/lib/mysql6/bin/mysqldump',
				'/opt/local/lib/mysql5/bin/mysqldump',
				'/opt/local/lib/mysql4/bin/mysqldump',
				'/xampp/mysql/bin/mysqldump',
				'/Program Files/xampp/mysql/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 6.0/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.7/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.6/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.5/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.4/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.1/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 5.0/bin/mysqldump',
				'/Program Files/MySQL/MySQL Server 4.1/bin/mysqldump',
				'/opt/local/bin/mysqldump',
			);

			$this->mysqldump_executable_path = Backup_Utilities::get_executable_path( $paths );

		}

		return $this->mysqldump_executable_path;

	}

	/**
	 * Perform the database backup.
	 *
	 * @return bool Whether the backup completed successfully or not.
	 */
	public function backup() {

		if ( ! $this->get_mysqldump_executable_path() ) {
			return false;
		}

		// Grab the database connections args
		$args = $this->get_mysql_connection_args();

		// Allow lock-tables to be overridden
		if ( defined( 'HMBKP_MYSQLDUMP_SINGLE_TRANSACTION' ) && HMBKP_MYSQLDUMP_SINGLE_TRANSACTION  ) {
			$args[] = '--single-transaction';
		}

		// Make sure binary data is exported properly
		$args[] = '--hex-blob';

		// The file we're saving too
		$args[] = '-r ' . escapeshellarg( $this->get_backup_filepath() );

		// The database we're dumping
		$args[] = escapeshellarg( $this->get_name() );

		$process = new Process( $this->get_mysqldump_executable_path() . ' ' . implode( ' ', $args ) );
		$process->setTimeout( HOUR_IN_SECONDS );

		try {
			$process->run();
		} catch ( \Exception $e ) {
			$this->error( __CLASS__, $e->getMessage() );
		}

		if ( ! $process->isSuccessful() ) {
			$this->error( __CLASS__, $process->getErrorOutput() );
		}

		return $this->verify_backup();

	}

	/**
	 * Get the connection args for the mysqldump command
	 *
	 * @return string[] The array of connection args
	 */
	public function get_mysql_connection_args() {

		$args = array();

		$args[] = '-u ' . escapeshellarg( $this->get_user() );

		if ( $this->get_password() ) {
			$args[] = '-p' . escapeshellarg( $this->get_password() );
		}

		$args[] = '-h ' . escapeshellarg( $this->get_host() );

		if ( $this->get_port() ) {
			$args[] = '-P ' . escapeshellarg( $this->get_port() );
		}

		if ( $this->get_socket() ) {
			$args[] = '--protocol=socket -S ' . escapeshellarg( $this->get_socket() );
		}

		return $args;

	}
}
