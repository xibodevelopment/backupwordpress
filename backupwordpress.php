<?php

/*
Plugin Name: BackUpWordPress
Plugin URI: http://bwp.hmn.md/
Description: Simple automated backups of your WordPress powered website. Once activated you'll find me under <strong>Tools &rarr; Backups</strong>.
Author: Human Made Limited
Version: 2.6.2
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

if ( ! defined( 'HMBKP_PLUGIN_SLUG' ) )
	define( 'HMBKP_PLUGIN_SLUG', basename( dirname( __FILE__ ) ) );

if ( ! defined( 'HMBKP_PLUGIN_PATH' ) )
	define( 'HMBKP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'HMBKP_PLUGIN_URL' ) )
	define( 'HMBKP_PLUGIN_URL', plugin_dir_url(  __FILE__  ) );

define( 'HMBKP_PLUGIN_LANG_DIR', apply_filters( 'hmbkp_filter_lang_dir', HMBKP_PLUGIN_SLUG . '/languages/' ) );

if ( ! defined( 'HMBKP_ADMIN_URL' ) ) {
	if ( is_multisite() )
		define( 'HMBKP_ADMIN_URL', add_query_arg( 'page', HMBKP_PLUGIN_SLUG, network_admin_url( 'settings.php' ) ) );
	else
		define( 'HMBKP_ADMIN_URL', add_query_arg( 'page', HMBKP_PLUGIN_SLUG, admin_url( 'tools.php' ) ) );
}

$key = array( ABSPATH, time() );

foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT', 'SECRET_KEY' ) as $constant )
	if ( defined( $constant ) )
		$key[] = constant( $constant );

shuffle( $key );

define( 'HMBKP_SECURE_KEY', md5( serialize( $key ) ) );

if ( ! defined( 'HMBKP_REQUIRED_WP_VERSION' ) )
	define( 'HMBKP_REQUIRED_WP_VERSION', '3.7.3' );

// Max memory limit isn't defined in old versions of WordPress
if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) )
	define( 'WP_MAX_MEMORY_LIMIT', '256M' );

if ( ! defined( 'HMBKP_ADMIN_PAGE' ) ) {

	if ( is_multisite() )
		define( 'HMBKP_ADMIN_PAGE', 'settings_page_' . HMBKP_PLUGIN_SLUG );
	else
		define( 'HMBKP_ADMIN_PAGE', 'tools_page_' . HMBKP_PLUGIN_SLUG );

}

// Load the admin menu
require_once( HMBKP_PLUGIN_PATH . '/admin/menu.php' );
require_once( HMBKP_PLUGIN_PATH . '/admin/actions.php' );

// Load hm-backup
if ( ! class_exists( 'HM_Backup' ) )
	require_once( HMBKP_PLUGIN_PATH . '/hm-backup/hm-backup.php' );

// Load the schedules
require_once( HMBKP_PLUGIN_PATH . '/classes/class-schedule.php' );
require_once( HMBKP_PLUGIN_PATH . '/classes/class-schedules.php' );

// Load the core functions
require_once( HMBKP_PLUGIN_PATH . '/functions/core.php' );
require_once( HMBKP_PLUGIN_PATH . '/functions/interface.php' );

// Load Services
require_once( HMBKP_PLUGIN_PATH . '/classes/class-services.php' );

// Load the email service
require_once( HMBKP_PLUGIN_PATH . '/classes/class-email.php' );

// Load the wp cli command
if ( defined( 'WP_CLI' ) && WP_CLI )
	include( HMBKP_PLUGIN_PATH . '/classes/wp-cli.php' );

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

// Handle any advanced option changes
hmbkp_constant_changes();

/**
 * Plugin setup
 *
 * @return null
 */
function hmbkp_init() {

	$plugin_data = get_plugin_data( __FILE__ );

	// define the plugin version
	define( 'HMBKP_VERSION', $plugin_data['Version'] );

	// Fire the update action
	if ( HMBKP_VERSION != get_option( 'hmbkp_plugin_version' ) )
		hmbkp_update();

}
add_action( 'admin_init', 'hmbkp_init' );

/**
 * Enqueue plugin scripts
 */
function hmbkp_load_scripts() {

	wp_enqueue_script( 'hmbkp-colorbox', HMBKP_PLUGIN_URL . 'assets/colorbox/jquery.colorbox-min.js', array( 'jquery', 'jquery-ui-tabs' ), sanitize_title( HMBKP_VERSION ) );

	wp_enqueue_script( 'hmbkp', HMBKP_PLUGIN_URL . 'assets/hmbkp.js', array( 'hmbkp-colorbox' ), sanitize_title( HMBKP_VERSION ) );

	wp_localize_script(
		'hmbkp',
		'hmbkp',
		array(
			'page_slug'    => HMBKP_PLUGIN_SLUG,
			'nonce'         		=> wp_create_nonce( 'hmbkp_nonce' ),
			'update'				=> __( 'Update', 'hmbkp' ),
			'cancel'				=> __( 'Cancel', 'hmbkp' ),
			'delete_schedule'		=> __( 'Are you sure you want to delete this schedule? All of it\'s backups will also be deleted.', 'hmbkp' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'hmbkp' ) . "\n",
			'delete_backup'			=> __( 'Are you sure you want to delete this backup?', 'hmbkp' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'hmbkp' ) . "\n",
			'remove_exclude_rule'	=> __( 'Are you sure you want to remove this exclude rule?', 'hmbkp' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'hmbkp' ) . "\n",
			'remove_old_backups'	=> __( 'Reducing the number of backups that are stored on this server will cause some of your existing backups to be deleted, are you sure that\'s what you want?', 'hmbkp' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'hmbkp' ) . "\n"
		)
	);

}
add_action( 'admin_print_scripts-' . HMBKP_ADMIN_PAGE, 'hmbkp_load_scripts' );

/**
 * Load Intercom and send across user information and server info
 *
 * Only loaded if the user has opted in.
 *
 * @return void
 */
function hmbkp_load_intercom_script() {

	if ( ! get_option( 'hmbkp_enable_support' ) )
		return;

	require_once HMBKP_PLUGIN_PATH . 'classes/class-requirements.php';

	foreach ( HMBKP_Requirements::get_requirement_groups() as $group ) {

		foreach ( HMBKP_Requirements::get_requirements( $group ) as $requirement ) {

			$info[$requirement->name()] = $requirement->result();

		}

	}

	foreach ( HMBKP_Services::get_services() as $file => $service )
		array_merge( $info, call_user_func( array( $service, 'intercom_data' ) ) );

	$current_user = wp_get_current_user();

	$info['user_hash'] = hash_hmac( "sha256", $current_user->user_email, "fcUEt7Vi4ym5PXdcr2UNpGdgZTEvxX9NJl8YBTxK" );
	$info['email'] = $current_user->user_email;
	$info['created_at'] = strtotime( $current_user->user_registered );
	$info['app_id'] = "7f1l4qyq";
	$info['name'] = $current_user->display_name;
	$info['widget'] = array( 'activator' => '#intercom' ); ?>

	<script id="IntercomSettingsScriptTag">
		window.intercomSettings = <?php echo json_encode( $info ); ?>;
	</script>
	<script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://static.intercomcdn.com/intercom.v1.js';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}};})()</script>

<?php }
add_action( 'admin_footer-' . HMBKP_ADMIN_PAGE, 'hmbkp_load_intercom_script' );

/**
 * Enqueue the plugin styles
 */
function hmbkp_load_styles(){

	wp_enqueue_style( 'hmbkp_colorbox', HMBKP_PLUGIN_URL . 'assets/colorbox/example1/colorbox.css', false, HMBKP_VERSION );
	wp_enqueue_style( 'hmbkp', HMBKP_PLUGIN_URL . 'assets/hmbkp.css', false, HMBKP_VERSION );

}
add_action( 'admin_print_styles-' . HMBKP_ADMIN_PAGE, 'hmbkp_load_styles' );

/**
 * Function to run when the schedule cron fires
 * @param $schedule_id
 */
function hmbkp_schedule_hook_run( $schedule_id ) {

	$schedules = HMBKP_Schedules::get_instance();
	$schedule  = $schedules->get_schedule( $schedule_id );

	if ( ! $schedule )
		return;

	$schedule->run();

}
add_action( 'hmbkp_schedule_hook', 'hmbkp_schedule_hook_run' );

/**
 * Loads the plugin text domain for translation
 * This setup allows a user to just drop his custom translation files into the WordPress language directory
 * Files will need to be in a subdirectory with the name of the textdomain 'hmbkp'
 */
function hmbkp_plugin_textdomain() {

	// Set unique textdomain string
	$textdomain = 'hmbkp';

	// The 'plugin_locale' filter is also used by default in load_plugin_textdomain()
	$locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );

	// Set filter for WordPress languages directory
	$hmbkp_wp_lang_dir = apply_filters( 'hmbkp_do_filter_wp_lang_dir', trailingslashit( WP_LANG_DIR ) . trailingslashit( $textdomain )  . $textdomain . '-' . $locale . '.mo' );

	// Translations: First, look in WordPress' "languages" folder = custom & update-secure!
	load_textdomain( $textdomain, $hmbkp_wp_lang_dir );

	// Translations: Secondly, look in plugin's "languages" folder = default
	load_plugin_textdomain( $textdomain, false, HMBKP_PLUGIN_LANG_DIR );

}
add_action( 'init', 'hmbkp_plugin_textdomain', 1 );

/**
 * Displays the server info in the Help tab
 */
function hmbkp_display_server_info_tab() {

	require_once( HMBKP_PLUGIN_PATH . '/classes/class-requirements.php' );

	ob_start();
	require_once( 'admin/server-info.php' );
	$info = ob_get_clean();

	get_current_screen()->add_help_tab(
		array(
			'title' => __( 'Server Info', 'backupwordpress' ),
			'id' => 'hmbkp_server',
			'content' => $info
		)
	);

}
add_action( 'load-' . HMBKP_ADMIN_PAGE, 'hmbkp_display_server_info_tab' );

/**
 * Ensure BackUpWordPress is loaded before addons
 */
function hmbkp_load_first() {

	$active_plugins = get_option( 'active_plugins' );

	$plugin_path = plugin_basename( __FILE__ );

	$key = array_search( $plugin_path, $active_plugins );

	if ( $key > 0 ) {

		array_splice( $active_plugins, $key, 1 );

		array_unshift( $active_plugins, $plugin_path );

		update_option( 'active_plugins', $active_plugins );

	}

}
add_action( 'activated_plugin', 'hmbkp_load_first' );
