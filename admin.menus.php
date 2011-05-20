<?php

/**
 * Add the backups menu item
 * to the tools menu
 */
function hmbkp_admin_menu() {
	global $hmbkp_plugin_hook;
	$hmbkp_plugin_hook = add_management_page( __( 'Manage Backups','hmbkp' ), __( 'Backups','hmbkp' ), 'manage_options', HMBKP_PLUGIN_SLUG, 'hmbkp_manage_backups' );
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

/**
 *	Add Contextual Help to Backups tools page.
 *
 *	Help is pulled from the readme FAQ.
 *	NOTE: FAQ used is from the wordpress repo, and might not be up to date for development versions.
 *
 */
 function hmbkp_contextual_help( $contextual_help, $screen_id, $screen ) {
	global $hmbkp_plugin_hook;
	if ( $screen_id == $hmbkp_plugin_hook ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		$plugin = plugins_api( 'plugin_information', array( 'slug' => 'backupwordpress' ) );
		$hmbkp_contextual_help = '';
		
		//Check if help is for the right version.	
		if( version_compare( HMBKP_VERSION, $plugin->version, '>' ) > 0 )
			$hmbkp_contextual_help .= sprintf( __('<p><strong>Help for version <em>%s</em>.</strong> Looks like you are using a development version <em>(%s)</em> &mdash; this information may not be up to date. Please check the readme.txt file.</p>', 'hmbkp'), $plugin->version, HMBKP_VERSION );
		if( version_compare( HMBKP_VERSION, $plugin->version, '>' ) < 0 )
			$hmbkp_contextual_help .= sprintf( __('<p><strong>Help for version <em>%s</em>.</strong> Looks like you are using an older version <em>(%s)</em> &ndash; this information may not be up to date. Please check the readme.txt file.</p>', 'hmbkp'), $plugin->version, HMBKP_VERSION );
		
		$hmbkp_contextual_help .= $plugin->sections['faq'];
		
	}
	return $hmbkp_contextual_help . __( '<p><strong>For more information:</strong><p/>', 'hmbkp' ) . $contextual_help;
}
add_filter( 'contextual_help', 'hmbkp_contextual_help', 10, 3 );
 