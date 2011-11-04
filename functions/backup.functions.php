<?php

/**
 * Backup database and files
 */
function hmbkp_do_backup() {

	// Clean up any mess left by the last backup
	hmbkp_cleanup();

	$backup = new HMBackup();
	
	$backup->path = hmbkp_path();
	$backup->files_only = hmbkp_get_files_only();
	$backup->database_only = hmbkp_get_database_only();
	
	if ( defined( 'MYSQLDUMP_PATH' ) && MYSQLDUMP_PATH )
		$backup->mysqldump_path = MYSQLDUMP_PATH;

	if ( defined( 'ZIP_PATH' ) && ZIP_PATH )
		$backup->mysqldump_path = ZIP_PATH;
		
	$backup->excludes = hmbkp_get_excludes();
	
	$backup->backup();

	// Email Backup
	hmbkp_email_backup( $filepath );

    hmbkp_set_status( __( 'Removing old backups', 'hmbkp' ) );

	// Delete any old backup files
    hmbkp_delete_old_backups();
    
    unlink( hmbkp_path() . '/.backup_running' );
    
	$file = hmbkp_path() . '/.backup_complete';
	
	if ( !$handle = @fopen( $file, 'w' ) )
		return false;
	
	fwrite( $handle, '' );
	
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

	if ( !hmbkp_get_email_address() )
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
	$sent = wp_mail( hmbkp_get_email_address(), $subject, $message, $headers, $file );

	// If it failed- Try to send a download link - The file was probably too large.
	if ( !$sent ) :

		$subject = sprintf( __( 'Backup of %s', 'hmbkp' ), $domain );
		$message = sprintf( __( "BackUpWordPress has completed a backup of your site %s.\n\nUnfortunately the backup file was too large to attach to this email.\n\nYou can download the backup file by clicking the link below:\n\n%s\n\nKind Regards\n\n The Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), get_bloginfo( 'url' ), $download );

		$sent = wp_mail( hmbkp_get_email_address(), $subject, $message, $headers );

	endif;

	// Set option for email not sent error
	if ( !$sent )
		update_option( 'hmbkp_email_error', 'hmbkp_email_failed' );
	else
		delete_option( 'hmbkp_email_error' );

	return true;

}

/**
 * Return an array of invalid custom exclude rules
 *
 * @return array
 */
function hmbkp_invalid_custom_excludes() {
 
    $invalid_rules = array();
 
    // Check if any absolute path excludes actually exist
    if ( $excludes = hmbkp_get_excludes() )
     
        foreach ( explode( ',', $excludes ) as $rule )
            if ( ( $rule = trim( $rule ) ) && in_array( substr( $rule, 0, 1 ), array( '/', '\\' ) ) && !file_exists( $rule ) && ! file_exists( ABSPATH . $rule ) && ! file_exists( trailingslashit( ABSPATH ) . $rule ) )
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
 
    $excludes = hmbkp_get_excludes();
     
    if( ! $excludes )
        return;
 
    $valid_rules = array_diff( explode( ',', $excludes ), hmbkp_invalid_custom_excludes() );
     
 
 
    return array_map( 'trim', $valid_rules );
 
}