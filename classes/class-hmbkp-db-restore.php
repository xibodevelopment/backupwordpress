<?php

class HMBKP_DB_Restore {

	protected $new_prefix = '';

	protected $dump_file_path = '';

	protected $dump_file_archive = '';

	protected $error;

	public function __construct( $dump_file_archive ) {

		$this->dump_file_archive = $dump_file_archive;

		$parts = pathinfo( $this->dump_file_archive );

		$this->dump_file_path = trailingslashit( $parts['dirname'] ) . $parts['filename'] . '.sql';

	}


	public function restore_database() {

		// Extract the DB dump from the zip archive
		$extracted = $this->extract_dump_file();

		if ( ! is_wp_error( $extracted ) ) {

			// Rename table prefix
			$this->update_table_prefix();

			// Run import
			$this->perform_import();

			// Clean up
			$this->delete_dump_file();

		} else {
			return $extracted->get_error_message();
		}

	}

	public function update_table_prefix() {

		$this->new_prefix = 'bwp' . time() . '_';

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
			'CREATE TABLE ' . $this->new_prefix . '$1 (',
			'DROP TABLE IF EXISTS `' . $this->new_prefix . '$1`;',
			'-- Table structure for table `' . $this->new_prefix . '$1`',
			'-- Dumping data for table `' . $this->new_prefix . '$1`',
			'LOCK TABLES `' . $this->new_prefix . '$1` WRITE;',
			'/*!40000 ALTER TABLE `' . $this->new_prefix . '$1` ENABLE KEYS */;',
			'/*!40000 ALTER TABLE `' . $this->new_prefix . '$1` DISABLE KEYS */;',
			'INSERT INTO `' . $this->new_prefix . '$1` VALUES '
		);

		$contents = file_get_contents( $this->dump_file_path );

		$contents = preg_replace( $patterns, $replacements, $contents );

		file_put_contents( $this->dump_file_path, $contents );

	}

	public function extract_dump_file() {

		WP_Filesystem();

		return unzip_file( $this->dump_file_archive, pathinfo( $this->dump_file_archive, PATHINFO_DIRNAME ) );

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
