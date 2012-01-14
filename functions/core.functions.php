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
 * Take a file size and return a human readable
 * version
 *
 * @param int $size
 * @param string $unit. (default: null)
 * @param string $retstring. (default: null)
 * @param bool $si. (default: true)
 * @return int
 */
function hmbkp_size_readable( $size, $unit = null, $retstring = '%01.2f %s', $si = true ) {

	// Units
	if ( $si === true ) :
		$sizes = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB' );
		$mod   = 1000;

	else :
		$sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
		$mod   = 1024;

	endif;

	$ii = count( $sizes ) - 1;

	// Max unit
	$unit = array_search( (string) $unit, $sizes );

	if ( is_null( $unit ) || $unit === false )
		$unit = $ii;

	// Loop
	$i = 0;

	while ( $unit != $i && $size >= 1024 && $i < $ii ) {
		$size /= $mod;
		$i++;
	}

	return sprintf( $retstring, $size, $sizes[$i] );
}

/**
 * Add daily as a cron schedule choice
 *
 * @todo can we not use the built in schedules
 * @param array $recc
 * @return array $recc
 */
function hmbkp_more_reccurences( $recc ) {

	$hmbkp_reccurrences = array(
	    'hmbkp_weekly' => array( 'interval' => 604800, 'display' => 'every week' ),
	    'hmbkp_fortnightly' => array( 'interval' => 1209600, 'display' => 'once a fortnight' ),
	    'hmbkp_monthly' => array( 'interval' => 2629743.83 , 'display' => 'once a month' )
	);

	return array_merge( $recc, $hmbkp_reccurrences );
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
 * Calculate the size of the backup
 *
 * Doesn't currently take into account for
 * compression
 *
 * @return string
 */
function hmbkp_calculate() {

    @ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );

    // Check cache
	if ( $filesize = get_transient( 'hmbkp_estimated_filesize' ) )
		return hmbkp_size_readable( $filesize, null, '%01u %s' );

	$filesize = 0;

    // Don't include database if files only
	if ( ! hmbkp_get_files_only() ) {

    	global $wpdb;

    	$res = $wpdb->get_results( 'SHOW TABLE STATUS FROM ' . DB_NAME, ARRAY_A );

    	foreach ( $res as $r )
    		$filesize += (float) $r['Data_length'];

    }

   	if ( ! hmbkp_get_database_only() ) {

    	// Get rid of any cached filesizes
    	clearstatcache();

		foreach ( HM_Backup::get_instance()->files() as $file )
			$filesize += (float) @filesize( ABSPATH . $file );

	}

    // Cache in a transient for a week
    set_transient( 'hmbkp_estimated_filesize', $filesize,  604800 );

    return hmbkp_size_readable( $filesize, null, '%01u %s' );

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
 * Set Up the shedule.
 * This should runn according to the Frequency defined, or set in the option.
 *
 * @access public
 * @return void
 */
function hmbkp_setup_schedule() {

	// Clear any old schedules
	wp_clear_scheduled_hook( 'hmbkp_schedule_backup_hook' );

	if( hmbkp_get_disable_automatic_backup() )
		return;

	// Default to 11 in the evening
	$time = '23:00';

	// Allow it to be overridden
	if ( defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) && HMBKP_DAILY_SCHEDULE_TIME )
		$time = HMBKP_DAILY_SCHEDULE_TIME;

	$offset = current_time( 'timestamp' ) - time();
	$scheduletime_UTC = strtotime( $time ) - $offset;

	if( defined( 'HMBKP_SCHEDULE_FREQUENCY' ) && HMBKP_SCHEDULE_FREQUENCY )
		$schedule_frequency = HMBKP_SCHEDULE_FREQUENCY;
	elseif( get_option('hmbkp_schedule_frequency') )
		$schedule_frequency = get_option('hmbkp_schedule_frequency');
	else
		$schedule_frequency = 'daily';

	// Advance by the interval. (except daily, when it will only happen if shcheduled time is in the past. )
	if( $schedule_frequency != 'daily' || $schedule_frequency == 'daily' && $scheduletime_UTC < time() ) {
		$interval =  wp_get_schedules('hmbkp_schedule_backup_hook');
		$interval = $interval[ $schedule_frequency ]['interval'];
		$scheduletime_UTC = $scheduletime_UTC + $interval;
	}

	wp_schedule_event( $scheduletime_UTC, $schedule_frequency, 'hmbkp_schedule_backup_hook' );
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

	if ( !is_dir( $to ) || !is_writable( $to ) || !is_dir( $from ) )
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
 * The maximum number of backups to keep
 * defaults to 10
 *
 * @return int
 */
function hmbkp_max_backups() {

	if ( defined( 'HMBKP_MAX_BACKUPS' ) && is_numeric( HMBKP_MAX_BACKUPS ) )
		return (int) HMBKP_MAX_BACKUPS;

	if ( get_option( 'hmbkp_max_backups' ) )
		return (int) get_option( 'hmbkp_max_backups', 10 );

	return 10;

}

/**
 * Whether to only backup files
 *
 * @return bool
 */
function hmbkp_get_files_only() {

	if ( defined( 'HMBKP_FILES_ONLY' ) && HMBKP_FILES_ONLY )
		return true;

	if ( get_option( 'hmbkp_files_only' ) )
		return true;

	return false;
}

/**
 * Whether to only backup the database
 *
 * @return bool
 */
function hmbkp_get_database_only() {

	if ( defined( 'HMBKP_DATABASE_ONLY' ) && HMBKP_DATABASE_ONLY )
		return true;

	if ( get_option( 'hmbkp_database_only' ) )
		return true;

	return false;

}

/**
 *	Returns defined email address or email address saved in options.
 *	If none set, return empty string.
 */
function hmbkp_get_email_address() {

	if ( defined( 'HMBKP_EMAIL' ) && HMBKP_EMAIL )
		$email = HMBKP_EMAIL;

	elseif ( get_option( 'hmbkp_email_address' ) )
		$email = get_option( 'hmbkp_email_address' );

	else
		return '';

	if ( is_email( $email ) )
		return $email;

	return '';

}

/**
 * Are automatic backups disabled
 *
 * @return bool
 */
function hmbkp_get_disable_automatic_backup() {

	if ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) && HMBKP_DISABLE_AUTOMATIC_BACKUP )
		return true;

	if ( get_option( 'hmbkp_disable_automatic_backup' ) )
		return true;

	return false;

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

	$hmbkp_path = hmbkp_path();

	if ( !is_dir( $hmbkp_path ) )
		return;

	if ( $handle = opendir( $hmbkp_path ) ) :

    	while ( false !== ( $file = readdir( $handle ) ) )
    		if ( ! in_array( $file, array( '.', '..', '.htaccess' ) ) && pathinfo( $file, PATHINFO_EXTENSION ) !== 'zip' )
				hmbkp_rmdirtree( trailingslashit( $hmbkp_path ) . $file );

    	closedir( $handle );

    endif;

}

function hmbkp_conform_dir( $dir ) {
	return HM_Backup::get_instance()->conform_dir( $dir );
}

function hmbkp_is_safe_mode_active() {
	return HM_Backup::get_instance()->is_safe_mode_active();
}