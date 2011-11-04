<?php

/**
 *	hmbkp_option_save function
 *
 *	Verify & save all the options set on
 *	the backupwordpress advanced options page.
 *
 *	Returns array of errors encountered when updating options.
 * 	If no errors - returns false.
 *
 *	Uses $_POST data
 */
function hmbkp_option_save() {

	if ( empty( $_POST['hmbkp_options_submit'] ) )
		return;

	check_admin_referer( 'hmbkp_options', 'hmbkp_options_nonce' );

	global $hmbkp_errors;
	$hmbkp_errors = new WP_Error;

	// Disable Automatic backups
	if ( isset( $_POST['hmbkp_automatic'] ) && ! (bool) $_POST['hmbkp_automatic'] ) {
		update_option( 'hmbkp_disable_automatic_backup', 'true' );
		wp_clear_scheduled_hook( 'hmbkp_schedule_backup_hook' );

	} else {
		delete_option( 'hmbkp_disable_automatic_backup');
	
	}

	// Update schedule frequency settings. Or reset to default of daily.
	if ( isset( $_POST['hmbkp_frequency'] ) && $_POST['hmbkp_frequency'] != 'daily' )
		update_option( 'hmbkp_schedule_frequency', esc_attr( $_POST['hmbkp_frequency'] ) );
	
	else
		delete_option( 'hmbkp_schedule_frequency' );

	// Clear schedule if settings have changed.
	if ( wp_get_schedule( 'hmbkp_schedule_backup_hook' ) != get_option( 'hmbkp_schedule_frequency' ) )
		wp_clear_scheduled_hook( 'hmbkp_schedule_backup_hook' );

	if ( isset( $_POST['hmbkp_what_to_backup'] ) && $_POST['hmbkp_what_to_backup'] == 'files only' ) {

		update_option( 'hmbkp_files_only', 'true' );
		delete_option( 'hmbkp_database_only' );

	} elseif ( isset( $_POST['hmbkp_what_to_backup'] ) && $_POST['hmbkp_what_to_backup'] == 'database only' ) {

		update_option( 'hmbkp_database_only', 'true' );
		delete_option( 'hmbkp_files_only' );

	} else {

		delete_option( 'hmbkp_database_only' );
		delete_option( 'hmbkp_files_only' );

	}

	if ( isset( $_POST['hmbkp_backup_number'] ) && $max_backups = intval( $_POST['hmbkp_backup_number'] ) ) {
		update_option( 'hmbkp_max_backups', intval( esc_attr( $_POST['hmbkp_backup_number'] ) ) );

	} else {
		delete_option( 'hmbkp_max_backups' );

		// Only error if it is actually empty.
		if ( isset( $_POST['hmbkp_backup_number'] ) )
			$hmbkp_errors->add( 'invalid_no_backups', __( 'You have entered an invalid number of backups.', 'hmbkp' ) );

	}

	if ( isset( $_POST['hmbkp_email_address'] ) && !is_email( $_POST['hmbkp_email_address'] ) && !empty( $_POST['hmbkp_email_address'] ) ) {
		$hmbkp_errors->add( 'invalid_email', __( 'You have entered an invalid email address.', 'hmbkp' ) );

	} elseif( isset( $_POST['hmbkp_email_address'] ) && !empty( $_POST['hmbkp_email_address'] ) ) {
		update_option( 'hmbkp_email_address', $_POST['hmbkp_email_address'] );

	} else {
		delete_option( 'hmbkp_email_address' );
	}

	if ( isset( $_POST['hmbkp_excludes'] ) && !empty( $_POST['hmbkp_excludes'] ) ) {

		update_option( 'hmbkp_excludes', $_POST['hmbkp_excludes'] );
		delete_transient( 'hmbkp_estimated_filesize');

	} else {

		delete_option( 'hmbkp_excludes' );
		delete_transient( 'hmbkp_estimated_filesize' );

	}

	if ( $hmbkp_errors->get_error_code() )
		return $hmbkp_errors;

	return true;

}
add_action( 'admin_init', 'hmbkp_option_save', 11 );

/**
 * Delete the backup and then redirect
 * back to the backups page
 */
function hmbkp_request_delete_backup() {

	if ( !isset( $_GET['hmbkp_delete'] ) || empty( $_GET['hmbkp_delete'] ) )
		return false;

	hmbkp_delete_backup( $_GET['hmbkp_delete'] );

	wp_redirect( remove_query_arg( 'hmbkp_delete' ), 303 );
	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_delete_backup' );

/**
 * Schedule a one time backup and then
 * redirect back to the backups page
 */
function hmbkp_request_do_backup() {

	// Are we sure
	if ( !isset( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_backup_now' || hmbkp_is_in_progress() || !hmbkp_possible() )
		return false;

	// If cron is disabled for manual backups
	if ( ( defined( 'HMBKP_DISABLE_MANUAL_BACKUP_CRON' ) && HMBKP_DISABLE_MANUAL_BACKUP_CRON ) || ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {

		hmbkp_do_backup();

	// If not fire the cron
	} else {

	// Schedule a single backup
		wp_schedule_single_event( time(), 'hmbkp_schedule_single_backup_hook' );

	// Remove the once every 60 seconds limitation
	delete_transient( 'doing_cron' );

	// Fire the cron now
	spawn_cron();

	}

	// Redirect back
	wp_redirect( remove_query_arg( 'action' ), 303 );
	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_do_backup' );

/**
 * Send the download file to the browser and
 * then redirect back to the backups page
 */
function hmbkp_request_download_backup() {

	if ( !isset( $_GET['hmbkp_download'] ) || empty( $_GET['hmbkp_download'] ) )
		return false;

	hmbkp_send_file( base64_decode( $_GET['hmbkp_download'] ) );

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_download_backup' );

/**
 * Display the running status via ajax
 *
 * @return void
 */
function hmbkp_ajax_is_backup_in_progress() {

	if ( !hmbkp_is_in_progress() )
		echo 0;

	elseif ( $status = hmbkp_get_status() )
		echo $status;

	else
		echo 1;

	exit;
}
add_action( 'wp_ajax_hmbkp_is_in_progress', 'hmbkp_ajax_is_backup_in_progress' );

/**
 * Display the calculated size via ajax
 *
 * @return void
 */
function hmbkp_ajax_calculate_backup_size() {
	echo hmbkp_calculate();
	exit;
}
add_action( 'wp_ajax_hmbkp_calculate', 'hmbkp_ajax_calculate_backup_size' );

/**
 * Test the cron response and if it's not 200 show a warning message
 *
 * @return void
 */
function hmbkp_ajax_cron_test() {

	$response = wp_remote_get( site_url( 'wp-cron.php' ) );

	if ( !is_wp_error( $response ) && $response['response']['code'] != '200' )
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%s is returning a %s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run back ups in a separate process.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>' . $response['response']['code'] . '</code>' ) . '</p></div>';
	else
		echo 1;

	exit;
}
add_action( 'wp_ajax_hmbkp_cron_test', 'hmbkp_ajax_cron_test' );

/**
 * Handles changes in the defined Constants
 * that users can define to control advanced
 * settings
 *
 * @return void
 */
function hmbkp_constant_changes() {

	// Check whether we need to disable the cron
	if ( hmbkp_get_disable_automatic_backup() && wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) )
		wp_clear_scheduled_hook( 'hmbkp_schedule_backup_hook' );

	// Or whether we need to re-enable it
	if ( !hmbkp_get_disable_automatic_backup() && !wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) )
		hmbkp_setup_schedule();

	// Allow the time of the daily backup to be changed
	if ( wp_get_schedule( 'hmbkp_schedule_backup_hook' ) != get_option( 'hmbkp_schedule_frequency' ) )
		hmbkp_setup_schedule();

	// Reset if custom time is removed
	if ( ( ( defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) && !HMBKP_DAILY_SCHEDULE_TIME ) || !defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) ) && get_option( 'hmbkp_schedule_frequency' ) == 'daily' && date( 'H:i', wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) ) != '23:00' && !hmbkp_get_disable_automatic_backup() )
		hmbkp_setup_schedule();

	// If a custom backup path has been set or changed
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && hmbkp_conform_dir( HMBKP_PATH ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) )
		hmbkp_path_move( $from, HMBKP_PATH );

	// If a custom backup path has been removed
	if ( ( ( defined( 'HMBKP_PATH' ) && !HMBKP_PATH ) || !defined( 'HMBKP_PATH' ) && hmbkp_conform_dir( hmbkp_path_default() ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) ) )
		hmbkp_path_move( $from, hmbkp_path_default() );

	// If the custom path has changed and the new directory isn't writable
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && hmbkp_conform_dir( HMBKP_PATH ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) && $from != hmbkp_path_default() && !is_writable( HMBKP_PATH ) && is_dir( $from ) )
		hmbkp_path_move( $from, hmbkp_path_default() );

}