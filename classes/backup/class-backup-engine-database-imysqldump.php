<?php

namespace HM\BackUpWordPress;
use Ifsnop\Mysqldump as IMysqldump;

class IMysqldump_Database_Backup_Engine extends Database_Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function backup() {

		try {

			$dump = new IMysqldump\Mysqldump( $this->get_dsn(), $this->get_user(), $this->get_password(), $this->get_dump_settings() );
			$dump->start( $this->get_backup_filepath() );

		} catch ( \Exception $e ) {
			$this->error( __CLASS__, sprintf( __( 'imysqldump error: %s', 'backupwordpress' ), $e->getMessage() ) );
		}

		return $this->verify_backup();

	}

	public function get_dump_settings() {

		// PDO connection string formats:
		// mysql:host=localhost;port=3307;dbname=testdb
		// mysql:unix_socket=/tmp/mysql.sock;dbname=testdb

		// Allow passing custom options to dump process.
		return apply_filters( 'hmbkp_imysqldump_command', array(
			'default-character-set' => $this->get_charset(),
			'hex-blob'              => true,
			'single-transaction'    => defined( 'HMBKP_MYSQLDUMP_SINGLE_TRANSACTION' ) && HMBKP_MYSQLDUMP_SINGLE_TRANSACTION
		) );

	}

	public function get_dsn() {

		$dsn = 'mysql:host=' . $this->get_host() . ';dbname=' . $this->get_name();

		if ( $this->get_host() && $this->get_port() ) {
			$dsn = 'mysql:host=' . $this->get_host() . ';port=' . $this->get_port() . ';dbname=' . $this->get_name();
		}

		elseif ( $this->get_socket() ) {
			$dsn = 'mysql:unix_socket=' . $this->get_socket() . ';dbname=' . $this->get_name();
		}

		return $dsn;

	}

}