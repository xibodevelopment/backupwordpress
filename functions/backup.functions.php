<?php

/**
 * Backup database and files
 *
 * Creates a temporary directory containing a copy of all files
 * and a dump of the database. Then zip that up and delete the temporary files
 *
 * @uses hmbkp_backup_mysql
 * @uses hmbkp_backup_files
 * @uses hmbkp_delete_old_backups
 */
function hmbkp_do_backup() {

	// Make sure it's possible to do a backup
	if ( !hmbkp_possible() )
		return false;

	// Clean up any mess left by the last backup
	hmbkp_cleanup();

    $time_start = date( 'Y-m-d-H-i-s' );

	$filename = sanitize_file_name( get_bloginfo( 'name' ) . '.backup.' . $time_start . '.zip' );
	$filepath = trailingslashit( hmbkp_path() ) . $filename;

	// Set as running for a max of 1 hour
	hmbkp_set_status();

	// Raise the memory limit
	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', '256M' ) );
	@set_time_limit( 0 );

    hmbkp_set_status( __( 'Dumping database', 'hmbkp' ) );

	// Backup database
	if ( ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY ) || !defined( 'HMBKP_FILES_ONLY' ) )
	    hmbkp_backup_mysql();

	hmbkp_set_status( __( 'Creating zip archive', 'hmbkp' ) );

	// Zip everything up
	hmbkp_archive_files( $filepath );

	// Delete the database dump file
	if ( ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY ) || !defined( 'HMBKP_FILES_ONLY' ) )
		unlink( hmbkp_path() . '/database_' . DB_NAME . '.sql' );

	// Email Backup
	hmbkp_email_backup( $filepath );

    hmbkp_set_status( __( 'Removing old backups', 'hmbkp' ) );

	// Delete any old backup files
    hmbkp_delete_old_backups();
    
    unlink( hmbkp_path() . '/.backup_running' );
    
	$file = hmbkp_path() . '/.backup_complete';
	
	if ( !$handle = @fopen( $file, 'w' ) )
		return false;
	
	fwrite( $handle );
	
	fclose( $handle );


}

/**
 * Deletes old backup files
 */
function hmbkp_delete_old_backups() {

    $files = hmbkp_get_backups();

    if ( count( $files ) <= hmbkp_max_backups() )
    	return;

    foreach( array_slice( $files, hmbkp_max_backups() ) as $file )
       	hmbkp_delete_backup( base64_encode( $file ) );

}

/**
 * Returns an array of backup files
 */
function hmbkp_get_backups() {

    $files = array();

    $hmbkp_path = hmbkp_path();

    if ( $handle = opendir( $hmbkp_path ) ) :

    	while ( false !== ( $file = readdir( $handle ) ) )
    		if ( strpos( $file, '.zip' ) !== false )
	   			$files[filemtime( trailingslashit( $hmbkp_path ) . $file )] = trailingslashit( $hmbkp_path ) . $file;

    	closedir( $handle );

    endif;

    // If there is a custom backups directory and it's not writable then include those backups as well
    if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && is_dir( HMBKP_PATH ) && !is_writable( HMBKP_PATH ) ) :

    	if ( $handle = opendir( HMBKP_PATH ) ) :

    		while ( false !== ( $file = readdir( $handle ) ) )
    			if ( strpos( $file, '.zip' ) !== false )
		   			$files[filemtime( trailingslashit( HMBKP_PATH ) . $file )] = trailingslashit( HMBKP_PATH ) . $file;

    		closedir( $handle );

    	endif;

	endif;

    krsort( $files );

	if( empty($files) )
		return false;

    return $files;
}

/**
 * Delete a backup file
 *
 * @param $file base64 encoded filename
 */
function hmbkp_delete_backup( $file ) {

	$file = base64_decode( $file );

	// Delete the file
	if ( strpos( $file, hmbkp_path() ) !== false || strpos( $file, WP_CONTENT_DIR . '/backups' ) !== false )
	  unlink( $file );

}

/**
 * Check if a backup is running
 *
 * @return bool
 */
function hmbkp_is_in_progress() {
	return file_exists( hmbkp_path() . '/.backup_running' );
}

/**
  * Email backup.
  *
  *	@param $file
  * @return bool
  */
function hmbkp_email_backup( $file ) {

	if ( !defined('HMBKP_EMAIL' ) || !HMBKP_EMAIL || !is_email( HMBKP_EMAIL ) )
		return;

	// Raise the memory and time limit
	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', '256M' ) );
	@set_time_limit( 0 );

	$download = get_bloginfo( 'wpurl' ) . '/wp-admin/tools.php?page=' . HMBKP_PLUGIN_SLUG . '&hmbkp_download=' . base64_encode( $file );
	$domain = parse_url( get_bloginfo( 'url' ), PHP_URL_HOST ) . parse_url( get_bloginfo( 'url' ), PHP_URL_PATH );

	$subject = sprintf( __( 'Backup of %s', 'hmbkp' ), $domain );
	$message = sprintf( __( "BackUpWordPress has completed a backup of your site %s.\n\nThe backup file should be attached to this email.\n\nYou can also download the backup file by clicking the link below:\n\n%s\n\nKind Regards\n\n The Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), get_bloginfo( 'url' ), $download );
	$headers = 'From: BackUpWordPress <' . get_bloginfo( 'admin_email' ) . '>' . "\r\n";

	// Try to send the email
	$sent = wp_mail( HMBKP_EMAIL, $subject, $message, $headers, $file );

	// If it failed- Try to send a download link - The file was probably too large.
	if ( !$sent ) :

		$subject = sprintf( __( 'Backup of %s', 'hmbkp' ), $domain );
		$message = sprintf( __( "BackUpWordPress has completed a backup of your site %s.\n\nUnfortunately the backup file was too large to attach to this email.\n\nYou can download the backup file by clicking the link below:\n\n%s\n\nKind Regards\n\n The Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), get_bloginfo( 'url' ), $download );

		$sent = wp_mail( HMBKP_EMAIL, $subject, $message, $headers );

	endif;

	// Set option for email not sent error
	if ( !$sent )
		update_option( 'hmbkp_email_error', 'hmbkp_email_failed' );
	else
		delete_option( 'hmbkp_email_error' );

	return true;

}

/**
 * Set the status of the running backup
 * 
 * @param string $message. (default: '')
 * @return void
 */
function hmbkp_set_status( $message = '' ) {
	
	$file = hmbkp_path() . '/.backup_running';
	
	if ( !$handle = @fopen( $file, 'w' ) )
		return false;
	
	fwrite( $handle, $message );
	
	fclose( $handle );
	
}

/**
 * Get the status of the running backup
 * 
 * @return string
 */
function hmbkp_get_status() {
	
	if ( !file_exists( hmbkp_path() . '/.backup_running' ) )
		return false;
		
	return file_get_contents( hmbkp_path() .'/.backup_running' );
	
}