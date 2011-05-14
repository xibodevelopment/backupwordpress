<?php

/**
 * Zip up all the wordpress files.
 *
 * Attempts to use the shell zip command, if
 * thats not available then it fallsback on
 * PHP zip classes.
 *
 * @param string $backup_filepath
 */
function hmbkp_archive_files( $backup_filepath ) {

	// Do we have the path to the zip command
	if ( hmbkp_zip_path() ) :

		// Zip up ABSPATH
		if ( ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY ) || !defined( 'HMBKP_DATABASE_ONLY' ) ) :

			$exclude = ' -x ' . hmbkp_exclude_string( 'zip' );

			shell_exec( 'cd ' . escapeshellarg( ABSPATH ) . ' && ' . escapeshellarg( hmbkp_zip_path() ) . ' -rq ' . escapeshellarg( $backup_filepath ) . ' ./' . $exclude );

		endif;

		// Add the database dump to the archive
		if ( ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY ) || !defined( 'HMBKP_FILES_ONLY' ) ) :
			shell_exec( 'cd ' . escapeshellarg( hmbkp_path() ) . ' && ' . escapeshellarg( hmbkp_zip_path() ) . ' -uq ' . escapeshellarg( $backup_filepath ) . ' ' . escapeshellarg( 'database_' . DB_NAME . '.sql' ) );
		endif;

	// If not use the fallback
	else :
		hmbkp_archive_files_fallback( $backup_filepath );

	endif;

}

/**
 * Attempt to work out the path to the zip command
 *
 * Can be overridden by defining HMBKP_ZIP_PATH in
 * wp-config.php.
 *
 * @return string $path on success, empty string on failure
 */
function hmbkp_zip_path() {

	if ( !hmbkp_shell_exec_available() || ( defined( 'HMBKP_ZIP_PATH' ) && !HMBKP_ZIP_PATH ) )
		return false;

	$path = '';

	// List of possible zip locations
	$zip_locations = array(
		'zip',
		'/usr/bin/zip'
	);

	// Allow the path to be overridden
	if ( defined( 'HMBKP_ZIP_PATH' ) && HMBKP_ZIP_PATH )
		array_unshift( $zip_locations, HMBKP_ZIP_PATH );

 	// If we don't have a path set
 	if ( !$path = get_option( 'hmbkp_zip_path' ) ) :

		// Try to find out where zip is
		foreach ( $zip_locations as $location )
	 		if ( shell_exec( 'which ' . $location ) )
 				$path = $location;

		// Save it for later
 		if ( $path )
			update_option( 'hmbkp_zip_path', $path );

	endif;

	// Check again in-case the saved path has stopped working for some reason
	if ( $path && !shell_exec( 'which ' . $path ) ) :
		delete_option( 'hmbkp_zip_path' );
		return hmbkp_zip_path();

	endif;

	return $path;

}

function hmbkp_excludes() {

	// Exclude the back up path
	$excludes[] = hmbkp_path();

	// Exclude the default back up path
	$excludes[] = hmbkp_path_default();

	// Exclude the custom path if one is defined
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
		$excludes[] = hmbkp_conform_dir( HMBKP_PATH );

	return array_unique( $excludes );

}

function hmbkp_exclude_string( $context = 'zip' ) {

	// Return a comma separated list by default
	$wildcard = ', ';

	// The zip command
	if ( $context == 'zip' ) :
		$wildcard = '*';
		$separator = ' -x ';

	// The PCLZIP fallback library
	elseif ( $context == 'pclzip' ) :
		$wildcard = '([.]*?)';
		$separator = '|';

	endif;

	// Get the excludes
	$excludes = hmbkp_excludes();

	// Add wildcards to the directories
	foreach( $excludes as $key => &$exclude )
		if ( is_dir( $exclude ) )
			$exclude = str_replace( ABSPATH, '', hmbkp_conform_dir( $exclude ) . $wildcard );

	// Escape shell args to zip command
	if ( $context == 'zip' )
		$excludes = array_map( 'escapeshellarg', $excludes );

	return implode( $separator, $excludes );

}