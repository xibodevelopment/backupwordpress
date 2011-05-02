<?php

/*
Plugin Name: BackUpWordPress
Plugin URI: http://humanmade.co.uk/
Description: Simple automated backups of your WordPress powered website. Once activated you'll find me under <strong>Tools &rarr; Backups</strong>.
Author: Human Made Limited
Version: 1.2 Beta
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

	// Check whether we need to disable the cron
	if ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) && HMBKP_DISABLE_AUTOMATIC_BACKUP && wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) )
		wp_clear_scheduled_hook( 'hmbkp_schedule_backup_hook' );

	// Or whether we need to re-enable it
	elseif( ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) && !HMBKP_DISABLE_AUTOMATIC_BACKUP || !defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) ) && !wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) )
		hmbkp_setup_daily_schedule();

	// Allow the time of the daily backup to be changed
	if ( defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) && HMBKP_DAILY_SCHEDULE_TIME && wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) != strtotime( HMBKP_DAILY_SCHEDULE_TIME ) && wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) )
		hmbkp_setup_daily_schedule();

	// Reset if custom time is removed
	elseif( ( ( defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) && !HMBKP_DAILY_SCHEDULE_TIME ) || !defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) ) && date( 'H:i', wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) ) != '23:00' && ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) && !HMBKP_DISABLE_AUTOMATIC_BACKUP || !defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) ) )
		hmbkp_setup_daily_schedule();

}
add_action( 'admin_init', 'hmbkp_actions' );

/**
 * Hook in an change the plugin description when BackUpWordPress is activated
 *
 * @param array $plugins
 * @return $plugins
 */
function hmbkp_plugin_row( $plugins ) {

	if ( isset( $plugins[HMBKP_PLUGIN_SLUG . '/plugin.php'] ) )
		$plugins[HMBKP_PLUGIN_SLUG . '/plugin.php']['Description'] = str_replace( 'Once activated you\'ll find me under <strong>Tools &rarr; Backups</strong>', 'Find me under <strong><a href="' . admin_url( 'tools.php?page=' . HMBKP_PLUGIN_SLUG ) . '">Tools &rarr; Backups</a></strong>', $plugins[HMBKP_PLUGIN_SLUG . '/plugin.php']['Description'] );

	return $plugins;

}
add_filter( 'all_plugins', 'hmbkp_plugin_row', 10 );

// Load the admin menu
require_once( HMBKP_PLUGIN_PATH . '/admin.menus.php' );

// Load the core functions
require_once( HMBKP_PLUGIN_PATH . '/functions/core.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/interface.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/settings.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.mysql.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.files.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.mysql.fallback.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/backup.files.fallback.functions.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/mysql-ping.php' );

// Plugin activation and deactivation
add_action( 'activate_' . HMBKP_PLUGIN_SLUG . '/plugin.php', 'hmbkp_activate' );
add_action( 'deactivate_' . HMBKP_PLUGIN_SLUG . '/plugin.php', 'hmbkp_deactivate' );

// Add more cron schedules
add_filter( 'cron_schedules', 'hmbkp_more_reccurences' );

// Cron hook for backups
add_action( 'hmbkp_schedule_backup_hook', 'hmbkp_do_backup' );
add_action( 'hmbkp_schedule_single_backup_hook', 'hmbkp_do_backup' );

// Make sure backups dir exists and is writable
if ( !is_dir( hmbkp_path() ) ) :

    function hmbkp_path_exists_warning() {
	    $php_user = exec( 'whoami' );
		$php_group = reset( explode( ' ', exec( 'groups' ) ) );
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress is almost ready.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'The backups directory can\'t be created because your %s directory isn\'t writable, run %s or %s or create the folder yourself.', 'hmbkp' ), '<code>wp-content</code>', '<code>chown ' . $php_user . ':' . $php_group . ' ' . WP_CONTENT_DIR . '</code>', '<code>chmod 777 ' . WP_CONTENT_DIR . '</code>' ) . '</p></div>';
    }
    add_action( 'admin_notices', 'hmbkp_path_exists_warning' );

endif;

if ( is_dir( hmbkp_path() ) && !is_writable( hmbkp_path() ) ) :

    function hmbkp_writable_path_warning() {
		$php_user = exec( 'whoami' );
		$php_group = reset( explode( ' ', exec( 'groups' ) ) );
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress is almost ready.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'Your backups directory isn\'t writable. run %s or %s or set the permissions yourself.', 'hmbkp' ), '<code>chown -R ' . $php_user . ':' . $php_group . ' ' . hmbkp_path() . '</code>', '<code>chmod -R 777 ' . hmbkp_path() . '</code>' ) . '</p></div>';
    }
    add_action( 'admin_notices', 'hmbkp_writable_path_warning' );

endif;

if ( ini_get( 'safe_mode' ) ) :

    function hmbkp_safe_mode_warning() {
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( ' %s is running in %s. Please contact your host and ask them to disable %s.', 'hmbkp' ), '<code>PHP</code>', '<a href="http://php.net/manual/en/features.safe-mode.php"><code>Safe Mode</code></a>', '<code>Safe Mode</code>' ) . '</p></div>';
    }
    add_action( 'admin_notices', 'hmbkp_safe_mode_warning' );

endif;

if ( defined( 'HMBKP_FILES_ONLY' ) && HMBKP_FILES_ONLY && defined( 'HMBKP_DATABASE_ONLY' ) && HMBKP_DATABASE_ONLY ) :

    function hmbkp_nothing_to_backup_warning() {
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'You have both %s and %s defined so there isn\'t anything to back up.', 'hmbkp' ), '<code>HMBKP_DATABASE_ONLY</code>', '<code>HMBKP_FILES_ONLY</code>' ) . '</p></div>';
    }
    add_action( 'admin_notices', 'hmbkp_nothing_to_backup_warning' );

endif;

if ( get_transient( 'hmbkp_estimated_filesize' ) && disk_free_space( ABSPATH ) <= ( 2 * get_transient( 'hmbkp_estimated_filesize' ) ) ) :

    function hmbkp_low_space_warning() {
    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'You only have %s of free space left on your server.', 'hmbkp' ), '<code>' . hmbkp_size_readable( disk_free_space( ABSPATH ), null, '%01u %s' ) . '</code>' ) . '</p></div>';
    }
    add_action( 'admin_notices', 'hmbkp_low_space_warning' );

endif;