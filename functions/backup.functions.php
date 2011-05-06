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

	// Make sure it's safe to do a backup
	if ( !is_writable( hmbkp_path() ) || !is_dir( hmbkp_path() ) || ini_get( 'safe_mode' ) )
		return false;

    $time_start = date( 'Y-m-d-H-i-s' );

	$filename = $time_start . '.zip';
	$filepath = trailingslashit( hmbkp_path() ) . $filename;
	
	// Set as running for a max of 2 hours
	set_transient( 'hmbkp_running', $time_start, 7200 );

	// Raise the memory limit
	ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', '256M' ) );
	set_time_limit( 0 );

    update_option( 'hmbkp_status', __( 'Creating tmp directory.', 'hmbkp' ) );

	// Create a temporary directory for this backup
    $backup_tmp_dir = hmbkp_create_tmp_dir( $time_start );

    update_option( 'hmbkp_status', __( 'Dumping database to tmp folder.', 'hmbkp' ) );

	// Backup database
	if ( ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY ) || !defined( 'HMBKP_FILES_ONLY' ) )
	    hmbkp_backup_mysql( $backup_tmp_dir );

	update_option( 'hmbkp_status', __( 'Copying files to tmp folder.', 'hmbkp' ) );

	// Backup files
	if ( ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY ) || !defined( 'HMBKP_DATABASE_ONLY' ) )
		hmbkp_backup_files( $backup_tmp_dir );

    update_option( 'hmbkp_status', __( 'Zipping up tmp directory.', 'hmbkp' ) );

	// Zip up the files
	hmbkp_archive_files( $backup_tmp_dir, $filepath );

    update_option( 'hmbkp_status', __( 'Removing tmp directory.', 'hmbkp' ) );

	// Remove the temporary directory
	hmbkp_rmdirtree( $backup_tmp_dir );

	//Email Backup
	hmbkp_email_backup( $filepath );

    update_option( 'hmbkp_status', __( 'Removing old backups.', 'hmbkp' ) );

	// Delete any old backup files
    hmbkp_delete_old_backups();

    delete_transient( 'hmbkp_running' );
    delete_option( 'hmbkp_status' );

    update_option( 'hmbkp_complete', true );

}

/**
 * Deletes old backup files
 */
function hmbkp_delete_old_backups() {

    $files = hmbkp_get_backups();

    if ( count( $files ) <= hmbkp_max_backups() )
    	return;

    foreach ( $files as $key => $f )
        if ( ( $key + 1 ) > hmbkp_max_backups() )
        	hmbkp_delete_backup( base64_encode( $f['file'] ) );

}

/**
 * Returns an array of backup files
 */
function hmbkp_get_backups() {

    $files = array();

    $hmbkp_path = hmbkp_path();

    if ( !is_writable( $hmbkp_path ) )
    	return;

    if ( $handle = opendir( $hmbkp_path ) ) :

    	while ( false !== ( $file = readdir( $handle ) ) )
    		if ( ( substr( $file, 0, 1 ) != '.' ) && !is_dir( trailingslashit( $hmbkp_path ) . $file ) && strpos( $file, '.zip' ) !== false )
    			$files[] = array( 'file' => trailingslashit( $hmbkp_path ) . $file, 'filename' => $file );

    	closedir( $handle );

    endif;

    if ( count( $files ) < 1 )
    	return;

    foreach ( $files as $key => $row )
    	$filename[$key] = $row['filename'];

    array_multisort( $filename, SORT_DESC, $files );

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
 * Create and return the path to the tmp directory
 *
 * @param string $date
 * @return string
 */
function hmbkp_create_tmp_dir( $date ) {

    $backup_tmp_dir = trailingslashit( hmbkp_path() ) . $date;

    if ( !is_dir( $backup_tmp_dir ) )
		mkdir( $backup_tmp_dir );

    return $backup_tmp_dir;

}

/**
 * Check if a backup is running
 *
 * @return bool
 */
function hmbkp_is_in_progress() {
	return (bool) get_transient( 'hmbkp_running' );
}

/**
  * Email backup.
  *
  *	@param $file
  * @return bool
  */
function hmbkp_email_backup( $file ) {

	if( !defined('HMBKP_EMAIL' ) || !HMBKP_EMAIL )
		return;
	
	if( !is_email( HMBKP_EMAIL ) ) 
		return;

	// Raise the memory limit
	ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', '256M' ) );
	set_time_limit( 0 );
			$subject = sprintf( __('Backup from %s', 'hmbkp' ), get_bloginfo('url') );
	$message = __( 'Backup Complete. The zip file of your backup is attached to this email.', 'hmbkp' );
	$headers = 'From: BackUpWordPress <' . get_bloginfo('admin_email') . '>' . "\r\n";
		
	// Try to send the email
	$sent = wp_mail( HMBKP_EMAIL, $subject, $message, $headers, $file );

	// If it failed- Try to send a download link - The file was probably too large. 
	if( !$sent ) :
		
		$subject = sprintf( __( 'BACKUP FAILED. %s not backed up', 'hmbkp' ), get_bloginfo( 'url' ) );
		$url = get_bloginfo('wpurl') . '/wp-admin/tools.php?page=' . HMBKP_PLUGIN_SLUG . '&hmbkp_download=' . base64_encode( $file );
		$message = 'Looks like the Backup is too large to email ('  . round(filesize($file) / 1048576, 2) . 'MB) - Download backup: ' . $url;
		
		$sent = wp_mail( $email_address, $subject, $message, $headers );

	endif;
	
	//Set option for email not sent error
	if( !$sent ) :
		update_option('hmbkp_email_errors', 'hmbkp_email_failed' );
		return false;
	else :
		delete_option('hmbkp_email_errors' );
	endif;
	
	return true;
	
} 