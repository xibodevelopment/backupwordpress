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

/**
 * Add a link to the backups page to the plugin action links.
 *
 * @param Array $links
 * @param string $file
 * @return Array $links
 */
function hmbkp_plugin_action_link( $links, $file ) {
	 
	if ( strpos( $file, HMBKP_PLUGIN_SLUG ) !== false )
		array_push( $links, '<a href="tools.php?page=' . HMBKP_PLUGIN_SLUG . '">' . __( 'Backups', 'hmbkp' ) . '</a>' );
	
	return $links;

}
add_filter('plugin_action_links', 'hmbkp_plugin_action_link', 10, 2 );