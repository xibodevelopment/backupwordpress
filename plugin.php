<?php

/*
Plugin Name: BackUpWordPress
Plugin URI: http://humanmade.co.uk/
Description: Simple automated backups of your WordPress powered website. Once activated you'll find me under <strong>Tools &rarr; Backups</strong>.
Author: Human Made Limited
Version: 1.3.1
Author URI: http://humanmade.co.uk/
*/

/*  Copyright 2011 Human Made Limited  (email : hello@humanmade.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'HMBKP_PLUGIN_SLUG', 'backupwordpress' );
define( 'HMBKP_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . HMBKP_PLUGIN_SLUG );
define( 'HMBKP_PLUGIN_URL', WP_PLUGIN_URL . '/' . HMBKP_PLUGIN_SLUG );
define( 'HMBKP_REQUIRED_WP_VERSION', '3.1' );


// Don't activate on anything less than PHP5
if ( version_compare( phpversion(), '5.0', '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( ABSPATH . 'wp-content/plugins/' . HMBKP_PLUGIN_SLUG . '/plugin.php' );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( __( 'BackUpWordPress requires PHP version 5.0.', 'hmbkp' ) );

}

// Don't activate on old versions of WordPress
if ( version_compare( get_bloginfo('version'), HMBKP_REQUIRED_WP_VERSION, '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( ABSPATH . 'wp-content/plugins/' . HMBKP_PLUGIN_SLUG . '/plugin.php' );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( sprintf( __( 'BackUpWordPress requires WordPress version %s.', 'hmbkp' ), HMBKP_REQUIRED_WP_VERSION ) );

}

// Load the admin actions file
function hmbkp_actions() {

	$plugin_data = get_plugin_data( __FILE__ );

	define( 'HMBKP_VERSION', $plugin_data['Version'] );

	// Fire the update action
	if ( HMBKP_VERSION > get_option( 'hmbkp_plugin_version' ) )
		hmbkp_update();

	require_once( HMBKP_PLUGIN_PATH . '/admin.actions.php' );

	// Load admin css and js
	if ( isset( $_GET['page'] ) && $_GET['page'] == HMBKP_PLUGIN_SLUG ) :
		wp_enqueue_script( 'hmbkp', HMBKP_PLUGIN_URL . '/assets/hmbkp.js' );
		wp_enqueue_style( 'hmbkp', HMBKP_PLUGIN_URL . '/assets/hmbkp.css' );
	endif;

	// Handle any advanced option changes
	hmbkp_constant_changes();

}
add_action( 'admin_init', 'hmbkp_actions' );

// Load the admin menu
require_once( HMBKP_PLUGIN_PATH . '/admin.menus.php' );

// Load the core functions
require_once( HMBKP_PLUGIN_PATH . '/functions/core.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/interface.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.mysql.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.files.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.mysql.fallback.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.files.fallback.functions.php' );

// Plugin activation and deactivation
add_action( 'activate_' . HMBKP_PLUGIN_SLUG . '/plugin.php', 'hmbkp_activate' );
add_action( 'deactivate_' . HMBKP_PLUGIN_SLUG . '/plugin.php', 'hmbkp_deactivate' );

// Add more cron schedules
add_filter( 'cron_schedules', 'hmbkp_more_reccurences' );

// Cron hook for backups
add_action( 'hmbkp_schedule_backup_hook', 'hmbkp_do_backup' );
add_action( 'hmbkp_schedule_single_backup_hook', 'hmbkp_do_backup' );