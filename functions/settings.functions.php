<?php

/**
 * Setup the daily backup schedule
 */
function hmbkp_setup_daily_schedule() {

	// Clear any old schedules
	wp_clear_scheduled_hook( 'hmbkp_schedule_backup_hook' );

	// Default to 11 in the evening
	$time = '23:00';

	// Allow it to be overridden
	if ( defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) && HMBKP_DAILY_SCHEDULE_TIME )
		$time = HMBKP_DAILY_SCHEDULE_TIME;

	if ( time() > strtotime( $time ) )
		$time = 'tomorrow ' . $time;

	wp_schedule_event( strtotime( $time ), 'hmbkp_daily', 'hmbkp_schedule_backup_hook' );
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
	if ( ( !$path || !is_writable( $path ) ) && $path != WP_CONTENT_DIR . '/backups' ) :
    	$path = WP_CONTENT_DIR . '/backups';
		update_option( 'hmbkp_path', $path );
	endif;

	// Create the backups directory if it doesn't exist
	if ( is_writable( WP_CONTENT_DIR ) && !is_dir( $path ) )
		mkdir( $path, 0755 );

	// Secure the directory with a .htaccess file
	$htaccess = $path . '/.htaccess';

	if ( !file_exists( $htaccess ) && is_writable( $path ) ) :
		require_once( ABSPATH . '/wp-admin/includes/misc.php' );
		insert_with_markers( $htaccess, 'BackUpWordPress', array( 'deny from all' ) );
	endif;

    return hmbkp_conform_dir( $path );
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

	return 10;

}