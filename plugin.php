<?php

/*
Plugin Name: BackUpWordPress
Plugin URI: http://hmn.md/backupwordpress/
Description: Simple automated backups of your WordPress powered website. Once activated you'll find me under <strong>Tools &rarr; Backups</strong>.
Author: Human Made Limited
Version: 2.2.4
Author URI: http://hmn.md/
*/

/*  Copyright 2011 Human Made Limited  (email : support@hmn.md)

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

if ( ! defined( 'HMBKP_PLUGIN_SLUG' ) )
	define( 'HMBKP_PLUGIN_SLUG', 'backupwordpress' );

if ( ! defined( 'HMBKP_PLUGIN_PATH' ) )
	define( 'HMBKP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'HMBKP_PLUGIN_URL' ) )
	define( 'HMBKP_PLUGIN_URL', plugins_url( HMBKP_PLUGIN_SLUG ) );

if ( ! defined( 'HMBKP_ADMIN_URL' ) )
	define( 'HMBKP_ADMIN_URL', add_query_arg( 'page', HMBKP_PLUGIN_SLUG, admin_url( 'tools.php' ) ) );

$key = array( ABSPATH, time() );

foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT', 'SECRET_KEY' ) as $constant )
	if ( defined( $constant ) )
		$key[] = $constant;

define( 'HMBKP_SECURE_KEY', md5( shuffle( $key ) ) );

if ( ! defined( 'HMBKP_REQUIRED_WP_VERSION' ) )
	define( 'HMBKP_REQUIRED_WP_VERSION', '3.3.3' );

// Max memory limit isn't defined in old versions of WordPress
if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) )
	define( 'WP_MAX_MEMORY_LIMIT', '256M' );

if ( ! defined( 'HMBKP_SCHEDULE_TIME' ) )
	define( 'HMBKP_SCHEDULE_TIME', '11pm' );

// Don't activate on anything less than PHP 5.2.4
if ( version_compare( phpversion(), '5.2.4', '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( __FILE__ );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( __( 'BackUpWordPress requires PHP version 5.2.4 or greater.', 'hmbkp' ) );

}

// Don't activate on old versions of WordPress
if ( version_compare( get_bloginfo( 'version' ), HMBKP_REQUIRED_WP_VERSION, '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( __FILE__ );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( sprintf( __( 'BackUpWordPress requires WordPress version %s or greater.', 'hmbkp' ), HMBKP_REQUIRED_WP_VERSION ) );

}

// Load the admin menu
require_once( HMBKP_PLUGIN_PATH . '/admin/menu.php' );
require_once( HMBKP_PLUGIN_PATH . '/admin/actions.php' );

// Load hm-backup
if ( ! class_exists( 'HM_Backup' ) )
	require_once( HMBKP_PLUGIN_PATH . '/hm-backup/hm-backup.php' );

// Load the schedules
require_once( HMBKP_PLUGIN_PATH . '/classes/schedule.php' );
require_once( HMBKP_PLUGIN_PATH . '/classes/schedules.php' );

// Load the core functions
require_once( HMBKP_PLUGIN_PATH . '/functions/core.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/interface.php' );

// Load Services
require_once( HMBKP_PLUGIN_PATH . '/classes/services.php' );

// Load the email service
require_once( HMBKP_PLUGIN_PATH . '/classes/email.php' );

// Load the wp cli command
if ( defined( 'WP_CLI' ) && WP_CLI )
	include( HMBKP_PLUGIN_PATH . '/classes/wp-cli.php' );

// Hook in the activation and deactivation actions
register_activation_hook( HMBKP_PLUGIN_SLUG . '/plugin.php', 'hmbkp_activate' );
register_deactivation_hook( HMBKP_PLUGIN_SLUG . '/plugin.php', 'hmbkp_deactivate' );

// Handle any advanced option changes
hmbkp_constant_changes();

if ( ! function_exists( 'hmbkp_init' ) ) :

/**
 * Plugin setup
 *
 * @return null
 */
function hmbkp_init() {

	$plugin_data = get_plugin_data( __FILE__ );

	// define the plugin version
	define( 'HMBKP_VERSION', $plugin_data['Version'] );

	// Load translations
	load_plugin_textdomain( 'hmbkp', false, HMBKP_PLUGIN_SLUG . '/languages/' );

	// Fire the update action
	if ( HMBKP_VERSION != get_option( 'hmbkp_plugin_version' ) )
		hmbkp_update();

	// Load admin css and js
	if ( isset( $_GET['page'] ) && $_GET['page'] == HMBKP_PLUGIN_SLUG ) {

		wp_enqueue_script( 'hmbkp-fancybox', HMBKP_PLUGIN_URL . '/assets/fancyBox/source/jquery.fancybox.js', array( 'jquery' ), sanitize_title( HMBKP_VERSION ) );
		wp_enqueue_script( 'hmbkp', HMBKP_PLUGIN_URL . '/assets/hmbkp.js', array( 'jquery-ui-tabs', 'jquery-ui-widget', 'hmbkp-fancybox' ), sanitize_title( HMBKP_VERSION ) );

		wp_localize_script( 'hmbkp', 'hmbkp', array(
			'nonce'         		=> wp_create_nonce( 'hmbkp_nonce' ),
			'update'				=> __( 'Update', 'hmbkp' ),
			'cancel'				=> __( 'Cancel', 'hmbkp' ),
			'delete_schedule'		=> __( 'Are you sure you want to delete this schedule? All of it\'s backups will also be deleted.' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'hmbkp' ) . "\n",
			'delete_backup'			=> __( 'Are you sure you want to delete this backup?', 'hmbkp' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'hmbkp' ) . "\n",
			'remove_exclude_rule'	=> __( 'Are you sure you want to remove this exclude rule?', 'hmbkp' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'hmbkp' ) . "\n",
			'remove_old_backups'	=> __( 'Reducing the number of backups that are stored on this server will cause some of your existing backups to be deleted, are you sure that\'s what you want?', 'hmbkp' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'hmbkp' ) . "\n"
		) );

		wp_enqueue_style( 'hmbkp_fancybox', HMBKP_PLUGIN_URL . '/assets/fancyBox/source/jquery.fancybox.css', false, HMBKP_VERSION );
		wp_enqueue_style( 'hmbkp', HMBKP_PLUGIN_URL . '/assets/hmbkp.css', false, HMBKP_VERSION );

	}

}
add_action( 'admin_init', 'hmbkp_init' );

/**
 * Function to run when the schedule cron fires
 * @param  array $args
 */
function hmbkp_schedule_hook_run( $schedule_id ) {

	$schedules = new HMBKP_Schedules();
	$schedule = $schedules->get_schedule( $schedule_id );

	if ( ! $schedule )
		return;

	$schedule->run();

}
add_action( 'hmbkp_schedule_hook', 'hmbkp_schedule_hook_run' );

endif;