<?php

/**
 * Delete the backup and then redirect
 * back to the backups page
 */
function hmbkp_request_delete_backup() {

	if ( empty( $_GET['hmbkp_delete_backup'] ) || ! check_admin_referer( 'hmbkp-delete_backup' ) )
		return;

	$schedule = new HMBKP_Scheduled_Backup( urldecode( $_GET['hmbkp_schedule_id'] ) );

	$schedule->delete_backup( base64_decode( $_GET['hmbkp_delete_backup'] ) );

	wp_redirect( remove_query_arg( array( 'hmbkp_delete_backup', '_wpnonce' ) ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_delete_backup' );

/**
 * Delete a schedule and all it's backups and then redirect
 * back to the backups page
 */
function hmbkp_request_delete_schedule() {

	if ( empty( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_delete_schedule' || ! check_admin_referer( 'hmbkp-delete_schedule' ) )
		return;

	$schedule = new HMBKP_Scheduled_Backup( urldecode( $_GET['hmbkp_schedule_id'] ) );
	$schedule->cancel( true );

	wp_redirect( remove_query_arg( array( 'hmbkp_schedule_id', 'action', '_wpnonce' ) ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_delete_schedule' );

/**
 * Perform a manual backup via ajax
 */
function hmbkp_ajax_request_do_backup() {

	if ( empty( $_POST['hmbkp_schedule_id'] ) )
		exit;

	// We want to display any fatal errors in this ajax request so we can catch them on the other side.
	error_reporting( E_ERROR );
	@ini_set( 'display_errors', 'On' );
	@ini_set( 'html_errors', 'Off' );

	// Force a memory error for testing purposes
	//ini_set( 'memory_limit', '2M' );
	//function a() { a(); } a();

	ignore_user_abort( true );

	hmbkp_cleanup();

	$schedule = new HMBKP_Scheduled_Backup( urldecode( $_POST['hmbkp_schedule_id'] ) );

	$schedule->run();

	exit;

}
add_action( 'wp_ajax_hmbkp_run_schedule', 'hmbkp_ajax_request_do_backup' );

/**
 * Send the download file to the browser and
 * then redirect back to the backups page
 */
function hmbkp_request_download_backup() {

	global $is_apache;

	if ( empty( $_GET['hmbkp_download_backup'] ) || ! check_admin_referer( 'hmbkp-download_backup' ) || ! file_exists( base64_decode( $_GET['hmbkp_download_backup'] ) ) )
		return;

	$url = str_replace( HM_Backup::conform_dir( HM_Backup::get_home_path() ), home_url(), trailingslashit( dirname( base64_decode( $_GET['hmbkp_download_backup'] ) ) ) ) . urlencode( pathinfo( base64_decode( $_GET['hmbkp_download_backup'] ), PATHINFO_BASENAME ) );

	if ( $is_apache ) {

		// Force the .htaccess to be rebuilt
		if ( file_exists( hmbkp_path() . '/.htaccess' ) )
			unlink( hmbkp_path() . '/.htaccess' );

		hmbkp_path();

		$url = add_query_arg( 'key', HMBKP_SECURE_KEY, $url );

	}

	wp_redirect( $url, 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_download_backup' );

/**
 * cancels a running backup then redirect
 * back to the backups page
 */
function hmbkp_request_cancel_backup() {

	if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_cancel' )
		return;

	$schedule = new HMBKP_Scheduled_Backup( urldecode( $_GET['hmbkp_schedule_id'] ) );

	// Delete the running backup
	if ( $schedule->get_running_backup_filename() && file_exists( trailingslashit( hmbkp_path() ) . $schedule->get_running_backup_filename() ) )
		unlink( trailingslashit( hmbkp_path() ) . $schedule->get_running_backup_filename() );

	hmbkp_cleanup();

	wp_redirect( remove_query_arg( array( 'action' ) ), 303 );

	exit;

}
add_action( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_request_cancel_backup' );

/**
 * Dismiss an error and then redirect
 * back to the backups page
 */
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
 */
function hmbkp_ajax_is_backup_in_progress() {

	if ( empty( $_POST['hmbkp_schedule_id'] ) )
		exit;

	$schedule = new HMBKP_Scheduled_Backup( urldecode( $_POST['hmbkp_schedule_id'] ) );

	if ( ! $schedule->get_status() )
		echo 0;

	else
		hmbkp_schedule_actions( $schedule );

	exit;

}
add_action( 'wp_ajax_hmbkp_is_in_progress', 'hmbkp_ajax_is_backup_in_progress' );

/**
 * Display the calculated size via ajax
 */
function hmbkp_ajax_calculate_backup_size() {

	if ( empty( $_POST['hmbkp_schedule_id'] ) )
		exit;

	$schedule = new HMBKP_Scheduled_Backup( urldecode( $_POST['hmbkp_schedule_id'] ) );

	$recalculate_filesize = true;

	include_once( HMBKP_PLUGIN_PATH . '/admin/schedule.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_calculate', 'hmbkp_ajax_calculate_backup_size' );

/**
 * Test the cron response and if it's not 200 show a warning message
 */
function hmbkp_ajax_cron_test() {

	$response = wp_remote_head( site_url( 'wp-cron.php' ) );

	if ( is_wp_error( $response ) )
		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%1$s is returning a %2$s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled back ups. See the %3$s for more details.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>' . $response->get_error_message() . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

	elseif ( wp_remote_retrieve_response_code( $response ) != 200 )
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%1$s is returning a %2$s response which could mean cron jobs aren\'t getting fired properly. BackUpWordPress relies on wp-cron to run scheduled back ups. See the %3$s for more details.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>' . wp_remote_retrieve_response_code( $response ) . ' ' . get_status_header_desc( wp_remote_retrieve_response_code( $response ) ) . '</code>', '<a href="http://wordpress.org/extend/plugins/backupwordpress/faq/">FAQ</a>' ) . '</p></div>';

	else
		echo 1;

	exit;

}
add_action( 'wp_ajax_hmbkp_cron_test', 'hmbkp_ajax_cron_test' );

/**
 * Load the edit schedule form
 */
function hmbkp_edit_schedule_load() {

	if ( empty( $_GET['hmbkp_schedule_id'] ) )
		exit;

	$schedule = new HMBKP_Scheduled_Backup( $_GET['hmbkp_schedule_id'] );

	require( HMBKP_PLUGIN_PATH . '/admin/schedule-form.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_edit_schedule_load', 'hmbkp_edit_schedule_load' );

/**
 * Load the edit schedule excludes form
 */
function hmbkp_edit_schedule_excludes_load() {

	if ( empty( $_GET['hmbkp_schedule_id'] ) )
		exit;

	$schedule = new HMBKP_Scheduled_Backup( $_GET['hmbkp_schedule_id'] );

	require( HMBKP_PLUGIN_PATH . '/admin/schedule-form-excludes.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_edit_schedule_excludes_load', 'hmbkp_edit_schedule_excludes_load' );

/**
 * Load the add schedule form
 */
function hmbkp_add_schedule_load() {

	$schedule = new HMBKP_Scheduled_Backup( date( 'U' ) );
	$is_new_schedule = true;

	require( HMBKP_PLUGIN_PATH . '/admin/schedule-form.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_add_schedule_load', 'hmbkp_add_schedule_load' );

/**
 * Catch the edit schedule form
 *
 * Validate and either return errors or update the schedule
 */
function hmnkp_edit_schedule_submit() {

	if ( empty( $_GET['hmbkp_schedule_id'] ) )
		exit;

	$schedule = new HMBKP_Scheduled_Backup( $_GET['hmbkp_schedule_id'] );

	$errors = array();

	if ( isset( $_GET['hmbkp_schedule_type'] ) ) {

		if ( ! trim( $_GET['hmbkp_schedule_type'] ) )
			$errors['hmbkp_schedule_type'] = __( 'Backup type cannot be empty', 'hmbkp' );

		elseif ( ! in_array( $_GET['hmbkp_schedule_type'], array( 'complete', 'file', 'database' ) ) )
			$errors['hmbkp_schedule_type'] = __( 'Invalid backup type', 'hmbkp' );

		else
			$schedule->set_type( $_GET['hmbkp_schedule_type'] );

	}

	if ( isset( $_GET['hmbkp_schedule_reoccurrence'] ) ) {

		if ( empty( $_GET['hmbkp_schedule_reoccurrence'] ) )
			$errors['hmbkp_schedule_reoccurrence'] = __( 'Schedule cannot be empty', 'hmbkp' );

		elseif ( ! in_array( $_GET['hmbkp_schedule_reoccurrence'], array_keys( wp_get_schedules() ) ) && $_GET['hmbkp_schedule_reoccurrence'] !== 'manually' )
			$errors['hmbkp_schedule_reoccurrence'] = __( 'Invalid schedule', 'hmbkp' );

		else
			$schedule->set_reoccurrence( $_GET['hmbkp_schedule_reoccurrence'] );

	}

	if ( isset( $_GET['hmbkp_schedule_max_backups'] ) ) {

		if ( empty( $_GET['hmbkp_schedule_max_backups'] ) )
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups can\'t be empty', 'hmbkp' );

		elseif ( ! is_numeric( $_GET['hmbkp_schedule_max_backups'] ) )
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be a number', 'hmbkp' );

		elseif ( ! ( $_GET['hmbkp_schedule_max_backups'] >= 1 ) )
			$errors['hmbkp_schedule_max_backups'] = __( 'Max backups must be greater than 0', 'hmbkp' );

		else
			$schedule->set_max_backups( (int) $_GET['hmbkp_schedule_max_backups'] );

		$schedule->delete_old_backups();

	}

	foreach ( HMBKP_Services::get_services( $schedule ) as $service )
        $errors = array_merge( $errors, $service->save() );

	$schedule->save();

	if ( $errors )
		echo json_encode( $errors );

	exit;

}
add_action( 'wp_ajax_hmnkp_edit_schedule_submit', 'hmnkp_edit_schedule_submit' );


/**
 * Add an exclude rule
 *
 * @access public
 * @return void
 */
function hmbkp_add_exclude_rule() {

	if ( empty( $_POST['hmbkp_schedule_id'] ) )
		exit;

	$schedule = new HMBKP_Scheduled_Backup( $_POST['hmbkp_schedule_id'] );

	$schedule->set_excludes( $_POST['hmbkp_exclude_rule'], true );

	$schedule->save();

	include( HMBKP_PLUGIN_PATH . '/admin/schedule-form-excludes.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_add_exclude_rule', 'hmbkp_add_exclude_rule' );


/**
 * Delete an exclude rule
 *
 * @access public
 * @return void
 */
function hmbkp_delete_exclude_rule() {

	if ( empty( $_GET['hmbkp_schedule_id'] ) )
		exit;

	$schedule = new HMBKP_Scheduled_Backup( $_GET['hmbkp_schedule_id'] );

	$excludes = $schedule->get_excludes();

	$schedule->set_excludes( array_diff( $excludes, (array) stripslashes( $_GET['hmbkp_exclude_rule'] ) ) );

	$schedule->save();

	include( HMBKP_PLUGIN_PATH . '/admin/schedule-form-excludes.php' );

	exit;

}
add_action( 'wp_ajax_hmbkp_delete_exclude_rule', 'hmbkp_delete_exclude_rule' );


/**
 * Ajax action for previewing an exclude rule.
 *
 * @access public
 * @return void
 */
function hmbkp_preview_exclude_rule() {

	if ( empty( $_POST['hmbkp_schedule_id'] ) || empty( $_POST['hmbkp_schedule_excludes'] ) )
		exit;

	$schedule = new HMBKP_Scheduled_Backup( $_POST['hmbkp_schedule_id'] );

	$excludes = explode( ',', $_POST['hmbkp_schedule_excludes'] );

	hmbkp_file_list( $schedule, $excludes, 'get_excluded_files' );

	$schedule->set_excludes( $excludes );

	if ( $schedule->get_excluded_file_count() ) { ?>

		<p><?php printf( _n( '%s matches 1 file.', '%1$s matches %2$d files.', $schedule->get_excluded_file_count(), 'hmbkp' ), '<code>' . implode( '</code>, <code>', $excludes ) . '</code>', $schedule->get_excluded_file_count() ); ?></p>

	<?php } else { ?>

		<p><?php printf( __( '%s didn\'t match any files.', 'hmbkp' ), '<code>' . implode( '</code>, <code>', $excludes ) . '</code>' ); ?></p>

	<?php } ?>

		<p><button type="button" class="button-primary hmbkp_save_exclude_rule"><?php _e( 'Exclude', 'hmbkp' ); ?></button> <button type="button" class="button-secondary hmbkp_cancel_save_exclude_rule"><?php _e( 'Cancel', 'hmbkp' ); ?></button></p>

	<?php exit;

}
add_action( 'wp_ajax_hmbkp_file_list', 'hmbkp_preview_exclude_rule', 10, 0 );

function hmbkp_display_error_and_offer_to_email_it() {

	if ( empty( $_POST['hmbkp_error'] ) )
		exit;

	$error = str_replace( HM_Backup::get_home_path(), '', $_POST['hmbkp_error'] ); ?>

	<h3><?php _e( 'Your BackUp Failed', 'hmbkp' ); ?></h3>

	<p><?php _e( "Here's the response from the server:" ); ?></p>

	<pre><?php echo esc_attr( $error ); ?></pre>

	<p class="description">You can email details of this error to <a href="http://hmn.md">Human Made Limited</a> so they can look into the issue.<br /><br /></p>

	<button class="button hmbkp-fancybox-close"><?php _e( 'Close', 'hmbkp' ); ?></button>
	<button class="button-primary hmbkp_send_error_via_email right"><?php _e( 'Email to Support', 'hmbkp' ); ?></button>

	<?php exit;
}
add_action( 'wp_ajax_hmbkp_backup_error', 'hmbkp_display_error_and_offer_to_email_it' );

function hmbkp_send_error_via_email() {

	if ( empty( $_POST['hmbkp_error'] ) )
		exit;

	$error = $_POST['hmbkp_error'];

	wp_mail( 'support@humanmade.co.uk', 'BackUpWordPress Fatal error on ' . parse_url( home_url(), PHP_URL_HOST ), $error, 'From: BackUpWordPress <' . get_bloginfo( 'admin_email' ) . '>' . "\r\n" );

	exit;

}
add_action( 'wp_ajax_hmbkp_email_error', 'hmbkp_send_error_via_email' );