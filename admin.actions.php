<?php

/**
 * Delete the backup and then redirect
 * back to the backups page
 */
function hmbkp_request_delete_backup() {

	if ( !isset( $_GET['hmbkp_delete'] ) || empty( $_GET['hmbkp_delete'] ) )
		return false;

	hmbkp_delete_backup( $_GET['hmbkp_delete'] );

	wp_redirect( remove_query_arg( 'hmbkp_delete' ) );
	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_delete_backup' );

/**
 * Schedule a one time backup and then
 * redirect back to the backups page
 */
function hmbkp_request_do_backup() {

	// Are we sure
	if ( !isset( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_backup_now' || hmbkp_is_in_progress() || !is_writable( hmbkp_path() ) || !is_dir( hmbkp_path() ) || ini_get( 'safe_mode' ) )
		return false;

	// Schedule a single backup
	wp_schedule_single_event( time(), 'hmbkp_schedule_single_backup_hook' );
	
	// Remove the once every 60 seconds limitation
	delete_transient( 'doing_cron' );
	
	// Fire the cron now
	spawn_cron();

	// Redirect back
	wp_redirect( remove_query_arg( 'action' ) );
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

function hmbkp_ajax_is_backup_in_progress() {

	if ( !hmbkp_is_in_progress() )
		echo 0;

	elseif ( get_option( 'hmbkp_status' ) )
		echo get_option( 'hmbkp_status' );

	else
		echo 1;

	exit;
}
add_action( 'wp_ajax_hmbkp_is_in_progress', 'hmbkp_ajax_is_backup_in_progress' );

function hmbkp_ajax_calculate_backup_size() {
	echo hmbkp_calculate();
	exit;
}
add_action( 'wp_ajax_hmbkp_calculate', 'hmbkp_ajax_calculate_backup_size' );

function hmbkp_ajax_cron_test() {
	
	$response = wp_remote_get( site_url( 'wp-cron.php' ) );
	
	if ( !is_wp_error( $response ) && $response['response']['code'] != '200' )
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%s is returning a %s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run back ups in a separate process.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>' . $response['response']['code'] . '</code>' ) . '</p></div>';
	else
		echo 1;
	
	exit;
}
add_action( 'wp_ajax_hmbkp_cron_test', 'hmbkp_ajax_cron_test' );