<?php

if ( ! defined( 'HMBKP_PLUGIN_PATH' ) )
	define( 'HMBKP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Load the schedules
require_once( HMBKP_PLUGIN_PATH . 'hm-backup/hm-backup.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-services.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-schedule.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-schedules.php' );
require_once( HMBKP_PLUGIN_PATH . 'functions/core.php' );

$schedules = HMBKP_Schedules::get_instance();

// Cancel all the schedules and delete all the backups
foreach ( $schedules->get_schedules() as $schedule )
	$schedule->cancel( true );

// Remove the backups directory
hmbkp_rmdirtree( hmbkp_path() );

// Remove all the options
foreach ( array( 'hmbkp_enable_support', 'hmbkp_plugin_version', 'hmbkp_path', 'hmbkp_default_path' ) as $option )
	delete_option( $option );
