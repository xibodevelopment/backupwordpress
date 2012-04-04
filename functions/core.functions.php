<?php

/**
 * Setup the plugin defaults on activation
 */
function hmbkp_activate() {

	hmbkp_deactivate();

	hmbkp_setup_schedule();

}

/**
 * Cleanup on plugin deactivation
 *
 * Removes options and clears all cron schedules
 */
function hmbkp_deactivate() {

	hmbkp_setup_hm_backup();

	// Options to delete
	$options = array(
		'hmbkp_zip_path',
		'hmbkp_mysqldump_path',
		'hmbkp_path',
		'hmbkp_running',
		'hmbkp_status',
		'hmbkp_complete',
		'hmbkp_email_error'
	);

	foreach ( $options as $option )
		delete_option( $option );

	delete_transient( 'hmbkp_running' );
	delete_transient( 'hmbkp_estimated_filesize' );

	// Clear cron
	wp_clear_scheduled_hook( 'hmbkp_schedule_backup_hook' );
	wp_clear_scheduled_hook( 'hmbkp_schedule_single_backup_hook' );

	hmbkp_cleanup();

}

/**
 * Handles anything that needs to be
 * done when the plugin is updated
 */
function hmbkp_update() {

	// Every update
	if ( version_compare( HMBKP_VERSION, get_option( 'hmbkp_plugin_version' ), '>' ) ) {

		hmbkp_deactivate();

		// Force .htaccess to be re-written
		if ( file_exists( hmbkp_path() . '/.htaccess' ) )
			unlink( hmbkp_path() . '/.htaccess' );

	}

	// Update from backUpWordPress 0.4.5
	if ( get_option( 'bkpwp_max_backups' ) ) :

		// Carry over the custom path
		if ( $legacy_path = get_option( 'bkpwppath' ) )
			update_option( 'hmbkp_path', $legacy_path );

		// Options to remove
		$legacy_options = array(
			'bkpwp_archive_types',
			'bkpwp_automail_from',
			'bkpwp_domain',
			'bkpwp_domain_path',
			'bkpwp_easy_mode',
			'bkpwp_excludelists',
			'bkpwp_install_user',
			'bkpwp_listmax_backups',
			'bkpwp_max_backups',
			'bkpwp_presets',
			'bkpwp_reccurrences',
			'bkpwp_schedules',
			'bkpwp_calculation',
			'bkpwppath',
			'bkpwp_status_config',
			'bkpwp_status'
		);

		foreach ( $legacy_options as $option )
			delete_option( $option );

	    global $wp_roles;

		$wp_roles->remove_cap( 'administrator','manage_backups' );
		$wp_roles->remove_cap( 'administrator','download_backups' );

		wp_clear_scheduled_hook( 'bkpwp_schedule_bkpwp_hook' );

	endif;

	// Update the stored version
	if ( get_option( 'hmbkp_plugin_version' ) !== HMBKP_VERSION )
		update_option( 'hmbkp_plugin_version', HMBKP_VERSION );

}

/**
 * Add weekly, fortnightly and monthly as a cron schedule choices
 *
 * @param array $reccurrences
 * @return array $reccurrences
 */
function hmbkp_more_reccurences( $reccurrences ) {

	return array_merge( $reccurrences, array(
	    'weekly' => array( 'interval' => 604800, 'display' => 'Once Weekly' ),
	    'fortnightly' => array( 'interval' => 1209600, 'display' => 'Once Fortnightly' ),
	    'monthly' => array( 'interval' => 2629743.83 , 'display' => 'Once Monthly' )
	) );
}
add_filter( 'cron_schedules', 'hmbkp_more_reccurences' );

/**
 * Recursively delete a directory including
 * all the files and sub-directories.
 *
 * @param string $dir
 */
function hmbkp_rmdirtree( $dir ) {

	if ( is_file( $dir ) )
		unlink( $dir );

    if ( ! is_dir( $dir ) )
    	return false;

    $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ), RecursiveIteratorIterator::CHILD_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );

	foreach ( $files as $file ) {

		if ( $file->isDir() )
			@rmdir( $file->getPathname() );

		else
			@unlink( $file->getPathname() );

	}

	@rmdir( $dir );

}

/**
 * Calculate the total filesize of all backups
 *
 * @return string
 */
function hmbkp_total_filesize() {

	$files = hmbkp_get_backups();
	$filesize = 0;

	clearstatcache();

   	foreach ( $files as $f )
		$filesize += @filesize( $f );

	return hmbkp_size_readable( $filesize );

}

/**
 * Get the path to the backups directory
 *
 * Will try to create it if it doesn't exist
 * and will fallback to default if a custom dir
 * isn't writable.
 */
function hmbkp_path() {

	$path = get_option( 'hmbkp_path' );

	// Allow the backups path to be defined
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
		$path = HMBKP_PATH;

	// If the dir doesn't exist or isn't writable then use wp-content/backups instead
	if ( ( ! $path || ! is_writable( $path ) ) && hmbkp_conform_dir( $path ) != hmbkp_path_default() )
    	$path = hmbkp_path_default();

	// Create the backups directory if it doesn't exist
	if ( is_writable( dirname( $path ) ) && ! is_dir( $path ) )
		mkdir( $path, 0755 );

	if ( get_option( 'hmbkp_path' ) != $path )
		update_option( 'hmbkp_path', $path );

	// Secure the directory with a .htaccess file
	$htaccess = $path . '/.htaccess';

	$contents[]	= '# ' . __( 'This .htaccess file ensures that other people cannot download your backup files.', 'hmbkp' );
	$contents[] = '';
	$contents[] = '<IfModule mod_rewrite.c>';
	$contents[] = 'RewriteEngine On';
	$contents[] = 'RewriteCond %{QUERY_STRING} !key=' . md5( HMBKP_SECURE_KEY );
	$contents[] = 'RewriteRule (.*) - [F]';
	$contents[] = '</IfModule>';
	$contents[] = '';

	if ( ! file_exists( $htaccess ) && is_writable( $path ) && require_once( ABSPATH . '/wp-admin/includes/misc.php' ) )
		insert_with_markers( $htaccess, 'BackUpWordPress', $contents );

    return hmbkp_conform_dir( $path );
}

/**
 * Return the default backup path
 *
 * @return string path
 */
function hmbkp_path_default() {
	return hmbkp_conform_dir( WP_CONTENT_DIR . '/backups' );
}

/**
 * Move the backup directory and all existing backup files to a new
 * location
 *
 * @param string $from path to move the backups dir from
 * @param string $to path to move the backups dir to
 * @return void
 */
function hmbkp_path_move( $from, $to ) {

	// Create the custom backups directory if it doesn't exist
	if ( is_writable( dirname( $to ) ) && ! is_dir( $to ) )
	    mkdir( $to, 0755 );

	if ( ! is_dir( $to ) || ! is_writable( $to ) || ! is_dir( $from ) )
	    return false;

	hmbkp_cleanup();

	if ( $handle = opendir( $from ) ) :

	    while ( false !== ( $file = readdir( $handle ) ) )
	    	if ( $file != '.' && $file != '..' )
	    		rename( trailingslashit( $from ) . $file, trailingslashit( $to ) . $file );

	    closedir( $handle );

	endif;

	hmbkp_rmdirtree( $from );

}

/**
 *	Returns defined email address or email address saved in options.
 *	If none set, return empty string.
 */
function hmbkp_get_email_address( $type = 'array' ) {

	$email = '';

	if ( defined( 'HMBKP_EMAIL' ) && HMBKP_EMAIL )
		$email = HMBKP_EMAIL;

	elseif ( get_option( 'hmbkp_email_address' ) )
		$email = get_option( 'hmbkp_email_address' );

	if ( ! empty( $email ) && $type == 'array' )
		$email = array_filter( array_map( 'trim', explode( ',', $email ) ) );

	return $email;

}

/**
 * Check if a backup is possible with regards to file
 * permissions etc.
 *
 * @return bool
 */
function hmbkp_possible() {

	if ( ! is_writable( hmbkp_path() ) || ! is_dir( hmbkp_path() ) || hmbkp_is_safe_mode_active() )
		return false;

	if ( defined( 'HMBKP_FILES_ONLY' ) && HMBKP_FILES_ONLY && defined( 'HMBKP_DATABASE_ONLY' ) && HMBKP_DATABASE_ONLY )
		return false;

	return true;
}

/**
 * Remove any non backup.zip files from the backups dir.
 *
 * @return void
 */
function hmbkp_cleanup() {

	delete_option( 'hmbkp_email_error' );

	$hmbkp_path = hmbkp_path();

	if ( ! is_dir( $hmbkp_path ) )
		return;

	if ( $handle = opendir( $hmbkp_path ) ) :

    	while ( false !== ( $file = readdir( $handle ) ) )
    		if ( ! in_array( $file, array( '.', '..', '.htaccess' ) ) && pathinfo( $file, PATHINFO_EXTENSION ) !== 'zip' )
				hmbkp_rmdirtree( trailingslashit( $hmbkp_path ) . $file );

    	closedir( $handle );

    endif;

}

function hmbkp_conform_dir( $dir ) {
	
	$HM_Backup = new HM_Backup();
	
	return $HM_Backup->conform_dir( $dir );
	
}

function hmbkp_is_safe_mode_active() {
	return HM_Backup::is_safe_mode_active();
}