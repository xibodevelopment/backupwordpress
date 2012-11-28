<?php

/**
 * Setup the plugin defaults on activation
 */
function hmbkp_activate() {

	hmbkp_deactivate();

}

/**
 * Cleanup on plugin deactivation
 *
 * Removes options and clears all cron schedules
 */
function hmbkp_deactivate() {

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

	// Clear hmbkp crons
	foreach( get_option( 'cron' ) as $cron )
		foreach( (array) $cron as $key => $value )
			if ( strpos( $key, 'hmbkp' ) !== false )
				wp_clear_scheduled_hook( $key );

	hmbkp_cleanup();

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

		foreach ( $legacy_options as $option )
			delete_option( $option );

	    global $wp_roles;

		$wp_roles->remove_cap( 'administrator','manage_backups' );
		$wp_roles->remove_cap( 'administrator','download_backups' );

		wp_clear_scheduled_hook( 'bkpwp_schedule_bkpwp_hook' );

	}

	// Version 1 to 2
	if ( get_option( 'hmbkp_plugin_version' ) && version_compare( '2.0' , get_option( 'hmbkp_plugin_version' ), '>' ) ) {

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
		$legacy_schedule->set_reoccurrence( str_replace( 'hmbkp_', '', get_option( 'hmbkp_schedule_frequency', 'daily' ) ) );

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
		foreach ( array( 'hmbkp_database_only', 'hmbkp_files_only', 'hmbkp_max_backups', 'hmbkp_email_address', 'hmbkp_email', 'hmbkp_schedule_frequency', 'hmbkp_disable_automatic_backup' ) as $option_name )
			delete_option( $option_name );


	}

	// Every update
	if ( get_option( 'hmbkp_plugin_version' ) && version_compare( HMBKP_VERSION, get_option( 'hmbkp_plugin_version' ), '>' ) ) {

		hmbkp_deactivate();

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

	$schedules = new HMBKP_Schedules;

	if ( $schedules->get_schedules() )
		return;

	/**
	 * Schedule a database backup daily and store backups
	 * for the last 2 weeks
	 */
	$database_daily = new HMBKP_Scheduled_Backup( 'default-1' );
	$database_daily->set_type( 'database' );
	$database_daily->set_reoccurrence( 'daily' );
	$database_daily->set_max_backups( 14 );
	$database_daily->save();

	/**
	 * Schedule a complete backup to run weekly and store backups for
	 * the last 3 months
	 */
	$complete_weekly = new HMBKP_Scheduled_Backup( 'default-2' );
	$complete_weekly->set_type( 'complete' );
	$complete_weekly->set_reoccurrence( 'weekly' );
	$complete_weekly->set_max_backups( 12 );
	$complete_weekly->save();

	function hmbkp_default_schedules_setup_warning() {
		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has setup your default schedules.', 'hmbkp' ) . '</strong> ' . __( 'By default BackUpWordPress performs a daily backup of your database and a weekly backup of your database &amp; files. You can modify these schedules.', 'hmbkp' ) . '</p></div>';
	}
	add_action( 'admin_notices', 'hmbkp_default_schedules_setup_warning' );

}
add_action( 'admin_init', 'hmbkp_setup_default_schedules' );

/**
 * Add weekly, fortnightly and monthly as a cron schedule choices
 *
 * @param array $reccurrences
 * @return array $reccurrences
 */
function hmbkp_more_reccurences( $reccurrences ) {

	return array_merge( $reccurrences, array(
	    'weekly' 		=> array( 'interval' => 604800, 'display' => 'Once Weekly' ),
	    'fortnightly'	=> array( 'interval' => 1209600, 'display' => 'Once Fortnightly' ),
	    'monthly'		=> array( 'interval' => 2629743.83 , 'display' => 'Once Monthly' )
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
		@unlink( $dir );

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
 * Get the path to the backups directory
 *
 * Will try to create it if it doesn't exist
 * and will fallback to default if a custom dir
 * isn't writable.
 */
function hmbkp_path() {

	global $is_apache;

	$path = get_option( 'hmbkp_path' );

	// Allow the backups path to be defined
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH )
		$path = HMBKP_PATH;

	// If the dir doesn't exist or isn't writable then use the default path instead instead
	if ( ( ! $path || ( is_dir( $path ) && ! is_writable( $path ) ) || ( ! is_dir( $path ) && ! is_writable( dirname( $path ) ) ) ) && get_option( 'hmbkp_path' ) !== get_option( 'hmbkp_default_path' ) )
    	$path = hmbkp_path_default();

	// Create the backups directory if it doesn't exist
	if ( ! is_dir( $path ) && is_writable( dirname( $path ) ) )
		mkdir( $path, 0755 );

	// If the path has changed then cache it
	if ( get_option( 'hmbkp_path' ) !== $path )
		update_option( 'hmbkp_path', $path );

	// Protect against directory browsing by including a index.html file
	$index = $path . '/index.html';

	if ( ! file_exists( $index ) && is_writable( $path ) )
		file_put_contents( $index, '' );

	$htaccess = $path . '/.htaccess';

	// Protect the directory with a .htaccess file on Apache servers
	if ( $is_apache && function_exists( 'insert_with_markers' ) && ! file_exists( $htaccess ) && is_writable( $path ) ) {

		$contents[]	= '# ' . sprintf( __( 'This %s file ensures that other people cannot download your backup files.', 'hmbkp' ), '.htaccess' );
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

	$path = get_option( 'hmbkp_default_path' );

	if ( empty( $path ) ) {

		$path = HM_Backup::conform_dir( trailingslashit( WP_CONTENT_DIR ) . substr( md5( time() ), 0, 10 ) . '-backups' );

		update_option( 'hmbkp_default_path', $path );

	}

	$upload_dir = wp_upload_dir();

	// If the backups dir can't be created in WP_CONTENT_DIR then fallback to uploads
	if ( ( ( ! is_dir( $path ) && ! is_writable( dirname( $path ) ) ) || ( is_dir( $path ) && ! is_writable( $path ) ) ) && strpos( $path, $upload_dir['basedir'] ) === false ) {

		hmbkp_path_move( $path, $path = HM_Backup::conform_dir( trailingslashit( $upload_dir['basedir'] ) . substr( md5( time() ), 0, 10 ) . '-backups' ) );

		update_option( 'hmbkp_default_path', $path );

	}

	return $path;
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

	if ( ! untrailingslashit( $from ) || ! untrailingslashit( $to ) )
		return;

	// Create the custom backups directory if it doesn't exist
	if ( is_writable( dirname( $to ) ) && ! is_dir( $to ) )
	    mkdir( $to, 0755 );

	if ( ! is_dir( $to ) || ! is_writable( $to ) )
	    return false;

	update_option( 'hmbkp_path', $to );

	hmbkp_cleanup();

	if ( ! is_dir( $from ) )
		return false;

	if ( $handle = opendir( $from ) ) :

	    while ( false !== ( $file = readdir( $handle ) ) )
	    	if ( $file !== '.' && $file !== '..' )
	    		if ( ! @rename( trailingslashit( $from ) . $file, trailingslashit( $to ) . $file ) )
	    			copy( trailingslashit( $from ) . $file, trailingslashit( $to ) . $file );

	    closedir( $handle );

	endif;

	hmbkp_rmdirtree( $from );

}

/**
 * Check if a backup is possible with regards to file
 * permissions etc.
 *
 * @return bool
 */
function hmbkp_possible() {

	if ( ! is_writable( hmbkp_path() ) || ! is_dir( hmbkp_path() ) )
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

	if ( ! is_dir( $hmbkp_path ) )
		return;

	if ( $handle = opendir( $hmbkp_path ) ) :

    	while ( false !== ( $file = readdir( $handle ) ) )
    		if ( ! in_array( $file, array( '.', '..', 'index.html' ) ) && pathinfo( $file, PATHINFO_EXTENSION ) !== 'zip' )
				hmbkp_rmdirtree( trailingslashit( $hmbkp_path ) . $file );

    	closedir( $handle );

    endif;

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
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && ! is_writable( HMBKP_PATH ) && get_option( 'hmbkp_path' ) === HMBKP_PATH && is_dir( HMBKP_PATH ) )
		hmbkp_path_move( HMBKP_PATH, hmbkp_path_default() );

}