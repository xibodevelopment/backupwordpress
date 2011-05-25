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

/**
 *	Add Contextual Help to Backups tools page.
 *
 *	Help is pulled from the readme FAQ.
 *	NOTE: FAQ used is from the wordpress repo, and might not be up to date for development versions.
 *
 */
 function hmbkp_contextual_help( $contextual_help, $screen_id, $screen ) {

	if ( isset( $_GET['page'] ) && $_GET['page'] == HMBKP_PLUGIN_SLUG ) :

		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		$plugin = plugins_api( 'plugin_information', array( 'slug' => 'backupwordpress' ) );

		// Check if help is for the right version.
		if ( version_compare( HMBKP_VERSION, $plugin->version, '!=' ) )
			$contextual_help = sprintf( '<p><strong>' . __( 'You are not using the latest stable version of BackUpWordPress', 'hmbkp' ) . '</strong>' . __( ' &mdash; The information below is for version %s. View the readme.txt file for help specific to version %s.', 'hmbkp' ) . '</p>', '<code>' . $plugin->version . '</code>', '<code>' . HMBKP_VERSION . '</code>' );

		$contextual_help .= $plugin->sections['faq'];

	endif;

	return $contextual_help;

}
add_filter( 'contextual_help', 'hmbkp_contextual_help', 10, 3 );
