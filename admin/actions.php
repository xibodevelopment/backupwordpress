<?php

namespace HM\BackUpWordPress;

/**
 * Delete the backup and then redirect back to the backups page
 */
function request_delete_backup() {

	check_admin_referer( 'hmbkp_delete_backup', 'hmbkp_delete_backup_nonce' );

	$schedule = new Scheduled_Backup( sanitize_text_field( urldecode( $_GET['hmbkp_schedule_id'] ) ) );

	$deleted = $schedule->delete_backup( sanitize_text_field( base64_decode( $_GET['hmbkp_backup_archive'] ) ) );

	if ( is_wp_error( $deleted ) ) {
		wp_die( $deleted->get_error_message() );
	}

	wp_safe_redirect( get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_delete_backup', 'HM\BackUpWordPress\request_delete_backup' );

/**
 * Enable support and then redirect back to the backups page
 */
function request_enable_support() {

	check_admin_referer( 'hmbkp_enable_support', 'hmbkp_enable_support_nonce' );

	update_option( 'hmbkp_enable_support', true );

	wp_safe_redirect( get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_enable_support', 'HM\BackUpWordPress\request_enable_support' );

/**
 * Delete a schedule and all it's backups and then redirect back to the backups page
 */
function request_delete_schedule() {

	check_admin_referer( 'hmbkp_delete_schedule', 'hmbkp_delete_schedule_nonce' );

	$schedule = new Scheduled_Backup( sanitize_text_field( urldecode( $_GET['hmbkp_schedule_id'] ) ) );
	$schedule->cancel( true );

	wp_safe_redirect( get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_delete_schedule', 'HM\BackUpWordPress\request_delete_schedule' );

add_action( 'admin_post_hmbkp_request_credentials', function() {

	global $wp_filesystem;

	ob_start();
	$creds = request_filesystem_credentials( '' );
	ob_end_clean();

	// Default to showing an error if we're not able to connect.
	$url = add_query_arg( 'connection_error', 1, get_settings_url() );

	/**
	 * If we have valid filesystem credentials then let's attempt
	 * to use them to create the backups directory. If we can't create it in
	 * WP_CONTENT_DIR then we fallback to trying in uploads.
	 */
	if ( WP_Filesystem( $creds ) ) {

		// If we're able to connect then no need to redirect with an error.
		$url = get_settings_url();

		// If the backup path exists then let's just try to chmod it to the correct permissions.
		if (
			is_dir( Path::get_instance()->get_default_path() ) &&
			! $wp_filesystem->chmod( Path::get_instance()->get_default_path(), FS_CHMOD_DIR )
		) {
			$url = add_query_arg( 'creation_error', 1, get_settings_url() );
		} else {

			// If the path doesn't exist then try to correct the permission for the parent directory and create it.
			$wp_filesystem->chmod( dirname( Path::get_instance()->get_default_path() ), FS_CHMOD_DIR );

			if (
				! $wp_filesystem->mkdir( Path::get_instance()->get_default_path(), FS_CHMOD_DIR ) &&
				! $wp_filesystem->mkdir( Path::get_instance()->get_fallback_path(), FS_CHMOD_DIR )
			) {
				$url = add_query_arg( 'creation_error', 1, get_settings_url() );
			}
		}
	}

	wp_safe_redirect( $url , 303 );
	die;

} );

/**
 * Perform a manual backup
 *
 * Handles ajax requests as well as standard GET requests
 */
function request_do_backup() {

	if ( empty( $_REQUEST['hmbkp_schedule_id'] ) ) {
		die;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		check_ajax_referer( 'hmbkp_run_schedule', 'hmbkp_run_schedule_nonce' );
	} else {
		check_admin_referer( 'hmbkp_run_schedule', 'hmbkp_run_schedule_nonce' );
	}

	Path::get_instance()->cleanup();

	// Fixes an issue on servers which only allow a single session per client
	session_write_close();

	$schedule_id = sanitize_text_field( urldecode( $_REQUEST['hmbkp_schedule_id'] ) );
	$task = new \HM\Backdrop\Task( '\HM\BackUpWordPress\run_schedule_async', $schedule_id );

	/**
	 * Backdrop doesn't cleanup tasks which fatal before they can finish
	 * so we manually cancel the task if it's already scheduled.
	 */
	if ( $task->is_scheduled() ) {
		$task->cancel();
	}
	$task->schedule();

	die;

}
add_action( 'wp_ajax_hmbkp_run_schedule', 'HM\BackUpWordPress\request_do_backup' );

function run_schedule_async( $schedule_id ) {
	$schedule = new Scheduled_Backup( $schedule_id );
	$schedule->run();
}

/**
 * Send the download file to the browser and then redirect back to the backups page
 */
function request_download_backup() {

	check_admin_referer( 'hmbkp_download_backup', 'hmbkp_download_backup_nonce' );

	if ( ! file_exists( sanitize_text_field( base64_decode( $_GET['hmbkp_backup_archive'] ) ) )  ) {
		return;
	}

	$url = str_replace( wp_normalize_path( Path::get_home_path() ), home_url( '/' ), trailingslashit( dirname( sanitize_text_field( base64_decode( $_GET['hmbkp_backup_archive'] ) ) ) ) ) . urlencode( pathinfo( sanitize_text_field( base64_decode( $_GET['hmbkp_backup_archive'] ) ), PATHINFO_BASENAME ) );

	global $is_apache;

	if ( $is_apache ) {

		Path::get_instance()->protect_path( 'reset' );

		$url = add_query_arg( 'key', HMBKP_SECURE_KEY, $url );

	}

	wp_safe_redirect( $url, 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_download_backup', 'HM\BackUpWordPress\request_download_backup' );

/**
 * Cancels a running backup then redirect back to the backups page
 */
function request_cancel_backup() {

	check_admin_referer( 'hmbkp_request_cancel_backup', 'hmbkp-request_cancel_backup_nonce' );

	$schedule = new Scheduled_Backup( sanitize_text_field( urldecode( $_GET['hmbkp_schedule_id'] ) ) );
	$status = $schedule->get_status();

	// Delete the running backup
	if ( $status->get_backup_filename() && file_exists( trailingslashit( Path::get_path() ) . $status->get_backup_filename() ) ) {
		unlink( trailingslashit( Path::get_path() ) . $status->get_backup_filename() );
	}

	if ( file_exists( $status->get_status_filepath() ) ) {
		unlink( $status->get_status_filepath() );
	}

	Path::get_instance()->cleanup();

	wp_safe_redirect( get_settings_url(), 303 );

	die;

}
add_action( 'admin_post_hmbkp_request_cancel_backup', 'HM\BackUpWordPress\request_cancel_backup' );

/**
 * Dismiss an error and then redirect back to the backups page
 */
function dismiss_error() {

	Path::get_instance()->cleanup();

	Notices::get_instance()->clear_all_notices();

	wp_safe_redirect( wp_get_referer(), 303 );

	die;

}
add_action( 'wp_ajax_hmbkp_dismiss_error', 'HM\BackUpWordPress\dismiss_error' );

/**
 * Catch the schedule service settings form submission
 *
 * Validate and either return errors or update the schedule
 */
function edit_schedule_services_submit() {

	check_admin_referer( 'hmbkp-edit-schedule-services', 'hmbkp-edit-schedule-services-nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		wp_die( __( 'The schedule ID was not provided. Aborting.', 'backupwordpress' ) );
	}

	$schedule = new Scheduled_Backup( sanitize_text_field( $_POST['hmbkp_schedule_id'] ) );

	$errors = array();

	// Save the service options
	foreach ( Services::get_services( $schedule ) as $service ) {
		$errors = array_merge( $errors, $service->save() );
	}

	$schedule->save();

	if ( ! empty( $errors ) ) {
		foreach ( $errors as $error ) {
			add_settings_error( $error );
		}
	}

	$redirect = remove_query_arg( array( 'hmbkp_panel', 'action' ), wp_get_referer() );

	if ( ! empty( $errors ) ) {
		$redirect = wp_get_referer();
	}

	wp_safe_redirect( $redirect, '303' );
	die;

}
add_action( 'admin_post_hmbkp_edit_schedule_services_submit', 'HM\BackUpWordPress\edit_schedule_services_submit' );

/**
 * Catch the schedule settings form submission
 *
 * Validate and either return errors or update the schedule
 */
function edit_schedule_submit() {

	check_admin_referer( 'hmbkp-edit-schedule', 'hmbkp-edit-schedule-nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		die;
	}

	$schedule = new Scheduled_Backup( sanitize_text_field( $_POST['hmbkp_schedule_id'] ) );
	$site_size = new Site_Size( $schedule->get_type(), $schedule->get_excludes() );

	$errors = $settings = array();

	if ( isset( $_POST['hmbkp_schedule_type'] ) ) {

		$schedule_type = sanitize_text_field( $_POST['hmbkp_schedule_type'] );

		if ( ! trim( $schedule_type ) ) {
			$errors['hmbkp_schedule_type'] = __( 'Backup type cannot be empty', 'backupwordpress' );
		} elseif ( ! in_array( $schedule_type, array( 'complete', 'file', 'database' ) ) ) {
			$errors['hmbkp_schedule_type'] = __( 'Invalid backup type', 'backupwordpress' );
		} else {
			$settings['type'] = $schedule_type;
		}
	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_type'] ) ) {

		$schedule_recurrence_type = sanitize_text_field( $_POST['hmbkp_schedule_recurrence']['hmbkp_type'] );

		if ( empty( $schedule_recurrence_type ) ) {
			$errors['hmbkp_schedule_recurrence']['hmbkp_type'] = __( 'Schedule cannot be empty', 'backupwordpress' );
		} elseif ( ! in_array( $schedule_recurrence_type, array_keys( cron_schedules() ) ) && 'manually' !== $schedule_recurrence_type ) {
			$errors['hmbkp_schedule_recurrence']['hmbkp_type'] = __( 'Invalid schedule', 'backupwordpress' );
		} else {
			$settings['recurrence'] = $schedule_recurrence_type;
		}
	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_week'] ) ) {

		$day_of_week = sanitize_text_field( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_week'] );

		if ( ! in_array( $day_of_week, array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) ) ) {
			$errors['hmbkp_schedule_start_day_of_week'] = __( 'Day of the week must be a valid, lowercase day name', 'backupwordpress' );
		} else {
			$settings['start_time']['day_of_week'] = $day_of_week;
		}
	}

	if ( ( 'monthly' === $schedule_recurrence_type ) && isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_month'] ) ) {

		$day_of_month = absint( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_day_of_month'] );

		$options = array(
			'min_range' => 1,
			'max_range' => 31,
		);

		if ( false === filter_var( $day_of_month, FILTER_VALIDATE_INT, array( 'options' => $options ) ) ) {
			$errors['hmbkp_schedule_start_day_of_month'] = __( 'Day of month must be between 1 and 31', 'backupwordpress' );
		} else {
			$settings['start_time']['day_of_month'] = $day_of_month;
		}
	}

	if ( isset( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_hours'] ) ) {

		$hours = absint( $_POST['hmbkp_schedule_recurrence']['hmbkp_schedule_start_hours'] );

		$options = array(
			'min_range' => 0,
			'max_range' => 23,
		);

		if ( false === filter_var( $hours, FILTER_VALIDATE_INT, array( 'options' => $options ) ) ) {
			$errors['hmbkp_schedule_start_hours'] = __( 'Hours must be between 0 and 23', 'backupwordpress' );
		} else {
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
		} else {
			$settings['start_time']['minutes'] = $minutes;
		}
	}

	if ( isset( $_POST['hmbkp_schedule_max_backups'] ) ) {

		$max_backups = sanitize_text_field( $_POST['hmbkp_schedule_max_backups'] );

		if ( empty( $max_backups ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups can\'t be empty', 'backupwordpress' );
		} elseif ( ! is_numeric( $max_backups ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be a number', 'backupwordpress' );
		} elseif ( ! ( $max_backups >= 1 ) ) {
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be greater than 0', 'backupwordpress' );
		} elseif ( $site_size->is_site_size_cached() && disk_space_low( $site_size->get_site_size() * $max_backups ) ) {
			$errors['hmbkp_schedule_max_backups'] = sprintf( __( 'Storing %s backups would use %s of disk space but your server only has %s free.', 'backupwordpress' ), '<code>' . number_format_i18n( $max_backups ) . '</code>', '<code>' . size_format( $max_backups * $site_size->get_site_size() ) . '</code>', '<code>' . size_format( disk_free_space( Path::get_path() ) ) . '</code>' );
		} else {
			$settings['max_backups'] = absint( $max_backups );
		}
	}

	// Save the service options
	foreach ( Services::get_services( $schedule ) as $service ) {
		$errors = array_merge( $errors, $service->save() );
	}

	if ( ! empty( $settings['recurrence'] ) && ! empty( $settings['start_time'] ) ) {

		// Calculate the start time depending on the recurrence
		$start_time = determine_start_time( $settings['recurrence'], $settings['start_time'] );

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

	if ( ! empty( $errors ) ) {
		foreach ( $errors as $error ) {
			add_settings_error( $error );
		}
	}

	$redirect = remove_query_arg( array( 'hmbkp_panel', 'action' ), wp_get_referer() );

	if ( ! empty( $errors ) ) {
		$redirect = wp_get_referer();
	}

	wp_safe_redirect( $redirect, '303' );
	die;

}
add_action( 'admin_post_hmbkp_edit_schedule_submit', 'HM\BackUpWordPress\edit_schedule_submit' );

/**
 * Add an exclude rule
 *
 * @access public
 * @return void
 */
function add_exclude_rule() {

	check_admin_referer( 'hmbkp-add-exclude-rule', 'hmbkp-add-exclude-rule-nonce' );

	if ( ! isset( $_GET['hmbkp_exclude_pathname'] ) ) {
		return;
	}

	$schedule = new Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );

	$exclude_rule = sanitize_text_field( $_GET['hmbkp_exclude_pathname'] );

	$schedule->set_excludes( $exclude_rule, true );

	$schedule->save();
	delete_transient( 'hmbkp_root_size' );

	wp_safe_redirect( wp_get_referer(), '303' );

	die;

}
add_action( 'admin_post_hmbkp_add_exclude_rule', 'HM\BackUpWordPress\add_exclude_rule' );

/**
 * Delete an exclude rule
 *
 * @access public
 * @return void
 */
function remove_exclude_rule() {

	check_admin_referer( 'hmbkp_remove_exclude_rule', 'hmbkp-remove_exclude_rule_nonce' );

	if ( ! isset( $_GET['hmbkp_remove_exclude'] ) ) {
		die;
	}

	$schedule = new Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );

	$excludes = $schedule->get_excludes();
	$exclude_rule_to_remove = stripslashes( sanitize_text_field( $_GET['hmbkp_remove_exclude'] ) );

	$schedule->set_excludes( array_diff( $excludes->get_user_excludes(), (array) $exclude_rule_to_remove ) );

	$schedule->save();
	delete_transient( 'hmbkp_root_size' );

	wp_safe_redirect( wp_get_referer(), '303' );

	die;

}
add_action( 'admin_post_hmbkp_remove_exclude_rule', 'HM\BackUpWordPress\remove_exclude_rule' );

/**
 *
 * @param null
 */
function recalculate_directory_filesize() {

	if ( ! isset( $_GET['hmbkp_recalculate_directory_filesize'] ) || ! check_admin_referer( 'hmbkp-recalculate_directory_filesize' ) ) {
		return;
	}

	// Delete the cached directory size
	@unlink( trailingslashit( Path::get_path() ) . '.files' );

	$url = add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_excludes' ), get_settings_url() );

	if ( isset( $_GET['hmbkp_directory_browse'] ) ) {
		$url = add_query_arg( 'hmbkp_directory_browse', sanitize_text_field( $_GET['hmbkp_directory_browse'] ), $url );
	}

	wp_safe_redirect( $url, '303' );
	die;

}
add_action( 'load-' . HMBKP_ADMIN_PAGE, 'HM\BackUpWordPress\recalculate_directory_filesize' );

function calculate_site_size() {

	$site_size = new Site_Size;

	if ( ! $site_size->is_site_size_cached() ) {
		$root = new \SplFileInfo( Path::get_root() );
		$site_size->filesize( $root );
	}

}
add_action( 'load-' . HMBKP_ADMIN_PAGE, 'HM\BackUpWordPress\calculate_site_size' );

/**
 * Receive the heartbeat and return backup status
 */
function heartbeat_received( $response, $data ) {

	$response['heartbeat_interval'] = 'fast';

	if ( ! empty( $data['hmbkp_schedule_id'] ) ) {

		$schedule = new Scheduled_Backup( sanitize_text_field( urldecode( $data['hmbkp_schedule_id'] ) ) );
		$status = new Backup_Status( $schedule->get_id() );

		if ( ! empty( $data['hmbkp_is_in_progress'] ) ) {

			if ( ! $status->get_status() ) {
				$response['hmbkp_schedule_status'] = 0;

				// Slow the heartbeat back down
				$response['heartbeat_interval'] = 'slow';

			} else {
				$response['hmbkp_schedule_status'] = schedule_status( $schedule, false );
			}
		}

		if ( ! empty( $data['hmbkp_client_request'] ) ) {

			$site_size = new Site_Size( $schedule->get_type(),  $schedule->get_excludes() );

			// Pass the site size to be displayed when it's ready.
			if ( $site_size->is_site_size_cached() ) {

				$response['hmbkp_site_size'] = $site_size->get_formatted_site_size();

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
add_filter( 'heartbeat_received', 'HM\BackUpWordPress\heartbeat_received', 10, 2 );

/**
 * Load the enable support modal contents
 *
 * @return void
 */
function load_enable_support() {

	check_ajax_referer( 'hmbkp_nonce', '_wpnonce' );

	require_once HMBKP_PLUGIN_PATH . 'admin/enable-support.php';

	die;

}
add_action( 'wp_ajax_load_enable_support', 'HM\BackUpWordPress\load_enable_support' );

/**
 * Display the running status via ajax
 */
function ajax_is_backup_in_progress() {

	check_ajax_referer( 'hmbkp_nonce', 'nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		die;
	}

	$schedule = new Scheduled_Backup( sanitize_text_field( urldecode( $_POST['hmbkp_schedule_id'] ) ) );

	if ( ! $schedule->get_status() ) {
		echo 0;
	} else {
		hmbkp_schedule_status( $schedule );
	}

	die;

}
add_action( 'wp_ajax_hmbkp_is_in_progress', 'HM\BackUpWordPress\ajax_is_backup_in_progress' );

/**
 * Display the calculated size via ajax
 */
function ajax_calculate_backup_size() {

	check_ajax_referer( 'hmbkp_nonce', 'nonce' );

	if ( empty( $_POST['hmbkp_schedule_id'] ) ) {
		die;
	}

	$schedule = new Scheduled_Backup( sanitize_text_field( urldecode( $_POST['hmbkp_schedule_id'] ) ) );

	$recalculate_filesize = true;

	require( HMBKP_PLUGIN_PATH . 'admin/schedule-sentence.php' );

	die;

}
add_action( 'wp_ajax_hmbkp_calculate', 'HM\BackUpWordPress\ajax_calculate_backup_size' );

/**
 * Test the cron response and if it's not 200 show a warning message
 */
function ajax_cron_test() {

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

		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'backupwordpress' ) . '</strong> ' . sprintf( __( '%1$s is returning a %2$s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled backups, and more generally relies on HTTP loopback connections not being blocked for manual backups. See the %3$s for more details.', 'backupwordpress' ), '<code>wp-cron.php</code>', '<code>' . esc_html( wp_remote_retrieve_response_code( $response1 ) ) . ' ' . esc_html( get_status_header_desc( wp_remote_retrieve_response_code( $response1 ) ) ) . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

		update_option( 'hmbkp_wp_cron_test_failed', true );

	} else {

		echo 1;

		delete_option( 'hmbkp_wp_cron_test_failed' );
		set_transient( 'hmbkp_wp_cron_test_beacon', 1, WEEK_IN_SECONDS );

	}

	die;

}
add_action( 'wp_ajax_hmbkp_cron_test', 'HM\BackUpWordPress\ajax_cron_test' );

/**
 * Remember notice dismissal
 */
function hmbkp_dismiss_notice() {
	update_site_option( 'hmbkp_hide_info_notice', true );
}
add_action( 'wp_ajax_hmbkp_dismiss_notice', 'HM\BackUpWordPress\hmbkp_dismiss_notice' );
