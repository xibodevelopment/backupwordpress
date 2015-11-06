<?php

namespace HM\BackUpWordPress;

class Mysqldump_Database_Backup_Engine extends Database_Backup_Engine {

	private $mysqldump_executable_path = '';

	public function __construct() {
		parent::__construct();
	}

	public function get_mysqldump_executable_path() {

		if ( ! self::is_exec_available() ) {
			return false;
		}

		// Return now if it's set in a constant
		if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) && HMBKP_MYSQLDUMP_PATH ) {
			$this->mysqldump_executable_path = HMBKP_MYSQLDUMP_PATH;
		}

		// Allow the mysqldump path to be set via a filter
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
				'/opt/local/bin/mysqldump'
			);

			$this->mysqldump_executable_path = $this->get_executable_path( $paths );

		}

		return $this->mysqldump_executable_path;

	}

	public function check_user_can_connect_to_database_via_cli() {

		if ( ! self::is_exec_available() ) {
			return false;
		}

		$output = $return_status = '';

		$args = $this->get_mysql_connection_args();

		$args[] = escapeshellarg( $this->get_name() );

		// Quit immediately
		$args[] = '--execute="quit"';

		// Pipe STDERR to STDOUT
		$args[] = ' 2>&1';

		$args = implode( ' ', $args );
		exec( 'mysql ' . $args, $output, $return_status );

		// Test: does this warning mean that we connectec correctly
		if ( $this->is_password_warning_error( $output ) ) {
			return true;
		}

		if ( ! $output && $return_status === 0 ) {
			return true;
		}

		$this->error( __CLASS__, $output );

		return false;

	}

	public function backup() {

		if ( ! $this->check_user_can_connect_to_database_via_cli() || ! $this->get_mysqldump_executable_path() ) {
			return false;
		}

		$output = $return_status = '';

		// Grab the database connections args
		$args = $this->get_mysql_connection_args();

		// We don't want to create a new DB
		$args[] = '--no-create-db';

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

		// Pipe STDERR to STDOUT
		$args[] = '2>&1';

		$args = implode( ' ', $args );
		$command = escapeshellcmd( $this->get_mysqldump_executable_path() );
		exec( $command . ' ' . $args, $output, $return_status );

		// Skip the new password warning that is output in mysql > 5.6 (@see http://bugs.mysql.com/bug.php?id=66546)
		if ( $this->is_password_warning_error( $output ) ) {
			$output = '';
			$return_status = 0;
		}

		if ( $output && $return_status !== 0 ) {
			$this->error( __CLASS__, $stderr );
		}

		return $this->verify_backup();

	}

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

	public function is_password_warning_error( $error ) {
		return isset( $error[0] ) && 'Warning: Using a password on the command line interface can be insecure.' === trim( $error[0] );
	}

}