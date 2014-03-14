<?php

/*
Plugin Name: BackUpWordPress
Plugin URI: http://hmn.md/backupwordpress/
Description: Simple automated backups of your WordPress powered website. Once activated you'll find me under <strong>Tools &rarr; Backups</strong>.
Author: Human Made Limited
Version: 2.5
Author URI: http://hmn.md/
*/

/*
Copyright 2011 - 2014 Human Made Limited  (email : support@hmn.md)

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

defined( 'WPINC' ) or die;

require_once dirname( __FILE__ ) . '/class-plugin.php';

// Get an instance of the plugin
$backupwordpress = BackUpWordPress_Plugin::get_instance();

// Hook in the activation and deactivation actions
register_activation_hook( HMBKP_PLUGIN_SLUG . '/backupwordpress.php', 'hmbkp_activate' );
register_deactivation_hook( HMBKP_PLUGIN_SLUG . '/backupwordpress.php', 'hmbkp_deactivate' );

// Don't activate on old versions of WordPress
global $wp_version;

if ( version_compare( $wp_version, HMBKP_REQUIRED_WP_VERSION, '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( __FILE__ );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( sprintf( __( 'BackUpWordPress requires WordPress version %s or greater.', 'hmbkp' ), HMBKP_REQUIRED_WP_VERSION ) );

}
