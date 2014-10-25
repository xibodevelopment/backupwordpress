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
		check_admin_referer( 'hmbkp-run-schedule', 'hmbkp-run-schedule' );
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

	hmbkp_cleanup();

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $_GET['hmbkp_schedule_id'] ) ) );

	$schedule->run();

	$errors = array_merge( $schedule->get_errors(), $schedule->get_warnings() );

	$error_message = '';

	foreach ( $errors as $error_set ) {
		$error_message .= implode( "\n\r", $error_set );
	}

	if ( $error_message && file_exists( $schedule->get_archive_filepath() ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$error_message .= 'HMBKP_SUCCESS';
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
add_action( 'admin_post_hmbkp_request_do_backup', 'hmbkp_request_do_backup' );

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

		// Force the .htaccess to be rebuilt
		if ( file_exists( hmbkp_path() . '/.htaccess' ) )
			unlink( hmbkp_path() . '/.htaccess' );

		hmbkp_path();

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

	hmbkp_cleanup();

	wp_safe_redirect( hmbkp_get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_cancel_backup', 'hmbkp_request_cancel_backup' );

/**
 * Dismiss an error and then redirect back to the backups page
 */
function hmbkp_dismiss_error() {

	// TODO Should really be nonced

	if ( empty( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_dismiss_error' ) {
		return;
	}

	hmbkp_cleanup();

	wp_safe_redirect( hmbkp_get_settings_url(), 303 );

	die;

}
add_action( 'admin_init', 'hmbkp_dismiss_error' );

/**
 * Catch the schedule service settings form submission
 *
 * Validate and either return errors or update the schedule
 */
function hmbkp_edit_schedule_services_submit() {

	check_admin_referer( 'hmbkp-edit-schedule-services', 'hmbkp-edit-schedule-services-nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		wp_die( __( 'The schedule ID was not provided. Aborting.', 'hmbkp' ) );
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
			$errors['hmbkp_schedule_type'] = __( 'Backup type cannot be empty', 'hmbkp' );
		}

		elseif ( ! in_array( $schedule_type, array( 'complete', 'file', 'database' ) ) ) {
			$errors['hmbkp_schedule_type'] = __( 'Invalid backup type', 'hmbkp' );
		}

		else {
			$settings['type'] = $schedule_type;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_type'] ) ) {

		$schedule_recurrence_type = sanitize_text_field( $_POST['hmbkp_schedule_recurrence']['hmbkp_type'] );

		if ( empty( $schedule_recurrence_type ) ) {
			$errors['hmbkp_schedule_recurrence']['hmbkp_type'] = __( 'Schedule cannot be empty', 'hmbkp' );
		}

		elseif ( ! in_array( $schedule_recurrence_type, array_keys( hmbkp_get_cron_schedules() ) ) && $schedule_recurrence_type !== 'manually' ) {
			$errors['hmbkp_schedule_recurrence']['hmbkp_type'] = __( 'Invalid schedule', 'hmbkp' );
		}

		else {
			$settings['recurrence'] = $schedule_recurrence_type;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_week'] ) ) {

		$day_of_week = sanitize_text_field( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_week'] );

		if ( ! in_array( $day_of_week, array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) ) ) {
			$errors['hmbkp_schedule_start_day_of_week'] = __( 'Day of the week must be a valid lowercase day name', 'hmbkp' );
		}

		else {
			$settings['start_time']['day_of_week'] = $day_of_week;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_month'] ) ) {

		$day_of_month = absint( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_month'] );

		$options = array(
			'min_range' => 1,
			'max_range' => 31
		);

		if ( false === filter_var( $day_of_month, FILTER_VALIDATE_INT, array( 'options' => $options ) ) ) {
			$errors['hmbkp_schedule_start_day_of_month'] = __( 'Day of month must be between 1 and 31', 'hmbkp' );
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
			$errors['hmbkp_schedule_start_hours'] = __( 'Hours must be between 0 and 23', 'hmbkp' );
		}

		else {
			$settings['start_time']['hours'] = $hours;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_minutes'] ) ) {

		$minutes = absint( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_minutes'] );

		$options = array(
			'min_range' => 0,
			'max_range' => 59
		);

		if ( false === filter_var( $minutes, FILTER_VALIDATE_INT, array( 'options' => $options ) ) ) {
			$errors['hmbkp_schedule_start_minutes'] = __( 'Minutes must be between 0 and 59', 'hmbkp' );
		}

		else {
			$settings['start_time']['minutes'] = $minutes;
		}

	}

	if ( isset( $_POST['hmbkp_schedule_max_backups'] ) ) {

		$max_backups = sanitize_text_field( $_POST['hmbkp_schedule_max_backups'] );

		if ( empty( $max_backups ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups can\'t be empty', 'hmbkp' );
		}

		elseif ( ! is_numeric( $max_backups ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be a number', 'hmbkp' );
		}

		elseif ( ! ( $max_backups >= 1 ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be greater than 0', 'hmbkp' );
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

/**
 * Receive the heartbeat and return backup status
 */
function hmbkp_heartbeat_received( $response, $data ) {

	if ( ! empty( $data['hmbkp_is_in_progress'] ) ) {

		$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( urldecode( $data['hmbkp_is_in_progress'] ) ) );

		if ( ! $schedule->get_status() ) {
			$response['hmbkp_schedule_status'] = 0;

		} else {
			$response['hmbkp_schedule_status'] = hmbkp_schedule_status( $schedule, false );

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

	$error = wp_strip_all_tags( stripslashes( $_POST['hmbkp_error'] ) );

	$error = str_replace( 'HMBKP_SUCCESS', '', $error, $succeeded );

	if ( $succeeded ) { ?>

		<h3><?php _e( 'Your backup completed but with the following errors / warnings, it\'s probably ok to ignore these.', 'hmbkp' ); ?></h3>

	<?php } else { ?>

		<h3><?php _e( 'Your backup failed', 'hmbkp' ); ?></h3>

	<?php } ?>

	<p><?php _e( 'Here\'s the response from the server:', 'hmbkp' ); ?></p>

	<pre><?php esc_html_e( $error ); ?></pre>

	<p class="description"><?php printf( __( 'You can email details of this error to %s so they can look into the issue.', 'hmbkp' ), '<a href="http://hmn.md">Human Made Limited</a>' ); ?><br /><br /></p>

	<button class="button hmbkp-colorbox-close"><?php _e( 'Close', 'hmbkp' ); ?></button>
	<button class="button-primary hmbkp_send_error_via_email right"><?php _e( 'Email to Support', 'hmbkp' ); ?></button>

	<?php die;

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

	if ( defined( 'ALTERNATE_WP_CRON' ) ) {

		delete_option( 'hmbkp_wp_cron_test_failed' );

		echo 1;

		die;

	}

	$response = wp_remote_head( site_url( 'wp-cron.php' ), array( 'timeout' => 30 ) );

	if ( is_wp_error( $response ) ) {

		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%1$s is returning a %2$s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled backups. See the %3$s for more details.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>' . $response->get_error_message() . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

		update_option( 'hmbkp_wp_cron_test_failed', true );

	} elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {

		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%1$s is returning a %2$s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled backups. See the %3$s for more details.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>' . esc_html( wp_remote_retrieve_response_code( $response ) ) . ' ' . esc_html( get_status_header_desc( wp_remote_retrieve_response_code( $response ) ) ) . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

		update_option( 'hmbkp_wp_cron_test_failed', true );

	} else {

		echo 1;

		delete_option( 'hmbkp_wp_cron_test_failed' );

	}

	die;

}
add_action( 'wp_ajax_hmbkp_cron_test', 'hmbkp_ajax_cron_test' );
