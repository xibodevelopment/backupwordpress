<?php

/**
 * Zip up all the wordpress files.
 *
 * Attempts to use the shell zip command, if
 * thats not available then it fallsback on
 * PHP zip classes.
 *
 * @param string $path
 */
function hmbkp_archive_files( $path ) {

	// Do we have the path to the zip command
	if ( hmbkp_zip_path() ) :

		// Zip up ABSPATH
		if ( ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY ) || !defined( 'HMBKP_DATABASE_ONLY' ) ) :

			$excludes = ' -x ' . hmbkp_exclude_string( 'zip' );

			shell_exec( 'cd ' . escapeshellarg( ABSPATH ) . ' && ' . escapeshellarg( hmbkp_zip_path() ) . ' -rq ' . escapeshellarg( $path ) . ' ./' . $excludes );

		endif;

		// Add the database dump to the archive
		if ( ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY ) || !defined( 'HMBKP_FILES_ONLY' ) ) :
			shell_exec( 'cd ' . escapeshellarg( hmbkp_path() ) . ' && ' . escapeshellarg( hmbkp_zip_path() ) . ' -uq ' . escapeshellarg( $path ) . ' ' . escapeshellarg( 'database_' . DB_NAME . '.sql' ) );
		endif;

	// If not use the fallback
	else :
		hmbkp_archive_files_fallback( $path );

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

/**
 * Returns an array of default exclude paths
 *
 * @access public
 * @return array
 */
function hmbkp_excludes() {

	// Exclude the back up path
	$excludes[] = hmbkp_path();

	// Exclude the default back up path
	$excludes[] = hmbkp_path_default();

	// Exclude the custom path if one is defined
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
		$excludes[] = hmbkp_conform_dir( HMBKP_PATH );

	return array_map( 'trailingslashit', array_unique( $excludes ) );

}

/**
 * Generate the exclude param string for the zip backup
 *
 * Takes the exclude rules and formats them for use with either
 * the shell zip command or pclzip
 *
 * @param string $context. (default: 'zip')
 * @return string
 */
function hmbkp_exclude_string( $context = 'zip' ) {

	// Return a comma separated list by default
	$separator = ', ';
	$wildcard = '';

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

	// Add any defined excludes
	if ( defined( 'HMBKP_EXCLUDE' ) && HMBKP_EXCLUDE )
		$excludes = array_merge( explode( ',', HMBKP_EXCLUDE ), $excludes );

	$excludes = array_map( 'trim', $excludes );

	// Add wildcards to the directories
	foreach( $excludes as $key => &$rule ) :

		$file = $absolute = $fragment = false;

		// Files don't end with /
		if ( !in_array( substr( $rule, -1 ), array( '\\', '/' ) ) )
			$file = true;

		// If rule starts with a / then treat as absolute path
		elseif ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) )
			$absolute = true;

		// Otherwise treat as dir fragment
		else
			$fragment = true;

		// Strip ABSPATH and conform
		$rule = str_replace( hmbkp_conform_dir( ABSPATH ), '', untrailingslashit( hmbkp_conform_dir( $rule ) ) );

		if ( in_array( substr( $rule, 0, 1 ), array( '\\', '/' ) ) )
			$rule = substr( $rule, 1 );

		// Escape string for regex
		if ( $context == 'pclzip' )
			//$rule = preg_quote( $rule );
			$rule = str_replace( '.', '\.', $rule );

		// Convert any existing wildcards
		if ( $wildcard != '*' && strpos( $rule, '*' ) !== false )
			$rule = str_replace( '*', $wildcard, $rule );

		// Wrap directory fragments in wildcards for zip
		if ( $context == 'zip' && $fragment )
			$rule = $wildcard . $rule . $wildcard;

		// Add a wildcard to the end of absolute url for zips
		if ( $context == 'zip' && $absolute )
			$rule .= $wildcard;

		// Add and end carrot to files for pclzip
		if ( $file && $context == 'pclzip' )
			$rule .= '$';

		// Add a start carrot to absolute urls for pclzip
		if ( $absolute && $context == 'pclzip' )
			$rule = '^' . $rule;

	endforeach;

	// Escape shell args for zip command
	if ( $context == 'zip' )
		$excludes = array_map( 'escapeshellarg', $excludes );

	return implode( $separator, $excludes );

}

/**
 * Return an array of invalid custom exclude rules
 *
 * @return array
 */
function hmbkp_invalid_custom_excludes() {

	$invalid_rules = array();

	// Check if any absolute path excludes actually exist
	if ( defined( 'HMBKP_EXCLUDE' ) && HMBKP_EXCLUDE )
		foreach ( explode( ',', HMBKP_EXCLUDE ) as $rule )
			if ( ( $rule = trim( $rule ) ) && in_array( substr( $rule, 0, 1 ), array( '/', '\\' ) ) && !file_exists( $rule ) && !file_exists( ABSPATH . $rule ) && !file_exists( trailingslashit( ABSPATH ) . $rule ) )
				$invalid_rules[] = $rule;

	return $invalid_rules;

}

/**
 * Return an array of valid custom exclude rules
 *
 * @return array
 */
function hmbkp_valid_custom_excludes() {

	$valid_rules = array();

	if ( defined( 'HMBKP_EXCLUDE' ) && HMBKP_EXCLUDE )
		$valid_rules = array_diff( explode( ',', HMBKP_EXCLUDE ), hmbkp_invalid_custom_excludes() );

	return array_map( 'trim', $valid_rules );

}