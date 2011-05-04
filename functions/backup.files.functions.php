<?php

/**
 * Copy the whole site to the temporary directory and
 * then zip it all up
 *
 * @param string $backup_tmp_dir
 */
function hmbkp_backup_files( $backup_tmp_dir ) {

    $wordpress_files = $backup_tmp_dir . '/wordpress_files';

	if ( !is_dir( $wordpress_files ) )
        mkdir( $wordpress_files, 0755 );

    // Copy the whole site to the temporary directory
    $files = hmbkp_ls( hmbkp_conform_dir( ABSPATH ) );

    $files_copied = $subdirs_created = 0;
    $i = 1;

    foreach ( (array) $files as $f ) :

        if ( is_dir( $f ) ) :

        	if ( mkdir( $wordpress_files . hmbkp_conform_dir( $f, true ), 0755 ) ) :
        		$subdirs_created++;

        	endif;

        elseif ( file_exists( $f ) ) :

        	$files_copied++;

        	if ( file_exists( $wordpress_files . hmbkp_conform_dir( $f, true ) ) )
        		unlink( $wordpress_files . hmbkp_conform_dir( $f, true ) );

        	// Copy the file
        	copy( $f, $wordpress_files . hmbkp_conform_dir( $f, true ) );
        	
        	// Chown the file so we can delete it
        	chmod( $f, 0644 );

        endif;

        $i++;
    
    endforeach;

}

/**
 * Zip up all the files in the tmp directory.
 *
 * Attempts to use the shell zip command, if
 * thats not available then it fallsback on
 * PHP zip classes.
 *
 * @param string $backup_tmp_dir
 * @param string $backup_filepath
 */
function hmbkp_archive_files( $backup_tmp_dir, $backup_filepath ) {

	// Do we have the path to the zip command
	if ( hmbkp_zip_path() )
		shell_exec( 'cd ' . escapeshellarg( $backup_tmp_dir ) . ' && zip -r ' . escapeshellarg( $backup_filepath ) . ' ./' );

	else
		hmbkp_archive_files_fallback( $backup_tmp_dir, $backup_filepath );

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