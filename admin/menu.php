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
	require_once( HMBKP_PLUGIN_PATH . '/admin/page.php' );
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
		array_push( $links, '<a href="tools.php?page=' . esc_attr( HMBKP_PLUGIN_SLUG ) . '">' . __( 'Backups', 'hmbkp' ) . '</a>' );

	return $links;

}
add_filter( 'plugin_action_links', 'hmbkp_plugin_action_link', 10, 2 );

/**
 * Add Contextual Help to Backups tools page.
 *
 * Help is pulled from the readme FAQ.
 *
 * @return null
 */
function hmbkp_contextual_help() {

	// Pre WordPress 3.3 compat
	if ( ! method_exists( get_current_screen(), 'add_help_tab' ) )
		return;

	require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

	if ( ! $plugin = get_transient( 'hmbkp_plugin_data' ) ) {

		$plugin = plugins_api( 'plugin_information', array( 'slug' => HMBKP_PLUGIN_SLUG ) );

		// Cache for one day
		set_transient( 'hmbkp_plugin_data', $plugin, 86400 );

	}

	$warning = '';

	// Check if help is for the right version.
	if ( ! empty( $plugin->version ) && version_compare( HMBKP_VERSION, $plugin->version, '!=' ) )
	    $warning = sprintf( '<div id="message" class="updated inline"><p><strong>' . __( 'You are not using the latest stable version of BackUpWordPress', 'hmbkp' ) . '</strong> &mdash; ' . __( 'The information below is for version %1$s. View the %2$s file for help specific to version %3$s.', 'hmbkp' ) . '</p></div>', '<code>' . esc_attr( $plugin->version ) . '</code>', '<code>readme.txt</code>', '<code>' . esc_attr( HMBKP_VERSION ) . '</code>' );

	ob_start();
	require_once( HMBKP_PLUGIN_PATH . '/admin/constants.php' );
	$constants = ob_get_clean();


	if ( $plugin && ! is_wp_error( $plugin ) )
		get_current_screen()->add_help_tab( array( 'title' => __( 'FAQ', 'hmbkp' ), 'id' => 'hmbkp_faq', 'content' => wp_kses_post( $warning . $plugin->sections['faq'] ) ) );
	
	get_current_screen()->add_help_tab( array( 'title' => __( 'Constants', 'hmbkp' ), 'id' => 'hmbkp_constants', 'content' => wp_kses_post( $constants ) ) );

	get_current_screen()->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'hmbkp' ) . '</strong></p>' .
		'<p><a href="https://github.com/humanmade/backupwordpress" target="_blank">GitHub</a></p>' .
		'<p><a href="http://wordpress.org/tags/backupwordpress?forum_id=10" target="_blank">' . __( 'Support Forums', 'hmbkp' ) .'</a></p>' .
		'<p><a href="http://translate.hmn.md/" target="_blank">' . __( 'Help with translation', 'hmbkp' ) .'</a></p>'
	);

}
add_filter( 'load-tools_page_' . HMBKP_PLUGIN_SLUG, 'hmbkp_contextual_help' );