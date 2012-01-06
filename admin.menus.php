<?php

/**
 * Add the backups menu item
 * to the tools menu
 *
 * @return null
 */
function hmbkp_admin_menu() {
	add_management_page( __( 'Manage Backups','hmbkp' ), __( 'Backups','hmbkp' ), ( defined( 'HMBKP_CAPABILITY' ) && HMBKP_CAPABILITY ) ? HMBKP_CAPABILITY : 'manage_options', HMBKP_PLUGIN_SLUG, 'hmbkp_manage_backups' );
}
add_action( 'admin_menu', 'hmbkp_admin_menu' );

/**
 * Load the backups admin page
 * when the menu option is clicked
 *
 * @return null
 */
function hmbkp_manage_backups() {
	require_once( HMBKP_PLUGIN_PATH . '/admin.page.php' );
}

/**
 * Add a link to the backups page to the plugin action links.
 *
 * @param array $links
 * @param string $file
 * @return array $links
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
 *
 * @return null
 */
function hmbkp_contextual_help() {

	require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	$plugin = plugins_api( 'plugin_information', array( 'slug' => 'backupwordpress' ) );
	
	$warning = '';

	// Check if help is for the right version.
	if ( ! empty( $plugin->version ) && version_compare( HMBKP_VERSION, $plugin->version, '!=' ) )
	    $warning = sprintf( '<div id="message" class="updated inline"><p><strong>' . __( 'You are not using the latest stable version of BackUpWordPress', 'hmbkp' ) . '</strong>' . __( ' &mdash; The information below is for version %s. View the readme.txt file for help specific to version %s.', 'hmbkp' ) . '</p></div>', '<code>' . $plugin->version . '</code>', '<code>' . HMBKP_VERSION . '</code>' );

	ob_start();
	require_once( HMBKP_PLUGIN_PATH . '/admin.constants.php' );
	$constants = ob_get_clean();
	
	// Pre WordPress 3.3 compat
	if ( ! method_exists( get_current_screen(), 'add_help_tab' ) )
		return;

	get_current_screen()->add_help_tab( array( 'title' => 'FAQ', 'id' => 'hmbkp_faq', 'content' => $warning . $plugin->sections['faq'] ) );
	get_current_screen()->add_help_tab( array( 'title' => 'Constants', 'id' => 'hmbkp_constants', 'content' => $warning . $constants ) );

	get_current_screen()->set_help_sidebar(
		'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
		'<p>' . __( '<a href="http://https://github.com/humanmade/backupwordpress" target="_blank">github</a>' ) . '</p>' .
		'<p>' . __( '<a href="http://wordpress.org/tags/backupwordpress?forum_id=10" target="_blank">Support Forums</a>' ) . '</p>'
	);

}
add_filter( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_contextual_help' );