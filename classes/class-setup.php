<?php

defined( 'ABSPATH' ) or exit;

/**
 * Class BackUpWordPress_Setup
 */
class BackUpWordPress_Setup {

	/**
	 * Defines the minimum version of WordPress required by BWP.
	 */
	const BWP_MIN_WP_VERSION = '3.9.3';

	/**
	 * Defines the minimum version of PHP required by BWP.
	 */
	const BWP_MIN_PHP_VERSION = '5.3.2';

	/**
	 * Setup the plugin defaults on activation
	 */
	public static function activate() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! self::meets_requirements() ) {

			wp_die( self::get_notice_message(), __( 'BackUpWordPress', 'backupwordpress' ), array( 'back_link' => true ) );

		}

		// loads the translation files
		load_plugin_textdomain( 'backupwordpress', false, HMBKP_PLUGIN_LANG_DIR );

		// Run deactivate on activation in-case it was deactivated manually
		self::deactivate();

	}

	/**
	 * Cleanup on plugin deactivation
	 *
	 * Removes options and clears all cron schedules
	 */
	public static function deactivate() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Determine if we need to do any cleanup
		if ( ! class_exists( 'HMBKP_Schedules' ) ) {
			return;
		}

		$schedules = HMBKP_Schedules::get_instance();

		if ( empty( $schedules ) ) {
			return;
		}

		// Clean up the backups directory
		hmbkp_cleanup();

		// Clear schedule crons
		foreach ( $schedules->get_schedules() as $schedule ) {
			$schedule->unschedule();
		}

		// Opt them out of support
		delete_option( 'hmbkp_enable_support' );

		// Remove the directory filesize cache
		delete_transient( 'hmbkp_directory_filesizes' );

	}

	/**
	 * Deactivate BackUpWordPress.
	 */
	public static function self_deactivate() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		deactivate_plugins( dirname( __DIR__ ) . '/backupwordpress.php' );

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

	}

	/**
	 * Determine if this WordPress install meets the minimum requirements for BWP to run.
	 *
	 * @return bool
	 */
	public static function meets_requirements() {

		return ( self::is_supported_php_version() && self::is_supported_wp_version() ) ;

	}

	/**
	 * Checks the current PHP version against the required version.
	 *
	 * @return mixed
	 */
	protected static function is_supported_php_version() {

		return version_compare( phpversion(), self::BWP_MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Checks the current WordPress version against the required version.
	 *
	 * @return mixed
	 */
	protected static function is_supported_wp_version() {

		return version_compare( get_bloginfo( 'version' ), self::BWP_MIN_WP_VERSION, '>=' );
	}

	/**
	 * Displays a user friendly message in the WordPress admin.
	 */
	public static function display_admin_notices() {

		echo '<div class="error"><p>' . self::get_notice_message() . '</p></div>';

	}

	/**
	 * Returns a localized user friendly error message.
	 *
	 * @return string
	 */
	public static function get_notice_message() {

		return sprintf(
			__( 'BackUpWordPress requires PHP version %1$s or later and WordPress version %2$s or later to run. It has not been activated. %3$s%4$s%5$sLearn more%6$s', 'backupwordpress' ),
			self::BWP_MIN_PHP_VERSION,
			self::BWP_MIN_WP_VERSION,
			'<a href="',
			'https://bwp.hmn.md/unsupported-php-version-error/',
			'">',
			'</a>'
		);
	}

}
