<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

if ( ! defined( 'HMBKP_REQUIRED_WP_VERSION' ) ) {
	define( 'HMBKP_REQUIRED_WP_VERSION', '3.8.4' );
}

// Don't activate on old versions of WordPress
global $wp_version;

if ( version_compare( $wp_version, HMBKP_REQUIRED_WP_VERSION, '<' ) ) {
	return;
}

if ( ! defined( 'HMBKP_PLUGIN_PATH' ) ) {
	define( 'HMBKP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Load the schedules
require_once( HMBKP_PLUGIN_PATH . 'hm-backup/hm-backup.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-services.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-schedule.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-schedules.php' );
require_once( HMBKP_PLUGIN_PATH . 'functions/core.php' );

$schedules = HMBKP_Schedules::get_instance();

// Cancel all the schedules and delete all the backups
foreach ( $schedules->get_schedules() as $schedule ) {
	$schedule->cancel( true );
}

// Remove the backups directory
hmbkp_rmdirtree( hmbkp_path() );

// Remove all the options
foreach ( array( 'hmbkp_enable_support', 'hmbkp_plugin_version', 'hmbkp_path', 'hmbkp_default_path', 'hmbkp_upsell' ) as $option ) {
	delete_option( $option );
}

// Delete all transients
foreach ( array( 'hmbkp_plugin_data', 'hmbkp_directory_filesizes', 'hmbkp_directory_filesize_running' ) as $transient ) {
	delete_transient( $transient );
}
