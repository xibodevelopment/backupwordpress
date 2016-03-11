<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

global $wpdb;

require_once dirname( __FILE__ ) . '/classes/class-path.php';

$path = HM\BackUpWordPress\PATH::get_instance()->get_existing_path();

// Delete the file manifest if it exists
if ( file_exists( $path . '/.files' ) ) {
	unlink( $path . '/.files' );
}

// Get all schedule options with a SELECT query and delete them.
$schedules = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", 'hmbkp_schedule_%' ) );

array_map( 'delete_option', $schedules );

// Remove all the options
array_map( 'delete_option', array( 'hmbkp_enable_support', 'hmbkp_plugin_version', 'hmbkp_path', 'hmbkp_default_path', 'hmbkp_upsell', 'hmbkp_notices' ) );

// Delete all transients
array_map( 'delete_transient', array( 'hmbkp_plugin_data', 'hmbkp_directory_filesizes', 'hmbkp_directory_filesize_running', 'timeout_hmbkp_wp_cron_test_beacon', 'hmbkp_wp_cron_test_beacon' ) );
