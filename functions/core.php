<?php

/**
 * Setup the plugin defaults on activation
 */
function hmbkp_activate() {

	// loads the translation files
	load_plugin_textdomain( 'hmbkp', false, HMBKP_PLUGIN_LANG_DIR );

	// Run deactivate on activation in-case it was deactivated manually
	hmbkp_deactivate();

}

/**
 * Cleanup on plugin deactivation
 *
 * Removes options and clears all cron schedules
 */
function hmbkp_deactivate() {

	// Clean up the backups directory
	hmbkp_cleanup();

	// Remove the plugin data cache
	delete_transient( 'hmbkp_plugin_data' );

	$schedules = HMBKP_Schedules::get_instance();

	// Clear schedule crons
	foreach ( $schedules->get_schedules() as $schedule ) {
		$schedule->unschedule();
	}

	// Opt them out of support
	delete_option( 'hmbkp_enable_support' );

}

/**
 * Handles anything that needs to be
 * done when the plugin is updated
 */
function hmbkp_update() {

	// Update from backUpWordPress 0.4.5
	if ( get_option( 'bkpwp_max_backups' ) ) {

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

		foreach ( $legacy_options as $option ) {
			delete_option( $option );
		}

		global $wp_roles;

		$wp_roles->remove_cap( 'administrator', 'manage_backups' );
		$wp_roles->remove_cap( 'administrator', 'download_backups' );

		wp_clear_scheduled_hook( 'bkpwp_schedule_bkpwp_hook' );

	}

	// Version 1 to 2
	if ( get_option( 'hmbkp_plugin_version' ) && version_compare( '2.0', get_option( 'hmbkp_plugin_version' ), '>' ) ) {

		/**
		 * Setup a backwards compatible schedule
		 */
		$legacy_schedule = new HMBKP_Scheduled_Backup( 'backup' );

		// Backup type
		if ( ( defined( 'HMBKP_FILES_ONLY' ) && HMBKP_FILES_ONLY ) || get_option( 'hmbkp_files_only' ) )
			$legacy_schedule->set_type( 'file' );

		elseif ( ( defined( 'HMBKP_DATABASE_ONLY' ) && HMBKP_DATABASE_ONLY ) || get_option( 'hmbkp_database_only' ) )
			$legacy_schedule->set_type( 'database' );

		else
			$legacy_schedule->set_type( 'complete' );

		// Daily schedule time
		if ( defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) && HMBKP_DAILY_SCHEDULE_TIME )
			$legacy_schedule->set_schedule_start_time( strtotime( HMBKP_DAILY_SCHEDULE_TIME ) );

		// Backup schedule
		$legacy_schedule->set_reoccurrence( get_option( 'hmbkp_schedule_frequency', 'hmbkp_daily' ) );

		// Automatic backups disabled?
		if ( ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) && HMBKP_DISABLE_AUTOMATIC_BACKUP ) || get_option( 'hmbkp_disable_automatic_backup' ) )
			$legacy_schedule->set_reoccurrence( 'manually' );

		// Max backups
		if ( defined( 'HMBKP_MAX_BACKUPS' ) && is_numeric( HMBKP_MAX_BACKUPS ) )
			$legacy_schedule->set_max_backups( (int) HMBKP_MAX_BACKUPS );

		else
			$legacy_schedule->set_max_backups( (int) get_option( 'hmbkp_max_backups', 10 ) );

		// Excludes
		if ( get_option( 'hmbkp_excludes' ) )
			$legacy_schedule->set_excludes( get_option( 'hmbkp_excludes' ) );

		// Backup email
		if ( defined( 'HMBKP_EMAIL' ) && is_email( HMBKP_EMAIL ) )
			$legacy_schedule->set_service_options( 'HMBKP_Email_Service', array( 'email' => HMBKP_EMAIL ) );

		elseif ( is_email( get_option( 'hmbkp_email_address' ) ) )
			$legacy_schedule->set_service_options( 'HMBKP_Email_Service', array( 'email' => get_option( 'hmbkp_email_address' ) ) );

		// Set the archive filename to what it used to be
		$legacy_schedule->set_archive_filename( implode( '-', array( get_bloginfo( 'name' ), 'backup', date( 'Y-m-d-H-i-s', current_time( 'timestamp' ) ) ) ) . '.zip' );

		$legacy_schedule->save();

		// Remove the legacy options
		foreach ( array( 'hmbkp_database_only', 'hmbkp_files_only', 'hmbkp_max_backups', 'hmbkp_email_address', 'hmbkp_email', 'hmbkp_schedule_frequency', 'hmbkp_disable_automatic_backup' ) as $option_name ) {
			delete_option( $option_name );
		}

	}

	// Update from 2.2.4
	if ( get_option( 'hmbkp_plugin_version' ) && version_compare( '2.2.5', get_option( 'hmbkp_plugin_version' ), '>' ) ) {

		$schedules = HMBKP_Schedules::get_instance();

		// Loop through all schedules and re-set the reccurrence to include hmbkp_
		foreach ( $schedules->get_schedules() as $schedule ) {

			$reoccurrence = $schedule->get_reoccurrence();

			if ( $reoccurrence !== 'manually' && strpos( $reoccurrence, 'hmbkp_' ) === false )
				$schedule->set_reoccurrence( 'hmbkp_' . $schedule->get_reoccurrence() );

			$schedule->save();

		}

	}

	// Every update
	if ( get_option( 'hmbkp_plugin_version' ) && version_compare( HMBKP_VERSION, get_option( 'hmbkp_plugin_version' ), '>' ) ) {

		hmbkp_deactivate();

		// re-calcuate the backups directory and move to it.
		if ( ! defined( 'HMBKP_PATH' ) ) {

			$old_path = hmbkp_path();

			delete_option( 'hmbkp_path' );
			delete_option( 'hmbkp_default_path' );

			hmbkp_path_move( $old_path, hmbkp_path() );

		}

		// Force .htaccess to be re-written
		if ( file_exists( hmbkp_path() . '/.htaccess' ) )
			unlink( hmbkp_path() . '/.htaccess' );

		// Force index.html to be re-written
		if ( file_exists( hmbkp_path() . '/index.html' ) )
			unlink( hmbkp_path() . '/index.html' );

	}

	// Update the stored version
	if ( get_option( 'hmbkp_plugin_version' ) !== HMBKP_VERSION )
		update_option( 'hmbkp_plugin_version', HMBKP_VERSION );

}

/**
 * Setup the default backup schedules
 */
function hmbkp_setup_default_schedules() {

	$schedules = HMBKP_Schedules::get_instance();

	if ( $schedules->get_schedules() )
		return;

	/**
	 * Schedule a database backup daily and store backups
	 * for the last 2 weeks
	 */
	$database_daily = new HMBKP_Scheduled_Backup( 'default-1' );
	$database_daily->set_type( 'database' );
	$database_daily->set_schedule_start_time( hmbkp_determine_start_time( 'hmbkp_daily', array( 'hours' => '23', 'minutes' => '0' ) ) );
	$database_daily->set_reoccurrence( 'hmbkp_daily' );
	$database_daily->set_max_backups( 14 );
	$database_daily->save();

	/**
	 * Schedule a complete backup to run weekly and store backups for
	 * the last 3 months
	 */
	$complete_weekly = new HMBKP_Scheduled_Backup( 'default-2' );
	$complete_weekly->set_type( 'complete' );
	$complete_weekly->set_schedule_start_time( hmbkp_determine_start_time( 'hmbkp_weekly', array( 'day_of_week' => 'sunday', 'hours' => '3', 'minutes' => '0' ) ) );
	$complete_weekly->set_reoccurrence( 'hmbkp_weekly' );
	$complete_weekly->set_max_backups( 12 );
	$complete_weekly->save();

	$schedules->refresh_schedules();

	function hmbkp_default_schedules_setup_warning() {
		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has setup your default schedules.', 'hmbkp' ) . '</strong> ' . __( 'By default BackUpWordPress performs a daily backup of your database and a weekly backup of your database &amp; files. You can modify these schedules.', 'hmbkp' ) . '</p></div>';
	}

	add_action( 'admin_notices', 'hmbkp_default_schedules_setup_warning' );

}

add_action( 'admin_init', 'hmbkp_setup_default_schedules' );

/**
 * Return an array of cron schedules
 *
 * @param $schedules
 * @return array $reccurrences
 */
function hmbkp_cron_schedules( $schedules ) {

	$schedules['hmbkp_hourly']      = array( 'interval' => HOUR_IN_SECONDS, 'display' => __( 'Once Hourly', 'hmbkp' ) );
	$schedules['hmbkp_twicedaily']  = array( 'interval' => 12 * HOUR_IN_SECONDS, 'display' => __( 'Twice Daily', 'hmbkp' ) );
	$schedules['hmbkp_daily']       = array( 'interval' => DAY_IN_SECONDS, 'display' => __( 'Once Daily', 'hmbkp' ) );
	$schedules['hmbkp_weekly']      = array( 'interval' => WEEK_IN_SECONDS, 'display' => __( 'Once Weekly', 'hmbkp' ) );
	$schedules['hmbkp_fortnightly'] = array( 'interval' => 2 * WEEK_IN_SECONDS, 'display' => __( 'Once Fortnightly', 'hmbkp' ) );
	$schedules['hmbkp_monthly']     = array( 'interval' => 30 * DAY_IN_SECONDS, 'display' => __( 'Once Monthly', 'hmbkp' ) );

	return $schedules;
}

add_filter( 'cron_schedules', 'hmbkp_cron_schedules' );

/**
 * Recursively delete a directory including
 * all the files and sub-directories.
 *
 * @param string $dir
 * @return bool
 * @return bool|WP_Error
 */
function hmbkp_rmdirtree( $dir ) {

	if ( strpos( HM_Backup::get_home_path(), $dir ) !== false )
		return new WP_Error( 'hmbkp_invalid_action_error', sprintf( __( 'You can only delete directories inside your WordPress installation', 'hmbkp' ) ) );

	if ( is_file( $dir ) )
		@unlink( $dir );

	if ( ! is_dir( $dir ) || ! is_readable( $dir ) )
		return false;

	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ), RecursiveIteratorIterator::CHILD_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );

	foreach ( $files as $file ) {

		if ( $file->isDir() )
			@rmdir( $file->getPathname() );

		else
			@unlink( $file->getPathname() );

	}

	@rmdir( $dir );

	return true;
}

/**
 * Get the path to the backups directory
 *
 * Will try to create it if it doesn't exist
 * and will fallback to default if a custom dir
 * isn't writable.
 */
function hmbkp_path() {

	global $is_apache;

	$path = untrailingslashit( get_option( 'hmbkp_path' ) );

	// Allow the backups path to be defined
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
		$path = untrailingslashit( HMBKP_PATH );

	// If the dir doesn't exist or isn't writable then use the default path instead instead
	if ( ( ! $path || ( is_dir( $path ) && ! wp_is_writable( $path ) ) || ( ! is_dir( $path ) && ! wp_is_writable( dirname( $path ) ) ) ) && $path !== hmbkp_path_default() )
		$path = hmbkp_path_default();

	// Create the backups directory if it doesn't exist
	if ( ! is_dir( $path ) && is_writable( dirname( $path ) ) )
		wp_mkdir_p( $path );

	// If the path has changed then cache it
	if ( get_option( 'hmbkp_path' ) !== $path )
		update_option( 'hmbkp_path', $path );

	// Protect against directory browsing by including a index.html file
	$index = $path . '/index.html';

	if ( ! file_exists( $index ) && wp_is_writable( $path ) )
		file_put_contents( $index, '' );

	$htaccess = $path . '/.htaccess';

	// Protect the directory with a .htaccess file on Apache servers
	if ( $is_apache && function_exists( 'insert_with_markers' ) && ! file_exists( $htaccess ) && wp_is_writable( $path ) ) {

		$contents[] = '# ' . sprintf( __( 'This %s file ensures that other people cannot download your backup files.', 'hmbkp' ), '.htaccess' );
		$contents[] = '';
		$contents[] = '<IfModule mod_rewrite.c>';
		$contents[] = 'RewriteEngine On';
		$contents[] = 'RewriteCond %{QUERY_STRING} !key=' . HMBKP_SECURE_KEY;
		$contents[] = 'RewriteRule (.*) - [F]';
		$contents[] = '</IfModule>';
		$contents[] = '';

		insert_with_markers( $htaccess, 'BackUpWordPress', $contents );

	}

	return HM_Backup::conform_dir( $path );

}

/**
 * Return the default backup path
 *
 * @return string path
 */
function hmbkp_path_default() {

	$path = untrailingslashit( get_option( 'hmbkp_default_path' ) );

	$content_dir = HM_Backup::conform_dir( trailingslashit( WP_CONTENT_DIR ) );

	$pos = strpos( $path, $content_dir );

	// no path set or current path doesn't match the database value
	if ( empty( $path ) || ( false === $pos ) || ( 0 !== $pos ) ) {

		$path = HM_Backup::conform_dir( trailingslashit( WP_CONTENT_DIR ) . 'backupwordpress-' . substr( HMBKP_SECURE_KEY, 0, 10 ) . '-backups' );

		update_option( 'hmbkp_default_path', $path );

	}

	$upload_dir = wp_upload_dir();

	// If the backups dir can't be created in WP_CONTENT_DIR then fallback to uploads
	if ( ( ( ! is_dir( $path ) && ! wp_is_writable( dirname( $path ) ) ) || ( is_dir( $path ) && ! wp_is_writable( $path ) ) ) && strpos( $path, $upload_dir['basedir'] ) === false ) {

		hmbkp_path_move( $path, $path = HM_Backup::conform_dir( trailingslashit( $upload_dir['basedir'] ) . 'backupwordpress-' . substr( HMBKP_SECURE_KEY, 0, 10 ) . '-backups' ) );

		update_option( 'hmbkp_default_path', $path );

	}

	return $path;

}

/**
 * Move the backup directory and all existing backup files to a new
 * location
 *
 * @param string $from path to move the backups dir from
 * @param string $to   path to move the backups dir to
 * @return void
 */
function hmbkp_path_move( $from, $to ) {

	if ( ! trim( untrailingslashit( trim( $from ) ) ) || ! trim( untrailingslashit( trim( $to ) ) ) )
		return;

	// Create the new directory if it doesn't exist
	if ( is_writable( dirname( $to ) ) && ! is_dir( $to ) )
		wp_mkdir_p( $to );

	// Bail if we couldn't
	if ( ! is_dir( $to ) || ! wp_is_writable( $to ) )
		return false;

	update_option( 'hmbkp_path', $to );

	// Bail if the old directory doesn't exist
	if ( ! is_dir( $from ) )
		return false;

	// Cleanup before we start moving things
	hmbkp_cleanup();

	// Move any existing backups
	if ( $handle = opendir( $from ) ) {

		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'zip' )
				if ( ! @rename( trailingslashit( $from ) . $file, trailingslashit( $to ) . $file ) )
					copy( trailingslashit( $from ) . $file, trailingslashit( $to ) . $file );
		}

		closedir( $handle );

	}

	// Only delete the old directory if it's inside WP_CONTENT_DIR
	if ( strpos( $from, WP_CONTENT_DIR ) !== false )
		hmbkp_rmdirtree( $from );

}

/**
 * Check if a backup is possible with regards to file
 * permissions etc.
 *
 * @return bool
 */
function hmbkp_possible() {

	if ( ! wp_is_writable( hmbkp_path() ) || ! is_dir( hmbkp_path() ) )
		return false;

	$test_backup = new HMBKP_Scheduled_Backup( 'test_backup' );

	if ( ! is_readable( $test_backup->get_root() ) )
		return false;

	return true;
}

/**
 * Remove any non backup.zip files from the backups dir.
 *
 * @return void
 */
function hmbkp_cleanup() {

	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
		return;

	$hmbkp_path = hmbkp_path();

	if ( ! is_dir( $hmbkp_path ) )
		return;

	if ( $handle = opendir( $hmbkp_path ) ) {

		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( ! in_array( $file, array( '.', '..', 'index.html' ) ) && pathinfo( $file, PATHINFO_EXTENSION ) !== 'zip' && strpos( $file, '-running' ) === false )
				hmbkp_rmdirtree( trailingslashit( $hmbkp_path ) . $file );
		}

		closedir( $handle );

	}

}

/**
 * Handles changes in the defined Constants
 * that users can define to control advanced
 * settings
 */
function hmbkp_constant_changes() {

	// If a custom backup path has been set or changed
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && HM_Backup::conform_dir( HMBKP_PATH ) !== ( $from = HM_Backup::conform_dir( get_option( 'hmbkp_path' ) ) ) )
		hmbkp_path_move( $from, HMBKP_PATH );

	// If a custom backup path has been removed
	if ( ( ( defined( 'HMBKP_PATH' ) && ! HMBKP_PATH ) || ! defined( 'HMBKP_PATH' ) && hmbkp_path_default() !== ( $from = HM_Backup::conform_dir( get_option( 'hmbkp_path' ) ) ) ) )
		hmbkp_path_move( $from, hmbkp_path_default() );

	// If the custom path has changed and the new directory isn't writable
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && ! wp_is_writable( HMBKP_PATH ) && get_option( 'hmbkp_path' ) === HMBKP_PATH && is_dir( HMBKP_PATH ) )
		hmbkp_path_move( HMBKP_PATH, hmbkp_path_default() );

}

/**
 * Get the max email attachment filesize
 *
 * Can be overridden by defining HMBKP_ATTACHMENT_MAX_FILESIZE
 *
 * return int the filesize
 */
function hmbkp_get_max_attachment_size() {

	$max_size = '10mb';

	if ( defined( 'HMBKP_ATTACHMENT_MAX_FILESIZE' ) && wp_convert_hr_to_bytes( HMBKP_ATTACHMENT_MAX_FILESIZE ) )
		$max_size = HMBKP_ATTACHMENT_MAX_FILESIZE;

	return wp_convert_hr_to_bytes( $max_size );

}

function hmbkp_is_path_accessible( $dir ) {

	if ( strpos( $dir, HM_Backup::get_home_path() ) === false ) {
		// path is inaccessible
		return false;
	}

	return true;
}

/**
 * List of schedules
 *
 * @return array
 */
function hmbkp_get_cron_schedules() {

	$schedules = wp_get_schedules();

	// remove any schedule whose key is not prefixed with 'hmbkp_'
	foreach ( $schedules as $key => $arr ) {
		if ( ! preg_match( '/^hmbkp_/', $key ) )
			unset( $schedules[$key] );
	}

	return $schedules;
}

/**
 * @param string $type the type of the schedule
 * @param array $times {
 *     An array of time arguments. Optional.
 *
 *     @type int $minutes          The minute to start the schedule on. Defaults to current time + 10 minutes. Accepts
 *                                 any valid `date( 'i' )` output.
 *     @type int $hours            The hour to start the schedule on. Defaults to current time + 10 minutes. Accepts
 *                                 any valid `date( 'G' )` output.
 *     @type string $day_of_week   The day of the week to start the schedule on. Defaults to current time + 10 minutes. Accepts
 *                                 any valid `date( 'l' )` output.
 *     @type int $day_of_month     The day of the month to start the schedule on. Defaults to current time + 10 minutes. Accepts
 *                                 any valid `date( 'j' )` output.
 *     @type int $now              The current time. Defaults to `time()`. Accepts any valid timestamp.
 *
 * }
 * @return int $timestamp Returns the resulting timestamp on success and Int 0 on failure
 */
function hmbkp_determine_start_time( $type, $times = array() ) {

	// Default to in 10 minutes
	if ( ! empty( $times['now'] ) ) {
		$default_timestamp = $times['now'] + 600;

	} else {
		$default_timestamp = time() + 600;
	}

	$default_times = array(
		'minutes'      => date( 'i', $default_timestamp ),
		'hours'        => date( 'G', $default_timestamp ),
		'day_of_week'  => date( 'l', $default_timestamp ),
		'day_of_month' => date( 'j', $default_timestamp ),
		'now'          => time()
	);

	$args = wp_parse_args( $times, $default_times );

	$schedule_start = '';

	$intervals = HMBKP_Scheduled_Backup::get_cron_schedules();

	// Allow the hours and minutes to be overwritten by a constant
	if ( defined( 'HMBKP_SCHEDULE_TIME' ) && HMBKP_SCHEDULE_TIME ) {
		$hm = HMBKP_SCHEDULE_TIME;
	}

	// The hour and minute that the schedule should start on
	else {
		$hm = $args['hours'] . ':' . $args['minutes'] . ':00';
	}

	switch ( $type ) {

		case 'hmbkp_hourly' :
		case 'hmbkp_daily' :
		case 'hmbkp_twicedaily':

			// The next occurance of the specified time
			$schedule_start = $hm;
			break;

		case 'hmbkp_weekly' :
		case 'hmbkp_fortnightly' :

			// The next day of the week at the specified time
			$schedule_start = $args['day_of_week'] . ' ' . $hm;
			break;

		case 'hmbkp_monthly' :

			// The occurance of the time on the specified day of the month
			$schedule_start = date( 'F', $args['now'] ) . ' ' . $args['day_of_month'] . ' ' . $hm;

			// If we've already gone past that day this month then we'll need to start next month
			if ( strtotime( $schedule_start, $args['now'] ) <= $args['now'] )
				$schedule_start = date( 'F', strtotime( '+ 1 month', $args['now'] ) )  . ' ' . $args['day_of_month'] . ' ' . $hm;

			// If that's still in the past then we'll need to jump to next year
			if ( strtotime( $schedule_start, $args['now'] ) <= $args['now'] )
				$schedule_start = date( 'F', strtotime( '+ 1 month', $args['now'] ) )  . ' ' . $args['day_of_month'] . ' ' . date( 'Y', strtotime( '+ 1 year', $args['now'] ) ) . ' ' . $hm;

			break;
		default :

			return 0;

			break;

	}

	$timestamp = strtotime( $schedule_start, $args['now'] );

	// Convert to UTC
	$timestamp -= get_option( 'gmt_offset' ) * 3600;

	// If the scheduled time already passed then keep adding the interval until we get to a future date
	while ( $timestamp <= $args['now'] ) {
		$timestamp += $intervals[ $type ]['interval'];
	}

	return $timestamp;

}