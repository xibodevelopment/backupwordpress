<?php

class HMBKP_DB_Restore {

	protected $dump_file_path = '';

	protected $error;

	public function __construct( $dump_file_path ) {

		$this->dump_file_path = $dump_file_path;

	}

	public function restore_database() {

		// Rename table prefix
		$this->update_table_prefix();

		// Run import
		$this->perform_import();

		// Clean up
		$this->delete_dump_file();

	}

	public function update_table_prefix() {

		$new_prefix = 'bwp' . time() . '_';

		$patterns = array(
			"/CREATE TABLE `[^_]*_(.*)` \(/",
			"/DROP TABLE IF EXISTS `[^_]*_(.*)`;/",
			"/-- Table structure for table `[^_]*_(.*)`/",
			"/-- Dumping data for table `[^_]*_(.*)`/",
			"/LOCK TABLES `[^_]*_(.*)` WRITE;/",
			"/\/\*!40000 ALTER TABLE `[^_]*_(.*)` ENABLE KEYS \*\/;/",
			"/\/\*!40000 ALTER TABLE `[^_]*_(.*)` DISABLE KEYS \*\/;/",
			"/INSERT INTO `[^_]*_(.*)` VALUES /"
		);

		$replacements = array(
			'CREATE TABLE ' . $new_prefix . '$1 (',
			'DROP TABLE IF EXISTS `' . $new_prefix . '$1`;',
			'-- Table structure for table `' . $new_prefix . '$1`',
			'-- Dumping data for table `' . $new_prefix . '$1`',
			'LOCK TABLES `' . $new_prefix . '$1` WRITE;',
			'/*!40000 ALTER TABLE `' . $new_prefix . '$1` ENABLE KEYS */;',
			'/*!40000 ALTER TABLE `' . $new_prefix . '$1` DISABLE KEYS */;',
			'INSERT INTO `' . $new_prefix . '$1` VALUES '
		);

		$contents = file_get_contents( $this->dump_file_path );

		$contents = preg_replace( $patterns, $replacements, $contents );

		file_put_contents( $this->dump_file_path, $contents );

	}

	public function perform_import() {

		if ( class_exists( 'HM_Backup' ) && HM_Backup::is_shell_exec_available() ) {
			$this->perform_shell_import();
		}

	}

	public function perform_shell_import() {

		$cmd = 'mysql ';

		//Host
		$host = explode( ':', DB_HOST );

		$host = reset( $host );

		$cmd .= ' -h ' . escapeshellarg( $host );

		// Username
		$cmd .= ' -u ' . escapeshellarg( DB_USER );

		// Don't pass the password if it's blank
		if ( DB_PASSWORD )
			$cmd .= ' -p' . escapeshellarg( DB_PASSWORD );

		// Set the host
		$cmd .= ' -h ' . escapeshellarg( $host );

		// The database we're importing to
		$cmd .= ' ' . escapeshellarg( DB_NAME );

		// The database dump file to import
		$cmd .= ' < ' . escapeshellarg( $this->dump_file_path );

		// Pipe STDERR to STDOUT
		$cmd .= ' 2>&1';

		// Store any returned data in an error
		$std_err = shell_exec( $cmd );

	}

	public function delete_dump_file() {

		return unlink( $this->dump_file_path );
	}

}
