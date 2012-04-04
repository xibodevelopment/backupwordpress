<?php

/**
 * Delete the backup and then redirect
 * back to the backups page
 */
function hmbkp_request_delete_backup() {

	if ( ! isset( $_GET['hmbkp_delete'] ) || empty( $_GET['hmbkp_delete'] ) )
		return;

	$schedule = new HMBKP_Scheduled_Backup( urldecode( $_GET['hmbkp_schedule'] ) );
	$schedule->delete_backup( base64_decode( urldecode( $_GET['hmbkp_delete'] ) ) );

	wp_redirect( remove_query_arg( 'hmbkp_delete' ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_delete_backup' );

/**
 * Perform a manual backup via ajax
 */
function hmbkp_ajax_request_do_backup() {

	ignore_user_abort( true );

	hmbkp_do_backup();

	exit;

}
add_action( 'wp_ajax_hmbkp_backup', 'hmbkp_ajax_request_do_backup' );

/**
 * Send the download file to the browser and
 * then redirect back to the backups page
 */
function hmbkp_request_download_backup() {

	if ( empty( $_GET['hmbkp_download'] ) )
		return;

	// Force the .htaccess to be rebuilt
	if ( file_exists( hmbkp_path() . '/.htaccess' ) )
		unlink( hmbkp_path() . '/.htaccess' );

	hmbkp_path();

	wp_redirect( add_query_arg( 'key', md5( HMBKP_SECURE_KEY ), str_replace( hmbkp_conform_dir( ABSPATH ), site_url(), base64_decode( $_GET['hmbkp_download'] ) ) ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_download_backup' );

function hmbkp_request_cancel_backup() {

	if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_cancel' )
		return;

	hmbkp_cleanup();

	wp_redirect( remove_query_arg( 'action' ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_cancel_backup' );

function hmbkp_dismiss_error() {

	if ( empty( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_dismiss_error' )
		return;

	hmbkp_cleanup();

	wp_redirect( remove_query_arg( 'action' ), 303 );

	exit;

}
add_action( 'admin_init', 'hmbkp_dismiss_error' );

/**
 * Display the running status via ajax
 *
 * @return void
 */
function hmbkp_ajax_is_backup_in_progress() {

	if ( ! hmbkp_in_progress() )
		echo 0;

	else
		include( HMBKP_PLUGIN_PATH . '/admin.backup-button.php' );

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

	if ( ! is_wp_error( $response ) && $response['response']['code'] != '200' )
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%s is returning a %s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled back ups. See the %s for more details.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>' . $response['response']['code'] . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

	else
		echo 1;

	exit;

}
add_action( 'wp_ajax_hmbkp_cron_test', 'hmbkp_ajax_cron_test' );

function hmbkp_edit_schedule_load() {

	$schedule = new HMBKP_Scheduled_Backup( esc_attr( $_GET['hmbkp_schedule'] ) );
	
	require( HMBKP_PLUGIN_PATH . '/admin.schedule-form.php' );
	
	exit;

}
add_action( 'wp_ajax_hmbkp_edit_schedule_load', 'hmbkp_edit_schedule_load' );


function hmnkp_edit_schedule_submit() {

//	$schedule = new HMBKP_Scheduled_Backup( esc_attr( $_GET['hmbkp_schedule'] ) );
	
	print_r( $_REQUEST );
	
	exit;

}
add_action( 'wp_ajax_hmnkp_edit_schedule_submit', 'hmnkp_edit_schedule_submit' );



/**
 * Handles changes in the defined Constants
 * that users can define to control advanced
 * settings
 *
 * @return null
 */
function hmbkp_constant_changes() {

	// If a custom backup path has been set or changed
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && hmbkp_conform_dir( HMBKP_PATH ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) )
		hmbkp_path_move( $from, HMBKP_PATH );

	// If a custom backup path has been removed
	if ( ( ( defined( 'HMBKP_PATH' ) && ! HMBKP_PATH ) || ! defined( 'HMBKP_PATH' ) && hmbkp_conform_dir( hmbkp_path_default() ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) ) )
		hmbkp_path_move( $from, hmbkp_path_default() );

	// If the custom path has changed and the new directory isn't writable
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && hmbkp_conform_dir( HMBKP_PATH ) != ( $from = hmbkp_conform_dir( get_option( 'hmbkp_path' ) ) ) && $from != hmbkp_path_default() && !is_writable( HMBKP_PATH ) && is_dir( $from ) )
		hmbkp_path_move( $from, hmbkp_path_default() );

}