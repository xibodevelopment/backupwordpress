<?php

/**
 * Create the mysql backup
 *
 * Uses mysqldump if available, fallsback to PHP
 * if not.
 */
function hmbkp_backup_mysql() {

	// Use mysqldump if we can
	if ( hmbkp_mysqldump_path() ) {

		// Path to the mysqldump executable
		$cmd = escapeshellarg( hmbkp_mysqldump_path() );

		// No Create DB command
		$cmd .= ' --no-create-db';
		
		// Make sure binary data is exported properly
		$cmd .= ' --hex-blob';

		// Username
		$cmd .= ' -u ' . escapeshellarg( DB_USER );

		// Don't pass the password if it's blank
		if ( DB_PASSWORD )
		    $cmd .= ' -p'  . escapeshellarg( DB_PASSWORD );

		// Set the host
		$cmd .= ' -h ' . escapeshellarg( DB_HOST );

		// Save the file
		$cmd .= ' -r ' . escapeshellarg( hmbkp_path() . '/database_' . DB_NAME . '.sql' );

		// The database we're dumping
		$cmd .= ' ' . escapeshellarg( DB_NAME );

		shell_exec( $cmd );

		// If the file doesn't exist then the shell_exec must have failed
		if ( file_exists( hmbkp_path() . '/database_' . DB_NAME . '.sql' ) )
			return true;

	}

	// Fallback to using the PHP library
	hmbkp_backup_mysql_fallback();
}

/**
 * Attempt to work out the path to mysqldump
 *
 * Can be overridden by defining HMBKP_MYSQLDUMP_PATH in
 * wp-config.php.
 *
 * @return string $path on success, empty string on failure
 */
function hmbkp_mysqldump_path() {

	if ( !hmbkp_shell_exec_available() || ( defined( 'HMBKP_MYSQLDUMP_PATH' ) && !HMBKP_MYSQLDUMP_PATH ) )
		return false;

	$path = '';

	// List of possible mysqldump locations
	$mysqldump_locations = array(
		'mysqldump',
		'/usr/local/bin/mysqldump',
		'/usr/local/mysql/bin/mysqldump',
		'/usr/mysql/bin/mysqldump',
		'/usr/bin/mysqldump',
		'/opt/local/lib/mysql6/bin/mysqldump',
		'/opt/local/lib/mysql5/bin/mysqldump',
		'/opt/local/lib/mysql4/bin/mysqldump',
		'\xampp\mysql\bin\mysqldump',
		'\Program Files\xampp\mysql\bin\mysqldump',
		'\Program Files\MySQL\MySQL Server 6.0\bin\mysqldump',
		'\Program Files\MySQL\MySQL Server 5.5\bin\mysqldump',
		'\Program Files\MySQL\MySQL Server 5.4\bin\mysqldump',
		'\Program Files\MySQL\MySQL Server 5.1\bin\mysqldump',
		'\Program Files\MySQL\MySQL Server 5.0\bin\mysqldump',
		'\Program Files\MySQL\MySQL Server 4.1\bin\mysqldump'
	);

	// Allow the path to be overridden
	if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) && HMBKP_MYSQLDUMP_PATH )
		array_unshift( $mysqldump_locations, HMBKP_MYSQLDUMP_PATH );

 	// If we don't have a path set
 	if ( !$path = get_option( 'hmbkp_mysqldump_path' ) ) :

		// Try to find out where mysqldump is
		foreach ( $mysqldump_locations as $location )
	 		if ( shell_exec( $location ) )
 				$path = $location;

		// Save it for later
 		if ( $path )
			update_option( 'hmbkp_mysqldump_path', $path );

	endif;

	// Check again in-case the saved path has stopped working for some reason
	if ( $path && !shell_exec( $path ) ) :
		delete_option( 'hmbkp_mysqldump_path' );
		return hmbkp_mysqldump_path();

	endif;

	return $path;

}