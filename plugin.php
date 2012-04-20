<?php

/*
Plugin Name: BackUpWordPress
Plugin URI: http://hmn.md/backupwordpress/
Description: Simple automated backups of your WordPress powered website. Once activated you'll find me under <strong>Tools &rarr; Backups</strong>.
Author: Human Made Limited
Version: 2.0 alpha
Author URI: http://hmn.md/
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
define( 'HMBKP_ADMIN_URL', add_query_arg( 'page', HMBKP_PLUGIN_SLUG, admin_url( 'tools.php' ) ) );

if ( ! defined( 'HMBKP_SECURE_KEY' ) )
	define( 'HMBKP_SECURE_KEY', md5( ABSPATH . time() ) );

if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) )
	define( 'WP_MAX_MEMORY_LIMIT', '256M' );

// Don't activate on anything less than PHP 5.2.4
if ( version_compare( phpversion(), '5.2.4', '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( HMBKP_PLUGIN_PATH . '/plugin.php' );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( __( 'BackUpWordPress requires PHP version 5.2.4 or greater.', 'hmbkp' ) );

}

// Don't activate on old versions of WordPress
if ( version_compare( get_bloginfo( 'version' ), HMBKP_REQUIRED_WP_VERSION, '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( HMBKP_PLUGIN_PATH . '/plugin.php' );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( sprintf( __( 'BackUpWordPress requires WordPress version %s.', 'hmbkp' ), HMBKP_REQUIRED_WP_VERSION ) );

}

/**
 * Plugin setup
 *
 * @return null
 */
function hmbkp_init() {

	$plugin_data = get_plugin_data( __FILE__ );

	define( 'HMBKP_VERSION', $plugin_data['Version'] );

	load_plugin_textdomain( 'hmbkp', false, HMBKP_PLUGIN_SLUG . '/languages/' );

	// Fire the update action
	if ( HMBKP_VERSION > get_option( 'hmbkp_plugin_version' ) )
		hmbkp_update();

	// Load admin css and js
	if ( isset( $_GET['page'] ) && $_GET['page'] == HMBKP_PLUGIN_SLUG ) {


		//wp_enqueue_script( 'hmbkp_modernizr', HMBKP_PLUGIN_URL . '/assets/modernizr.js' );
		//wp_enqueue_script( 'hmbkp_webshim', HMBKP_PLUGIN_URL . '/assets/webshim/src/polyfiller.js', array( 'jquery', 'hmbkp_modernizr' ) );
		wp_enqueue_script( 'hmbkp_fancybox', HMBKP_PLUGIN_URL . '/assets/fancyBox/source/jquery.fancybox.js', array( 'jquery' ) );
		wp_enqueue_script( 'hmbkp', HMBKP_PLUGIN_URL . '/assets/hmbkp.js', array( 'jquery-ui-tabs', 'hmbkp_fancybox' ) );

		wp_enqueue_style( 'hmbkp_fancybox', HMBKP_PLUGIN_URL . '/assets/fancyBox/source/jquery.fancybox.css' );
		wp_enqueue_style( 'hmbkp', HMBKP_PLUGIN_URL . '/assets/hmbkp.css' );

	}

	// Handle any advanced option changes
	hmbkp_constant_changes();

}
add_action( 'admin_init', 'hmbkp_init' );

// Load the admin menu
require_once( HMBKP_PLUGIN_PATH . '/admin.menu.php' );
require_once( HMBKP_PLUGIN_PATH . '/admin.actions.php' );

// Load hm-backup
if ( ! class_exists( 'HM_Backup' ) )
	require_once( HMBKP_PLUGIN_PATH . '/hm-backup/hm-backup.php' );

// Load the schedules
require_once( HMBKP_PLUGIN_PATH . '/class.schedules.php' );

// Load the core functions
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.actions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/core.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/interface.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.functions.php' );

// Load the wp cli command
if ( defined( 'WP_CLI' ) && WP_CLI )
	include( HMBKP_PLUGIN_PATH . '/functions/wp-cli.php' );

if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) )
	define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( hmbkp_path() ) );

// Plugin activation and deactivation
add_action( 'activate_' . HMBKP_PLUGIN_SLUG . '/plugin.php', 'hmbkp_activate' );
add_action( 'deactivate_' . HMBKP_PLUGIN_SLUG . '/plugin.php', 'hmbkp_deactivate' );