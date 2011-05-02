<?php

/**
 * Add the backups menu item
 * to the tools menu
 */
function hmbkp_admin_menu() {
	add_management_page( __( 'Manage Backups','hmbkp' ), __( 'Backups','hmbkp' ), 'manage_options', HMBKP_PLUGIN_SLUG, 'hmbkp_manage_backups' );
}
add_action( 'admin_menu', 'hmbkp_admin_menu' );

/**
 * Load the backups admin page
 * when the menu option is clicked
 */
function hmbkp_manage_backups() {
	require_once( HMBKP_PLUGIN_PATH . '/admin.page.php' );
}