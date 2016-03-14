<?php
/**
 * Class BackUpWordPress_Setup
 */
class HMBKP_Setup {

	/**
	 * Defines the minimum version of WordPress required by BWP.
	 */
	const MIN_WP_VERSION = '3.9';

	/**
	 * Defines the minimum version of PHP required by BWP.
	 */
	const MIN_PHP_VERSION = '5.3.2';

	/**
	 * Setup the plugin defaults on activation
	 */
	public static function activate() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// loads the translation files for the Error message in the wp_die call.
		load_plugin_textdomain( 'backupwordpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		if ( ! self::meets_requirements() ) {

			wp_die( self::get_notice_message(), __( 'BackUpWordPress', 'backupwordpress' ), array( 'back_link' => true ) );

		}

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

		self::delete_schedules();

		self::delete_transients();

	}

	/**
	 * Deletes the backup schedule database entries and WP Cron entries.
	 */
	public static function delete_schedules() {

		// Delete Cron schedules.
		global $wpdb;

		$schedules = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", 'hmbkp_schedule_%' ) );

		foreach ( array_map( array( 'self', 'trim_prefix' ), $schedules ) as $item ) {
			wp_clear_scheduled_hook( 'hmbkp_schedule_hook', array( 'id' => $item ) );
		}
	}

	public static function trim_prefix( $item ) {
		return ltrim( $item, 'hmbkp_schedule_' );
	}

	/**
	 * Deletes the plugin's transients from the database.
	 */
	public static function delete_transients() {

		// Delete all transients
		$transients = array(
			'hmbkp_plugin_data',
			'hmbkp_directory_filesizes',
			'hmbkp_directory_filesizes_running',
			'hmbkp_wp_cron_test_beacon',
			'hm_backdrop',
		);

		array_map( 'delete_transient', $transients );
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

		deactivate_plugins( dirname( dirname( __FILE__ ) ) . '/backupwordpress.php' );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

	}

	/**
	 * Determine if this WordPress install meets the minimum requirements for BWP to run.
	 *
	 * @return bool
	 */
	public static function meets_requirements() {

		if ( false === self::is_supported_php_version() ) {
			return false;
		}

		if ( false === self::is_supported_wp_version() ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks the current PHP version against the required version.
	 *
	 * @return bool 'Operator' parameter specified, returns a boolean.
	 */
	protected static function is_supported_php_version() {

		return version_compare( phpversion(), self::MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Checks the current WordPress version against the required version.
	 *
	 * @return bool 'Operator' parameter specified, returns a boolean.
	 */
	protected static function is_supported_wp_version() {

		return version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '>=' );
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
			self::MIN_PHP_VERSION,
			self::MIN_WP_VERSION,
			'<a href="',
			'https://bwp.hmn.md/unsupported-php-version-error/',
			'">',
			'</a>'
		);
	}
}
