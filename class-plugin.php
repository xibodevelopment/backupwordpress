<?php

defined( 'WPINC' ) or die;

/**
 * Class BackUpWordPress_Plugin
 */
class BackUpWordPress_Plugin {

	/**
	 * Simple factory method to get an instance of this class
	 *
	 * @return BackUpWordPress_Plugin
	 */
	public static function get_instance() {

		return new self;
	}

	/**
	 * Creates an instance of this class
	 */
	public function __construct() {

		$this->constants();

		$this->includes();

		// Handle any advanced option changes
		hmbkp_constant_changes();

		add_action( 'plugins_loaded', array( $this, 'init' ) );

		add_action( 'activated_plugin', array( $this, 'load_first' ) );

		add_action( 'admin_init', array( $this, 'update' ) );

	}

	/**
	 * Sets up all the action hooks and initializes constants. Includes.
	 */
	public function init() {

		add_action( 'admin_print_scripts-' . HMBKP_ADMIN_PAGE, array( $this, 'load_scripts' ) );

		add_action( 'admin_footer-' . HMBKP_ADMIN_PAGE, array( $this, 'load_intercom_script' ) );

		add_action( 'admin_print_styles-' . HMBKP_ADMIN_PAGE, array( $this, 'load_styles' ) );

		add_action( 'hmbkp_schedule_hook', array( $this, 'schedule_hook_run' ) );

		$this->plugin_textdomain();

		add_action( 'load-' . HMBKP_ADMIN_PAGE, array( $this, 'display_server_info_tab' ) );

	}

	/**
	 * Defines the plugin constants
	 */
	public function constants() {

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
			define( 'HMBKP_REQUIRED_WP_VERSION', '3.7.1' );

		// Max memory limit isn't defined in old versions of WordPress
		if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) )
			define( 'WP_MAX_MEMORY_LIMIT', '256M' );

		if ( ! defined( 'HMBKP_SCHEDULE_TIME' ) )
			define( 'HMBKP_SCHEDULE_TIME', '11pm' );

		// Save plugin settings page for later use
		if ( ! defined( 'HMBKP_ADMIN_PAGE' ) ) {

			if ( is_multisite() )
				define( 'HMBKP_ADMIN_PAGE', 'settings_page_' . HMBKP_PLUGIN_SLUG );
			else
				define( 'HMBKP_ADMIN_PAGE', 'tools_page_' . HMBKP_PLUGIN_SLUG );

		}

	}

	/**
	 * Loads included scripts and classes
	 */
	public function includes() {

		// Load the admin menu
		require_once( HMBKP_PLUGIN_PATH . 'admin/menu.php' );
		require_once( HMBKP_PLUGIN_PATH . '/admin/actions.php' );

		// Load the schedules
		require_once( HMBKP_PLUGIN_PATH . '/classes/class-schedule.php' );
		require_once( HMBKP_PLUGIN_PATH . '/classes/class-schedules.php' );

		// Load the core functions
		require_once(  HMBKP_PLUGIN_PATH . '/functions/core.php' );
		require_once(  HMBKP_PLUGIN_PATH . '/functions/interface.php' );

		// Load Services
		require_once( HMBKP_PLUGIN_PATH . '/classes/class-services.php' );

		// Load the email service
		require_once( HMBKP_PLUGIN_PATH . '/classes/class-email.php' );

		// Load the wp cli command
		if ( defined( 'WP_CLI' ) && WP_CLI )
			include( HMBKP_PLUGIN_PATH . '/classes/wp-cli.php' );

	}

	/**
	 * Checks active plugin version and runs update script if needed
	 */
	public function update() {

		$plugin_data = get_plugin_data( __FILE__ );

		// define the plugin version
		define( 'HMBKP_VERSION', $plugin_data['Version'] );

		// Fire the update action
		if ( HMBKP_VERSION != get_option( 'hmbkp_plugin_version' ) )
			hmbkp_update();
	}

	/**
	 * Loads the Javascript used by the plugin
	 */
	function load_scripts() {

		wp_enqueue_script( 'hmbkp-colorbox', HMBKP_PLUGIN_URL . 'assets/colorbox/jquery.colorbox-min.js', array( 'jquery', 'jquery-ui-tabs' ), sanitize_title( HMBKP_VERSION ) );

		wp_enqueue_script( 'hmbkp', HMBKP_PLUGIN_URL . 'assets/hmbkp.js', array( 'hmbkp-colorbox' ), sanitize_title( HMBKP_VERSION ) );

		wp_localize_script(
			'hmbkp',
			'hmbkp',
			array(
				'page_slug'    => HMBKP_PLUGIN_SLUG,
				'nonce'         		=> wp_create_nonce( 'hmbkp_nonce' ),
				'update'				=> __( 'Update', 'backupwordpress' ),
				'cancel'				=> __( 'Cancel', 'backupwordpress' ),
				'delete_schedule'		=> __( 'Are you sure you want to delete this schedule? All of it\'s backups will also be deleted.', 'backupwordpress' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'backupwordpress' ) . "\n",
				'delete_backup'			=> __( 'Are you sure you want to delete this backup?', 'backupwordpress' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'backupwordpress' ) . "\n",
				'remove_exclude_rule'	=> __( 'Are you sure you want to remove this exclude rule?', 'backupwordpress' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'backupwordpress' ) . "\n",
				'remove_old_backups'	=> __( 'Reducing the number of backups that are stored on this server will cause some of your existing backups to be deleted, are you sure that\'s what you want?', 'backupwordpress' ) . "\n\n" . __( '\'Cancel\' to go back, \'OK\' to delete.', 'backupwordpress' ) . "\n"
			)
		);

	}

	/**
	 * Loads Intercom support
	 */
	function load_intercom_script() {

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

	/**
	 * Injects the plugin styles
	 */
	function load_styles(){

		wp_enqueue_style( 'hmbkp_colorbox', HMBKP_PLUGIN_URL . 'assets/colorbox/example1/colorbox.css', false, HMBKP_VERSION );
		wp_enqueue_style( 'hmbkp', HMBKP_PLUGIN_URL . 'assets/hmbkp.css', false, HMBKP_VERSION );

	}

	/**
	 * Function to run when the schedule cron fires
	 * @param $schedule_id
	 */
	function schedule_hook_run( $schedule_id ) {

		$schedules = HMBKP_Schedules::get_instance();
		$schedule  = $schedules->get_schedule( $schedule_id );

		if ( ! $schedule )
			return;

		$schedule->run();

	}

	/**
	 * Loads the plugin text domain for translation
	 * This setup allows a user to just drop his custom translation files into the WordPress language directory
	 * Files will need to be in a subdirectory with the name of the textdomain 'backupwordpress'
	 */
	function plugin_textdomain() {

		// Set unique textdomain string
		$textdomain = 'backupwordpress';

		// The 'plugin_locale' filter is also used by default in load_plugin_textdomain()
		$locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );

		// Set filter for WordPress languages directory
		$hmbkp_wp_lang_dir = apply_filters( 'hmbkp_do_filter_wp_lang_dir', trailingslashit( WP_LANG_DIR ) . trailingslashit( $textdomain )  . $textdomain . '-' . $locale . '.mo' );

		// Translations: First, look in WordPress' "languages" folder = custom & update-secure!
		load_textdomain( $textdomain, $hmbkp_wp_lang_dir );

		// Translations: Secondly, look in plugin's "languages" folder = default
		load_plugin_textdomain( $textdomain, false, HMBKP_PLUGIN_LANG_DIR );

	}

	/**
	 * Displays the server info in the Help tab
	 */
	function display_server_info_tab() {

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

	/**
	 * Ensure BackUpWordPress is loaded before addons
	 */
	function load_first() {

		$active_plugins = get_option( 'active_plugins' );

		$plugin_path = plugin_basename( __FILE__ );

		$key = array_search( $plugin_path, $active_plugins );

		if ( $key > 0 ) {

			array_splice( $active_plugins, $key, 1 );

			array_unshift( $active_plugins, $plugin_path );

			update_option( 'active_plugins', $active_plugins );

		}

	}

}
