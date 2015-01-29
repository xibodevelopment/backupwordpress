<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

global $wpdb;

// Get all schedule options with a SELECT query and delete them.
$schedules = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", 'hmbkp_schedule_%' ) );

array_map( 'delete_option', $schedules );

// Remove the backups directory
require_once( dirname( __FILE__ ) . '/class-backup.php' );
require_once( dirname( __FILE__ ) . '/class-hmbkp-path.php' );
require_once( dirname( __FILE__ ) . '/../functions/core.php' );
hmbkp_rmdirtree( hmbkp_path() );

// Remove all the options
foreach ( array( 'hmbkp_enable_support', 'hmbkp_plugin_version', 'hmbkp_path', 'hmbkp_default_path', 'hmbkp_upsell' ) as $option ) {
	delete_option( $option );
}

// Delete all transients
foreach ( array( 'hmbkp_plugin_data', 'hmbkp_directory_filesizes', 'hmbkp_directory_filesize_running' ) as $transient ) {
	delete_transient( $transient );
}
