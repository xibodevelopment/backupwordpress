<?php

/*
Plugin Name: BackUpWordPress
Plugin URI: http://bwp.hmn.md/
Description: Simple automated backups of your WordPress powered website. Once activated you'll find me under <strong>Tools &rarr; Backups</strong>.
Author: Human Made Limited
 * Version: 3.0.2
Author URI: http://hmn.md/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: hmbkp
Domain Path: /languages
Network: true
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

if ( ! defined( 'HMBKP_REQUIRED_PHP_VERSION' ) ) {
	define( 'HMBKP_REQUIRED_PHP_VERSION', '5.3.2' );
}

hmbkp_maybe_self_deactivate();

if ( ! defined( 'HMBKP_PLUGIN_SLUG' ) ) {
	define( 'HMBKP_PLUGIN_SLUG', basename( dirname( __FILE__ ) ) );
}

if ( ! defined( 'HMBKP_PLUGIN_PATH' ) ) {
	define( 'HMBKP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'HMBKP_PLUGIN_URL' ) ) {
	define( 'HMBKP_PLUGIN_URL', plugin_dir_url(  __FILE__  ) );
}

define( 'HMBKP_PLUGIN_LANG_DIR', apply_filters( 'hmbkp_filter_lang_dir', HMBKP_PLUGIN_SLUG . '/languages/' ) );

if ( ! defined( 'HMBKP_ADMIN_URL' ) ) {

	if ( is_multisite() ) {
		define( 'HMBKP_ADMIN_URL', add_query_arg( 'page', HMBKP_PLUGIN_SLUG, network_admin_url( 'settings.php' ) ) );

	} else {
		define( 'HMBKP_ADMIN_URL', add_query_arg( 'page', HMBKP_PLUGIN_SLUG, admin_url( 'tools.php' ) ) );
	}

}

$key = array( ABSPATH, time() );

foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT', 'SECRET_KEY' ) as $constant ) {

	if ( defined( $constant ) ) {
		$key[] = constant( $constant );
	}

}

shuffle( $key );

define( 'HMBKP_SECURE_KEY', md5( serialize( $key ) ) );

if ( ! defined( 'HMBKP_REQUIRED_WP_VERSION' ) ) {
	define( 'HMBKP_REQUIRED_WP_VERSION', '3.9.3' );
}

// Max memory limit isn't defined in old versions of WordPress
if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) ) {
	define( 'WP_MAX_MEMORY_LIMIT', '256M' );
}

if ( ! defined( 'HMBKP_ADMIN_PAGE' ) ) {

	if ( is_multisite() ) {
		define( 'HMBKP_ADMIN_PAGE', 'settings_page_' . HMBKP_PLUGIN_SLUG );

	} else {
		define( 'HMBKP_ADMIN_PAGE', 'tools_page_' . HMBKP_PLUGIN_SLUG );
	}

}

require_once( HMBKP_PLUGIN_PATH . 'classes/class-notices.php' );

// Load the admin menu
require_once( HMBKP_PLUGIN_PATH . 'admin/menu.php' );
require_once( HMBKP_PLUGIN_PATH . 'admin/actions.php' );

// Load hm-backup
if ( ! class_exists( 'HM_Backup' ) )
	require_once( HMBKP_PLUGIN_PATH . 'hm-backup/hm-backup.php' );

// Load Backdrop
require_once( HMBKP_PLUGIN_PATH . 'backdrop/hm-backdrop.php' );

// Load the schedules
require_once( HMBKP_PLUGIN_PATH . 'classes/class-schedule.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-schedules.php' );

// Load the core functions
require_once( HMBKP_PLUGIN_PATH . 'functions/core.php' );
require_once( HMBKP_PLUGIN_PATH . 'functions/interface.php' );

// Load Services
require_once( HMBKP_PLUGIN_PATH . 'classes/class-services.php' );

// Load the email service
require_once( HMBKP_PLUGIN_PATH . 'classes/class-email.php' );

// Load the webhook services
require_once( HMBKP_PLUGIN_PATH . 'classes/class-webhooks.php' );
require_once( HMBKP_PLUGIN_PATH . 'classes/class-webhook-wpremote.php' );

// Load the wp cli command
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include( HMBKP_PLUGIN_PATH . 'classes/wp-cli.php' );
}

// Hook in the activation and deactivation actions
register_activation_hook( HMBKP_PLUGIN_SLUG . '/backupwordpress.php', 'hmbkp_activate' );
register_deactivation_hook( HMBKP_PLUGIN_SLUG . '/backupwordpress.php', 'hmbkp_deactivate' );

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
	if ( HMBKP_VERSION != get_option( 'hmbkp_plugin_version' ) ) {
		hmbkp_update();
	}

}
add_action( 'admin_init', 'hmbkp_init' );

/**
 * Enqueue plugin scripts
 */
function hmbkp_load_scripts() {

	$js_file = HMBKP_PLUGIN_URL . 'assets/hmbkp.min.js';

	if ( WP_DEBUG ) {
		$js_file = HMBKP_PLUGIN_URL . 'assets/hmbkp.js';
	}

	wp_enqueue_script( 'hmbkp', $js_file, array( 'heartbeat' ), sanitize_key( HMBKP_VERSION ) );

	wp_localize_script(
		'hmbkp',
		'hmbkp',
		array(
			'page_slug'    => HMBKP_PLUGIN_SLUG,
			'nonce'         		   => wp_create_nonce( 'hmbkp_nonce' ),
			'hmbkp_run_schedule_nonce' => wp_create_nonce( 'hmbkp_run_schedule' ),
			'update'				   => __( 'Update', 'backupwordpress' ),
			'cancel'				   => __( 'Cancel', 'backupwordpress' ),
			'delete_schedule'		   => __( 'Are you sure you want to delete this schedule? All of it\'s backups will also be deleted.', 'backupwordpress' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'backupwordpress' ) . "\n",
			'delete_backup'			   => __( 'Are you sure you want to delete this backup?', 'backupwordpress' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'backupwordpress' ) . "\n",
			'remove_exclude_rule'	   => __( 'Are you sure you want to remove this exclude rule?', 'backupwordpress' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'backupwordpress' ) . "\n",
			'remove_old_backups'	   => __( 'Reducing the number of backups that are stored on this server will cause some of your existing backups to be deleted, are you sure that\'s what you want?', 'backupwordpress' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'backupwordpress' ) . "\n"
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

	if ( ! get_option( 'hmbkp_enable_support' ) ) {
		return;
	}

	require_once HMBKP_PLUGIN_PATH . 'classes/class-requirements.php';

	foreach ( HMBKP_Requirements::get_requirement_groups() as $group ) {

		foreach ( HMBKP_Requirements::get_requirements( $group ) as $requirement ) {

			$info[ $requirement->name() ] = $requirement->result();

		}

	}

	foreach ( HMBKP_Services::get_services() as $file => $service ) {
		array_merge( $info, call_user_func( array( $service, 'intercom_data' ) ) );
	}

	$current_user = wp_get_current_user();

	$info['user_hash'] = hash_hmac( 'sha256', $current_user->user_email, 'fcUEt7Vi4ym5PXdcr2UNpGdgZTEvxX9NJl8YBTxK' );
	$info['email'] = $current_user->user_email;
	$info['created_at'] = strtotime( $current_user->user_registered );
	$info['app_id'] = '7f1l4qyq';
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

	$css_file = HMBKP_PLUGIN_URL . 'assets/hmbkp.min.css';

	if ( WP_DEBUG ) {
		$css_file = HMBKP_PLUGIN_URL . 'assets/hmbkp.css';
	}

	wp_enqueue_style( 'hmbkp', $css_file, false, sanitize_key( HMBKP_VERSION ) );

}
add_action( 'admin_print_styles-' . HMBKP_ADMIN_PAGE, 'hmbkp_load_styles' );

/**
 * Function to run when the schedule cron fires
 * @param $schedule_id
 */
function hmbkp_schedule_hook_run( $schedule_id ) {

	$schedules = HMBKP_Schedules::get_instance();
	$schedule  = $schedules->get_schedule( $schedule_id );

	if ( ! $schedule ) {
		return;
	}

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

	require_once( HMBKP_PLUGIN_PATH . 'classes/class-requirements.php' );

	ob_start();
	require_once( 'admin/server-info.php' );
	$info = ob_get_clean();

	get_current_screen()->add_help_tab(
		array(
			'title' => __( 'Server Info', 'backupwordpress' ),
			'id' => 'hmbkp_server',
			'content' => $info,
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

/**
 * Determine if the installation meets the PHP version requirements.
 */
function hmbkp_maybe_self_deactivate() {

	if ( ! function_exists( 'deactivate_plugins' ) || ! function_exists( 'current_action' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	// Don't activate on anything less than PHP required version
	if ( version_compare( phpversion(), HMBKP_REQUIRED_PHP_VERSION, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( 'plugins_loaded' === current_action() ) {
			add_action( 'admin_notices', 'hmbkp_display_admin_notices' );
		} else {
			wp_die( hmbkp_get_notice_message(), __( 'BackUpWordPress', 'backupwordpress' ), array( 'back_link' => true ) );
		}
	}

}
add_action( 'plugins_loaded', 'hmbkp_maybe_self_deactivate' );

/**
 * Displays a message as notice in the admin.
 */
function hmbkp_display_admin_notices() {

	echo '<div class="error"><p>' . hmbkp_get_notice_message() . '</p></div>';

}

/**
 * Returns a localized user friendly error message.
 *
 * @return string
 */
function hmbkp_get_notice_message() {

	return sprintf(
		__( 'BackUpWordPress requires PHP version %1$s or later. It is not active. %2$s%3$s%4$sLearn more%5$s', 'backupwordpress' ),
		HMBKP_REQUIRED_PHP_VERSION,
		'<a href="',
		'https://bwp.hmn.md/unsupported-php-version-error/',
		'">',
		'</a>'
	);
}
