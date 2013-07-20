<?php

if ( ! defined( 'HMBKP_PLUGIN_PATH' ) )
	define( 'HMBKP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'HMBKP_SCHEDULE_TIME' ) )
	define( 'HMBKP_SCHEDULE_TIME', '11pm' );

// Load the schedules
require_once( HMBKP_PLUGIN_PATH . '/hm-backup/hm-backup.php' );
require_once( HMBKP_PLUGIN_PATH . '/classes/services.php' );
require_once( HMBKP_PLUGIN_PATH . '/classes/schedule.php' );
require_once( HMBKP_PLUGIN_PATH . '/classes/schedules.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/core.php' );

$schedules = new HMBKP_Schedules;

// Cancel all the schedules and delete all the backups
foreach ( $schedules->get_schedules() as $schedule )
	$schedule->cancel( true );

// Remove the backups directory
hmbkp_rmdirtree( hmbkp_path() );

// Remove all the options
foreach ( array( 'hmbkp_plugin_version', 'hmbkp_path', 'hmbkp_path_default' ) as $option )
	delete_option( $option );