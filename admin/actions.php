<?php

/**
 * Delete the backup and then redirect back to the backups page
 */
function hmbkp_request_delete_backup() {

	check_admin_referer( 'hmbkp_delete_backup', 'hmbkp_delete_backup_nonce' );

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $_GET['hmbkp_schedule_id'] ) ) );

	$deleted = $schedule->delete_backup( sanitize_text_field( base64_decode( $_GET['hmbkp_backup_archive'] ) ) );

	if ( is_wp_error( $deleted ) ) {
		wp_die( $deleted->get_error_message() );
	}

	wp_safe_redirect( hmbkp_get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_delete_backup', 'hmbkp_request_delete_backup' );

/**
 * Enable support and then redirect back to the backups page
 */
function hmbkp_request_enable_support() {

	check_admin_referer( 'hmbkp_enable_support', 'hmbkp_enable_support_nonce' );

	update_option( 'hmbkp_enable_support', true );

	wp_safe_redirect( hmbkp_get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_enable_support', 'hmbkp_request_enable_support' );

/**
 * Delete a schedule and all it's backups and then redirect back to the backups page
 */
function hmbkp_request_delete_schedule() {

	check_admin_referer( 'hmbkp_delete_schedule', 'hmbkp_delete_schedule_nonce' );

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $_GET['hmbkp_schedule_id'] ) ) );
	$schedule->cancel( true );

	wp_safe_redirect( hmbkp_get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_delete_schedule', 'hmbkp_request_delete_schedule' );

/**
 * Perform a manual backup
 *
 * Handles ajax requests as well as standard GET requests
 */
function hmbkp_request_do_backup() {

	if ( empty( $_GET['hmbkp_schedule_id'] ) ) {
		die;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		check_ajax_referer( 'hmbkp_run_schedule', 'hmbkp_run_schedule_nonce' );
	} else {
		check_admin_referer( 'hmbkp_run_schedule', 'hmbkp_run_schedule_nonce' );
	}

	// Fixes an issue on servers which only allow a single session per client
	session_write_close();

	// We want to display any fatal errors in this ajax request so we can catch them on the other side.
	error_reporting( E_ERROR | E_USER_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR );
	@ini_set( 'display_errors', 'On' );
	@ini_set( 'html_errors', 'Off' );

	// Force a memory error for testing purposes
	//ini_set( 'memory_limit', '2M' );
	//function a() { a(); } a();

	// Force a 500 error for testing purposes
	//header( 'HTTP/1.1 500 Internal Server Error' );

	ignore_user_abort( true );

	HMBKP_Path::get_instance()->cleanup();

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $_GET['hmbkp_schedule_id'] ) ) );

	$schedule->run();

	HMBKP_Notices::get_instance()->clear_all_notices();

	$errors = array_merge( $schedule->get_errors(), $schedule->get_warnings() );

	$error_message = '';

	foreach ( $errors as $error_set ) {
		$error_message .= implode( "\n\r", $error_set );
	}

	if ( $error_message && file_exists( $schedule->get_archive_filepath() ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$error_message .= ' HMBKP_SUCCESS';
	}

	if ( trim( $error_message ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		echo $error_message;
	}

	if ( trim( $error_message ) && defined( 'DOING_AJAX' ) ) {
		wp_die( $error_message );
	}

	if ( ! defined( 'DOING_AJAX' ) ) {
		wp_safe_redirect( hmbkp_get_settings_url(), '303' );
	}

	die;

}
add_action( 'wp_ajax_hmbkp_run_schedule', 'hmbkp_request_do_backup' );
add_action( 'admin_post_hmbkp_run_schedule', 'hmbkp_request_do_backup' );

/**
 * Send the download file to the browser and then redirect back to the backups page
 */
function hmbkp_request_download_backup() {

	check_admin_referer( 'hmbkp_download_backup', 'hmbkp_download_backup_nonce' );

	if ( ! file_exists( sanitize_text_field( base64_decode( $_GET['hmbkp_backup_archive'] ) ) )  ) {
		return;
	}

	$url = str_replace( HM_Backup::conform_dir( HM_Backup::get_home_path() ), home_url(), trailingslashit( dirname( sanitize_text_field( base64_decode( $_GET['hmbkp_backup_archive'] ) ) ) ) ) . urlencode( pathinfo( sanitize_text_field( base64_decode( $_GET['hmbkp_backup_archive'] ) ), PATHINFO_BASENAME ) );

	global $is_apache;

	if ( $is_apache ) {

		HMBKP_Path::get_instance()->protect_path( 'reset' );

		$url = add_query_arg( 'key', HMBKP_SECURE_KEY, $url );

	}

	wp_safe_redirect( $url, 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_download_backup', 'hmbkp_request_download_backup' );

/**
 * Cancels a running backup then redirect back to the backups page
 */
function hmbkp_request_cancel_backup() {

	check_admin_referer( 'hmbkp_request_cancel_backup', 'hmbkp-request_cancel_backup_nonce' );

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $_GET['hmbkp_schedule_id'] ) ) );

	// Delete the running backup
	if ( $schedule->get_running_backup_filename() && file_exists( trailingslashit( hmbkp_path() ) . $schedule->get_running_backup_filename() ) ) {
		unlink( trailingslashit( hmbkp_path() ) . $schedule->get_running_backup_filename() );
	}

	if ( $schedule->get_schedule_running_path() && file_exists( $schedule->get_schedule_running_path() ) ) {
		unlink( $schedule->get_schedule_running_path() );
	}

	HMBKP_Path::get_instance()->cleanup();

	wp_safe_redirect( hmbkp_get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_cancel_backup', 'hmbkp_request_cancel_backup' );

/**
 * Dismiss an error and then redirect back to the backups page
 */
function hmbkp_dismiss_error() {

	check_admin_referer( 'hmbkp_dismiss_error', 'hmbkp_dismiss_error_nonce' );

	HMBKP_Path::get_instance()->cleanup();

	HMBKP_Notices::get_instance()->clear_all_notices();

	wp_safe_redirect( wp_get_referer(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_dismiss_error', 'hmbkp_dismiss_error' );

/**
 * Catch the schedule service settings form submission
 *
 * Validate and either return errors or update the schedule
 */
function hmbkp_edit_schedule_services_submit() {

	check_admin_referer( 'hmbkp-edit-schedule-services', 'hmbkp-edit-schedule-services-nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		wp_die( __( 'The schedule ID was not provided. Aborting.', 'backupwordpress' ) );
	}

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_POST['hmbkp_schedule_id'] ) );

	hmbkp_clear_settings_errors();

	$errors = array();

	// Save the service options
	foreach ( HMBKP_Services::get_services( $schedule ) as $service ) {
		$errors = array_merge( $errors, $service->save() );
	}

	$schedule->save();

	if ( $errors ) {

		foreach ( $errors as $error ) {
			hmbkp_add_settings_error( $error );
		}

	}

	wp_safe_redirect( wp_get_referer(), '303' );
	die;

}
add_action( 'admin_post_hmbkp_edit_schedule_services_submit', 'hmbkp_edit_schedule_services_submit' );

/**
 * Catch the schedule settings form submission
 *
 * Validate and either return errors or update the schedule
 */
function hmbkp_edit_schedule_submit() {

	check_admin_referer( 'hmbkp-edit-schedule', 'hmbkp-edit-schedule-nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		die;
	}

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_POST['hmbkp_schedule_id'] ) );

	hmbkp_clear_settings_errors();

	$errors = array();

	$settings = array();

	if ( isset( $_POST['hmbkp_schedule_type'] ) ) {

		$schedule_type = sanitize_text_field( $_POST['hmbkp_schedule_type'] );

		if ( ! trim( $schedule_type ) ) {
			$errors['hmbkp_schedule_type'] = __( 'Backup type cannot be empty', 'backupwordpress' );
		}

		elseif ( ! in_array( $schedule_type, array( 'complete', 'file', 'database' ) ) ) {
			$errors['hmbkp_schedule_type'] = __( 'Invalid backup type', 'backupwordpress' );
		}

		else {
			$settings['type'] = $schedule_type;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_type'] ) ) {

		$schedule_recurrence_type = sanitize_text_field( $_POST['hmbkp_schedule_recurrence']['hmbkp_type'] );

		if ( empty( $schedule_recurrence_type ) ) {
			$errors['hmbkp_schedule_recurrence']['hmbkp_type'] = __( 'Schedule cannot be empty', 'backupwordpress' );
		}

		elseif ( ! in_array( $schedule_recurrence_type, array_keys( hmbkp_get_cron_schedules() ) ) && 'manually' !== $schedule_recurrence_type ) {
			$errors['hmbkp_schedule_recurrence']['hmbkp_type'] = __( 'Invalid schedule', 'backupwordpress' );
		}

		else {
			$settings['recurrence'] = $schedule_recurrence_type;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_week'] ) ) {

		$day_of_week = sanitize_text_field( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_week'] );

		if ( ! in_array( $day_of_week, array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) ) ) {
			$errors['hmbkp_schedule_start_day_of_week'] = __( 'Day of the week must be a valid lowercase day name', 'backupwordpress' );
		}

		else {
			$settings['start_time']['day_of_week'] = $day_of_week;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_month'] ) ) {

		$day_of_month = absint( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_month'] );

		$options = array(
			'min_range' => 1,
			'max_range' => 31,
		);

		if ( false === filter_var( $day_of_month, FILTER_VALIDATE_INT, array( 'options' => $options ) ) ) {
			$errors['hmbkp_schedule_start_day_of_month'] = __( 'Day of month must be between 1 and 31', 'backupwordpress' );
		}

		else {
			$settings['start_time']['day_of_month'] = $day_of_month;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_hours'] ) ) {

		$hours = absint( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_hours'] );

		$options = array(
			'min_range' => 0,
			'max_range' => 23
		);

		if ( false === filter_var( $hours, FILTER_VALIDATE_INT, array( 'options' => $options ) ) ) {
			$errors['hmbkp_schedule_start_hours'] = __( 'Hours must be between 0 and 23', 'backupwordpress' );
		}

		else {
			$settings['start_time']['hours'] = $hours;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_minutes'] ) ) {

		$minutes = absint( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_minutes'] );

		$options = array(
			'min_range' => 0,
			'max_range' => 59,
		);

		if ( false === filter_var( $minutes, FILTER_VALIDATE_INT, array( 'options' => $options ) ) ) {
			$errors['hmbkp_schedule_start_minutes'] = __( 'Minutes must be between 0 and 59', 'backupwordpress' );
		}

		else {
			$settings['start_time']['minutes'] = $minutes;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_max_backups'] ) ) {

		$max_backups = sanitize_text_field( $_POST['hmbkp_schedule_max_backups'] );

		if ( empty( $max_backups ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups can\'t be empty', 'backupwordpress' );
		}

		elseif ( ! is_numeric( $max_backups ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be a number', 'backupwordpress' );
		}

		elseif ( ! ( $max_backups >= 1 ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be greater than 0', 'backupwordpress' );
		}

		else {
			$settings['max_backups'] = absint( $max_backups );
		}

	}

	// Save the service options
	foreach ( HMBKP_Services::get_services( $schedule ) as $service ) {
		$errors = array_merge( $errors, $service->save() );
	}

	if ( ! empty( $settings['recurrence'] ) && ! empty( $settings['start_time'] ) ) {

		// Calculate the start time depending on the recurrence
		$start_time = hmbkp_determine_start_time( $settings['recurrence'], $settings['start_time'] );

		if ( $start_time ) {
			$schedule->set_schedule_start_time( $start_time );
		}

	}

	if ( ! empty( $settings['recurrence'] ) ) {
		$schedule->set_reoccurrence( $settings['recurrence'] );
	}

	if ( ! empty( $settings['type'] ) ) {
		$schedule->set_type( $settings['type'] );
	}

	if ( ! empty( $settings['max_backups'] ) ) {
		$schedule->set_max_backups( $settings['max_backups'] );
	}

	// Save the new settings
	$schedule->save();

	// Remove any old backups in-case max backups was reduced
	$schedule->delete_old_backups();

	if ( $errors ) {

		foreach ( $errors as $error ) {
			hmbkp_add_settings_error( $error );
		}

	}

	$redirect = remove_query_arg( array( 'hmbkp_panel', 'action' ), wp_get_referer() );

	if ( $errors ) {
		$redirect = wp_get_referer();
	}

	wp_safe_redirect( $redirect, '303' );
	die;

}
add_action( 'admin_post_hmbkp_edit_schedule_submit', 'hmbkp_edit_schedule_submit' );

/**
 * Add an exclude rule
 *
 * @access public
 * @return void
 */
function hmbkp_add_exclude_rule() {

	check_admin_referer( 'hmbkp-add-exclude-rule', 'hmbkp-add-exclude-rule-nonce' );

	if ( ! isset( $_GET['hmbkp_exclude_pathname'] ) ) {
		return;
	}

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );

	$exclude_rule = str_ireplace( $schedule->get_root(), '', sanitize_text_field( $_GET['hmbkp_exclude_pathname'] ) );

	$schedule->set_excludes( $exclude_rule, true );

	$schedule->save();

	wp_safe_redirect( wp_get_referer(), '303' );

	die;

}
add_action( 'admin_post_hmbkp_add_exclude_rule', 'hmbkp_add_exclude_rule' );

/**
 * Delete an exclude rule
 *
 * @access public
 * @return void
 */
function hmbkp_remove_exclude_rule() {

	check_admin_referer( 'hmbkp_remove_exclude_rule', 'hmbkp-remove_exclude_rule_nonce' );

	if ( ! isset( $_GET['hmbkp_remove_exclude'] ) ) {
		die;
	}

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );

	$excludes = $schedule->get_excludes();

	$schedule->set_excludes( array_diff( $excludes, (array) stripslashes( sanitize_text_field( $_GET['hmbkp_remove_exclude'] ) ) ) );

	$schedule->save();

	wp_safe_redirect( wp_get_referer(), '303' );

	die;

}
add_action( 'admin_post_hmbkp_remove_exclude_rule', 'hmbkp_remove_exclude_rule' );

/**
 *
 * @param null $pathname
 */
function hmbkp_recalculate_directory_filesize() {

	if ( ! isset( $_GET['hmbkp_recalculate_directory_filesize'] ) || ! check_admin_referer( 'hmbkp-recalculate_directory_filesize' ) ) {
		return;
	}

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );

	$directory = sanitize_text_field( $_GET['hmbkp_recalculate_directory_filesize'] );

	// Delete the cached directory size
	delete_transient( 'hmbkp_directory_filesizes' );

	$url = add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_excludes' ), hmbkp_get_settings_url() );

	if ( isset( $_GET['hmbkp_directory_browse'] ) ) {
		$url = add_query_arg( 'hmbkp_directory_browse', sanitize_text_field( $_GET['hmbkp_directory_browse'] ), $url );
	}

	wp_safe_redirect( $url, '303' );
	die;

}
add_action( 'load-' . HMBKP_ADMIN_PAGE, 'hmbkp_recalculate_directory_filesize' );

function hmbkp_calculate_site_size() {

	if ( isset( $_GET['hmbkp_schedule_id'] ) ) {

		$current_schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );

	} else {

		// Refresh the schedules from the database to make sure we have the latest changes
		HMBKP_Schedules::get_instance()->refresh_schedules();

		$schedules = HMBKP_Schedules::get_instance()->get_schedules();

		$current_schedule = reset( $schedules );

	}

	if ( ! $current_schedule->is_site_size_cached() ) {
		// Start calculating
		$root = new SplFileInfo( $current_schedule->get_root() );
		$size = $current_schedule->filesize( $root );
	}

}
add_action( 'load-' . HMBKP_ADMIN_PAGE, 'hmbkp_calculate_site_size' );

/**
 * Receive the heartbeat and return backup status
 */
function hmbkp_heartbeat_received( $response, $data ) {

	$response['heartbeat_interval'] = 'fast';

	if ( ! empty( $data['hmbkp_schedule_id'] ) ) {

		$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $data['hmbkp_schedule_id'] ) ) );

		if ( ! empty( $data['hmbkp_is_in_progress'] ) ) {

			if ( ! $schedule->get_status() ) {
				$response['hmbkp_schedule_status'] = 0;

				// Slow the heartbeat back down
				$response['heartbeat_interval'] = 'slow';

			} else {
				$response['hmbkp_schedule_status'] = hmbkp_schedule_status( $schedule, false );
			}

		}

		if ( ! empty( $data['hmbkp_client_request'] ) ) {

			// Pass the site size to be displayed when it's ready.
			if ( $schedule->is_site_size_cached() ) {

				$response['hmbkp_site_size'] = $schedule->get_formatted_site_size();

				ob_start();
				require( HMBKP_PLUGIN_PATH . 'admin/schedule-form-excludes.php' );
				$response['hmbkp_dir_sizes'] = ob_get_clean();

				// Slow the heartbeat back down
				$response['heartbeat_interval'] = 'slow';
			}
		}

	}
	return $response;

}
add_filter( 'heartbeat_received', 'hmbkp_heartbeat_received', 10, 2 );

// TODO needs work
function hmbkp_display_error_and_offer_to_email_it() {

	check_ajax_referer( 'hmbkp_nonce', 'nonce' );

	if ( empty( $_POST['hmbkp_error'] ) ) {
		die;
	}

	$errors = explode( "\n", wp_strip_all_tags( stripslashes( $_POST['hmbkp_error'] ) ) );

	HMBKP_Notices::get_instance()->set_notices( 'backup_errors', $errors );

	wp_send_json_success( wp_get_referer() );

}
add_action( 'wp_ajax_hmbkp_backup_error', 'hmbkp_display_error_and_offer_to_email_it' );

// TODO needs work
function hmbkp_send_error_via_email() {

	check_ajax_referer( 'hmbkp_nonce', 'nonce' );

	if ( empty( $_POST['hmbkp_error'] ) ) {
		die;
	}

	$error = wp_strip_all_tags( $_POST['hmbkp_error'] );

	wp_mail( 'support@humanmade.co.uk', 'BackUpWordPress Fatal error on ' . parse_url( home_url(), PHP_URL_HOST ), $error, 'From: BackUpWordPress <' . get_bloginfo( 'admin_email' ) . '>' . "\r\n" );

	die;

}
add_action( 'wp_ajax_hmbkp_email_error', 'hmbkp_send_error_via_email' );

/**
 * Load the enable support modal contents
 *
 * @return void
 */
function hmbkp_load_enable_support() {

	check_ajax_referer( 'hmbkp_nonce', '_wpnonce' );

	require_once HMBKP_PLUGIN_PATH . 'admin/enable-support.php';

	die;

}
add_action( 'wp_ajax_load_enable_support', 'hmbkp_load_enable_support' );

/**
 * Display the running status via ajax
 */
function hmbkp_ajax_is_backup_in_progress() {

	check_ajax_referer( 'hmbkp_nonce', 'nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		die;
	}

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $_POST['hmbkp_schedule_id'] ) ) );

	if ( ! $schedule->get_status() ) {
		echo 0;
	} else {
		hmbkp_schedule_status( $schedule );
	}

	die;

}
add_action( 'wp_ajax_hmbkp_is_in_progress', 'hmbkp_ajax_is_backup_in_progress' );

/**
 * Display the calculated size via ajax
 */
function hmbkp_ajax_calculate_backup_size() {

	check_ajax_referer( 'hmbkp_nonce', 'nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		die;
	}

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $_POST['hmbkp_schedule_id'] ) ) );

	$recalculate_filesize = true;

	require( HMBKP_PLUGIN_PATH . 'admin/schedule-sentence.php' );

	die;

}
add_action( 'wp_ajax_hmbkp_calculate', 'hmbkp_ajax_calculate_backup_size' );

/**
 * Test the cron response and if it's not 200 show a warning message
 */
function hmbkp_ajax_cron_test() {

	check_ajax_referer( 'hmbkp_nonce', 'nonce' );

	// Only run the test once per week
	if ( get_transient( 'hmbkp_wp_cron_test_beacon' ) ) {

		echo 1;

		die;

	}

	// Skip the test if they are using Alternate Cron
	if ( defined( 'ALTERNATE_WP_CRON' ) ) {

		delete_option( 'hmbkp_wp_cron_test_failed' );

		echo 1;

		die;

	}

	$url = site_url( 'wp-cron.php' );

	// Attempt to load wp-cron.php 3 times, if we get the same error each time then inform the user.
	$response1 = wp_remote_head( $url, array( 'timeout' => 30 ) );
	$response2 = wp_remote_head( $url, array( 'timeout' => 30 ) );
	$response3 = wp_remote_head( $url, array( 'timeout' => 30 ) );

	if ( is_wp_error( $response1 ) && is_wp_error( $response2 ) && is_wp_error( $response3 ) ) {

		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'backupwordpress' ) . '</strong> ' . sprintf( __( '%1$s is returning a %2$s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled backups. See the %3$s for more details.', 'backupwordpress' ), '<code>wp-cron.php</code>', '<code>' . $response1->get_error_message() . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

		update_option( 'hmbkp_wp_cron_test_failed', true );

	} elseif ( ! in_array( 200, array_map( 'wp_remote_retrieve_response_code', array( $response1, $response2, $response3 ) ) ) ) {

		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'backupwordpress' ) . '</strong> ' . sprintf( __( '%1$s is returning a %2$s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled backups. See the %3$s for more details.', 'backupwordpress' ), '<code>wp-cron.php</code>', '<code>' . esc_html( wp_remote_retrieve_response_code( $response1 ) ) . ' ' . esc_html( get_status_header_desc( wp_remote_retrieve_response_code( $response1 ) ) ) . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

		update_option( 'hmbkp_wp_cron_test_failed', true );

	} else {

		echo 1;

		delete_option( 'hmbkp_wp_cron_test_failed' );
		set_transient( 'hmbkp_wp_cron_test_beacon', 1, WEEK_IN_SECONDS );

	}

	die;

}
add_action( 'wp_ajax_hmbkp_cron_test', 'hmbkp_ajax_cron_test' );
