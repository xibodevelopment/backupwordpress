<?php

namespace HM\BackUpWordPress;

/**
 * Displays a row in the manage backups table
 *
 * @param string                 $file
 * @param Scheduled_Backup $schedule
 */
function get_backup_row( $file, Scheduled_Backup $schedule ) {

	$encoded_file = urlencode( base64_encode( $file ) );
	$offset       = get_option( 'gmt_offset' ) * 3600;

	?>

	<tr class="hmbkp_manage_backups_row">

		<th scope="row">
			<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), @filemtime( $file ) + $offset ) ); ?>
		</th>

		<td class="code">
			<?php echo esc_html( size_format( @filesize( $file ) ) ); ?>
		</td>

		<td><?php echo esc_html( human_get_type( $file, $schedule ) ); ?></td>

		<td>

			<?php if (  is_path_accessible( Path::get_path() )  ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'hmbkp_backup_archive' => $encoded_file, 'hmbkp_schedule_id' => $schedule->get_id(), 'action' => 'hmbkp_request_download_backup' ), admin_url( 'admin-post.php' ) ), 'hmbkp_download_backup', 'hmbkp_download_backup_nonce' ) ); ?>" class="download-action"><?php _e( 'Download', 'backupwordpress' ); ?></a> |
			<?php endif; ?>

			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'hmbkp_backup_archive' => $encoded_file, 'hmbkp_schedule_id' => $schedule->get_id(), 'action' => 'hmbkp_request_delete_backup' ), admin_url( 'admin-post.php' ) ), 'hmbkp_delete_backup', 'hmbkp_delete_backup_nonce' ) ); ?>" class="delete-action"><?php _e( 'Delete', 'backupwordpress' ); ?></a>

		</td>

	</tr>

<?php }

/**
 * Displays admin notices for various error / warning
 * conditions
 *
 * @return void
 */
function admin_notices() {

	$current_screen = get_current_screen();

	if ( ! isset( $current_screen ) ) {
		return;
	}

	$page = is_multisite() ? HMBKP_ADMIN_PAGE . '-network' : HMBKP_ADMIN_PAGE;
	if ( $current_screen->id !== $page ) {
		return;
	}

	$notices = Notices::get_instance()->get_notices();

	if ( empty( $notices ) ) {
		return;
	}

	ob_start(); ?>

	<?php if ( ! empty( $notices['backup_errors'] ) ) : ?>

		<div id="hmbkp-warning-backup" class="error notice is-dismissible">
			<p>
				<strong><?php _e( 'BackUpWordPress detected issues with your last backup.', 'backupwordpress' ); ?></strong>
			</p>

			<ul>

				<?php foreach ( $notices['backup_errors'] as $notice ) : ?>

					<li><pre><?php echo esc_html( $notice ); ?></pre></li>

				<?php endforeach; ?>

			</ul>

			<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'backupwordpress' ); ?></span></button>

		</div>

	<?php endif; ?>

	<?php if ( ! empty( $notices['server_config'] ) ) : ?>

		<div id="hmbkp-warning-server" class="error notice is-dismissible">

			<ul>

				<?php foreach ( $notices['server_config'] as $notice ) : ?>

					<li><?php echo wp_kses_data( $notice ); ?></li>

				<?php endforeach; ?>

			</ul>

			<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'backupwordpress' ); ?></span></button>

		</div>

	<?php endif; ?>

	<?php $notices = array_filter( $notices );

	if ( ! empty( $notices ) ) : ?>

		<?php foreach ( $notices as $key => $notice_type ) : ?>

			<?php if ( ! ( in_array( $key, array( 'server_config', 'backup_errors' ) ) ) ) : ?>

				<div id="hmbkp-warning-other" class="error notice is-dismissible">

					<?php foreach ( array_unique( $notice_type ) as $msg ) : ?>

						<p><?php echo wp_kses_data( $msg ); ?></p>

					<?php endforeach; ?>

					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'backupwordpress' ); ?></span></button>

				</div>

			<?php endif; ?>

		<?php endforeach; ?>

	<?php endif; ?>

	<?php echo ob_get_clean();

}
add_action( 'admin_notices', 'HM\BackUpWordPress\admin_notices' );
add_action( 'network_admin_notices', 'HM\BackUpWordPress\admin_notices' );

function set_server_config_notices() {

	$notices = Notices::get_instance();

	$messages = array();

	if ( ! is_dir( Path::get_path() ) ) {
		$messages[] = sprintf( __( 'The backups directory can\'t be created because your %s directory isn\'t writable. Please create the folder manually.', 'backupwordpress' ), '<code>' . esc_html( dirname( Path::get_path() ) ) . '</code>' );
	}

	if ( is_dir( Path::get_path() ) && ! wp_is_writable( Path::get_path() ) ) {
		$messages[] = __( 'The backups directory isn\'t writable. Please fix the permissions.', 'backupwordpress' );
	}

	if ( Backup_Utilities::is_safe_mode_on() ) {
		$messages[] = sprintf( __( '%1$s is running in %2$s, please contact your host and ask them to disable it. BackUpWordPress may not work correctly whilst %3$s is on.', 'backupwordpress' ), '<code>PHP</code>', sprintf( '<a href="%1$s">%2$s</a>', __( 'http://php.net/manual/en/features.safe-mode.php', 'backupwordpress' ), __( 'Safe Mode', 'backupwordpress' ) ), '<code>' . __( 'Safe Mode', 'backupwordpress' ) . '</code>' );
	}

	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH ) {

		// Suppress open_basedir warning https://bugs.php.net/bug.php?id=53041
		if ( ! path_in_php_open_basedir( HMBKP_PATH ) ) {
			$messages[] = sprintf( __( 'Your server has an %1$s restriction in effect and your custom backups directory (%2$s) is not within the allowed path(s): (%3$s).', 'backupwordpress' ), '<code>open_basedir</code>', '<code>' . esc_html( HMBKP_PATH ) . '</code>', '<code>' . esc_html( @ini_get( 'open_basedir' ) ) . '</code>' );

		} elseif ( ! file_exists( HMBKP_PATH ) ) {
			$messages[] = sprintf( __( 'Your custom path does not exist', 'backupwordpress' ) );

		} else {

			if ( ! is_dir( HMBKP_PATH ) ) {
				$messages[] = sprintf( __( 'Your custom backups directory %1$s doesn\'t exist and can\'t be created, your backups will be saved to %2$s instead.', 'backupwordpress' ), '<code>' . esc_html( HMBKP_PATH ) . '</code>', '<code>' . esc_html( Path::get_path() ) . '</code>' );
			}

			if ( is_dir( HMBKP_PATH ) && ! wp_is_writable( HMBKP_PATH ) ) {
				$messages[] = sprintf( __( 'Your custom backups directory %1$s isn\'t writable, new backups will be saved to %2$s instead.', 'backupwordpress' ), '<code>' . esc_html( HMBKP_PATH ) . '</code>', '<code>' . esc_html( Path::get_path() ) . '</code>' );

			}
		}
	}

	if ( ! is_readable( Path::get_root() ) ) {
		$messages[] = sprintf( __( 'Your site root path %s isn\'t readable.', 'backupwordpress' ), '<code>' . Path::get_root() . '</code>' );
	}

	if ( ! Requirement_Mysqldump_Command_Path::test() && ! Requirement_PDO::test() ) {
		$messages[] = sprintf( __( 'Your database cannot be backed up because your server doesn\'t support %1$s or %2$s. Please contact your host and ask them to enable them.', 'backupwordpress' ), '<code>mysqldump</code>', '<code>PDO</code>' );
	}

	if ( count( $messages ) > 0 ) {
		$notices->set_notices( 'server_config', $messages, false );
	}

}
add_action( 'admin_init', 'HM\BackUpWordPress\set_server_config_notices' );

/**
 * Hook in an change the plugin description when BackUpWordPress is activated
 *
 * @param array $plugins
 * @return array $plugins
 */
function plugin_row( $plugins ) {

	$menu = is_multisite() ? 'Settings' : 'Tools';

	if ( isset( $plugins[HMBKP_PLUGIN_SLUG . '/backupwordpress.php'] ) ) {
		$plugins[HMBKP_PLUGIN_SLUG . '/backupwordpress.php']['Description'] = str_replace( 'Once activated you\'ll find me under <strong>' . $menu . ' &rarr; Backups</strong>', 'Find me under <strong><a href="' . esc_url( get_settings_url() ) . '">' . $menu . ' &rarr; Backups</a></strong>', $plugins[HMBKP_PLUGIN_SLUG . '/backupwordpress.php']['Description'] );
	}

	return $plugins;

}

add_filter( 'all_plugins', 'HM\BackUpWordPress\plugin_row', 10 );

/**
 * Get the human readable backup type in.
 *
 * @access public
 * @param string                 $type
 * @param Scheduled_Backup $schedule (default: null)
 * @return string
 */
function human_get_type( $type, Scheduled_Backup $schedule = null ) {

	if ( strpos( $type, 'complete' ) !== false ) {
		return __( 'Database and Files', 'backupwordpress' );
	}

	if ( strpos( $type, 'file' ) !== false ) {
		return __( 'Files', 'backupwordpress' );
	}

	if ( strpos( $type, 'database' ) !== false ) {
		return __( 'Database', 'backupwordpress' );
	}

	if ( ! is_null( $schedule ) ) {
		return human_get_type( $schedule->get_type() );
	}

	return __( 'Legacy', 'backupwordpress' );

}

/**
 * Display the row of actions for a schedule
 *
 * @access public
 * @param Scheduled_Backup $schedule
 * @return void
 */
function schedule_status( Scheduled_Backup $schedule, $echo = true ) {

	$status = new Backup_Status( $schedule->get_id() );

	ob_start(); ?>

	<span class="hmbkp-status"<?php if ( $status->get_status() ) { ?> title="<?php printf( __( 'Started %s ago', 'backupwordpress' ), human_time_diff( $status->get_start_time() ) ); ?>"<?php } ?>>
		<?php echo $status->get_status() ? wp_kses_data( $status->get_status() ) : __( 'Starting backup...', 'backupwordpress' ); ?>
		<a href="<?php echo admin_action_url( 'request_cancel_backup', array( 'hmbkp_schedule_id' => $schedule->get_id() ) ); ?>"><?php _e( 'cancel', 'backupwordpress' ); ?></a>
	</span>

	<?php $output = ob_get_clean();

	if ( ! $echo ) {
		return $output;
	}

	echo $output;

}

function backups_number( Scheduled_Backup $schedule ) {

	$number = count( $schedule->get_backups() );

	if ( 0 === $number ) {
		$output = sprintf( __( 'No backups completed', 'backupwordpress' ) );
	} else {
		$output = sprintf( _nx( 'One backup completed', '%1$s backups completed', $number, 'backups count', 'backupwordpress' ), number_format_i18n( $number ) );
	}

	echo apply_filters( 'hmbkp_backups_number', $output, $number );
}

function translated_schedule_title( $slug, $title ) {

	$titles = array(
		'complete-hourly'      => esc_html__( 'Complete Hourly', 'backupwordpress' ),
		'file-hourly'          => esc_html__( 'File Hourly', 'backupwordpress' ),
		'database-hourly'      => esc_html__( 'Database Hourly', 'backupwordpress' ),
		'complete-twicedaily'  => esc_html__( 'Complete Twice Daily', 'backupwordpress' ),
		'file-twicedaily'      => esc_html__( 'File Twice Daily', 'backupwordpress' ),
		'database-twicedaily'  => esc_html__( 'Database Twice Daily', 'backupwordpress' ),
		'complete-daily'       => esc_html__( 'Complete Daily', 'backupwordpress' ),
		'file-daily'           => esc_html__( 'File Daily', 'backupwordpress' ),
		'database-daily'       => esc_html__( 'Database Daily', 'backupwordpress' ),
		'complete-weekly'      => esc_html__( 'Complete Weekly', 'backupwordpress' ),
		'file-weekly'          => esc_html__( 'File Weekly', 'backupwordpress' ),
		'database-weekly'      => esc_html__( 'Database Weekly', 'backupwordpress' ),
		'complete-fortnightly' => esc_html__( 'Complete Every Two Weeks', 'backupwordpress' ),
		'file-fortnightly'     => esc_html__( 'File Every Two Weeks', 'backupwordpress' ),
		'database-fortnightly' => esc_html__( 'Database Every Two Weeks', 'backupwordpress' ),
		'complete-monthly'     => esc_html__( 'Complete Monthly', 'backupwordpress' ),
		'file-monthly'         => esc_html__( 'File Monthly', 'backupwordpress' ),
		'database-monthly'     => esc_html__( 'Database Monthly', 'backupwordpress' ),
		'complete-manually'    => esc_html__( 'Complete Manually', 'backupwordpress' ),
		'file-manually'        => esc_html__( 'File Manually', 'backupwordpress' ),
		'database-manually'    => esc_html__( 'Database Manually', 'backupwordpress' )
	);

	if ( isset( $titles[ $slug ] ) ) {
		return $titles[ $slug ];
	}

	return $title;

}

function get_settings_url() {

	$url = is_multisite() ? network_admin_url( 'settings.php?page=' . HMBKP_PLUGIN_SLUG ) : admin_url( 'tools.php?page=' . HMBKP_PLUGIN_SLUG );

	schedules::get_instance()->refresh_schedules();

	if ( ! empty( $_REQUEST['hmbkp_schedule_id'] ) && schedules::get_instance()->get_schedule( sanitize_text_field( $_REQUEST['hmbkp_schedule_id'] ) ) ) {
		$url = add_query_arg( 'hmbkp_schedule_id', sanitize_text_field( $_REQUEST['hmbkp_schedule_id'] ), $url );
	}

	return $url;

}

/**
 * Add an error message to the array of messages.
 *
 * @param $error_message
 */
function add_settings_error( $error_message ) {

	$hmbkp_settings_errors = get_transient( 'hmbkp_settings_errors' );

	// If it doesnt exist, create.
	if ( ! $hmbkp_settings_errors ) {
		set_transient( 'hmbkp_settings_errors', (array) $error_message );
	} else {
		set_transient( 'hmbkp_settings_errors', array_unique( array_merge( $hmbkp_settings_errors, (array) $error_message ) ) );
	}

}

/**
 * Back compat version of add_settings_error
 *
 * @deprecated 3.4 add_settings_error()
 */
function hmbkp_add_settings_error( $error_message ) {
	_deprecated_function( __FUNCTION__, '3.4', 'add_settings_error()' );
	add_settings_error( $error_message );
}

/**
 * Fetch the form submission errors for display.
 *
 * @return mixed
 */
function get_settings_errors() {
	return get_transient( 'hmbkp_settings_errors' );
}

/**
 * Clear all error messages.
 *
 * @return bool
 */
function clear_settings_errors(){
	return delete_transient( 'hmbkp_settings_errors' );
}

function path_in_php_open_basedir( $path, $ini_get = 'ini_get' ) {

	$open_basedir = @call_user_func( $ini_get, 'open_basedir' );

	if ( ! $open_basedir ) {
		return true;
	}

	$open_basedir_paths = array_map( 'trim', explode( PATH_SEPARATOR, $open_basedir ) );

	if ( ! $open_basedir_paths ) {
		return true;
	}

	// Is path in the open_basedir allowed paths?
	if ( in_array( $path, $open_basedir_paths ) ) {
		return true;
	}

	// Is path a subdirectory of one of the allowed paths?
	foreach ( $open_basedir_paths as $basedir_path ) {
		if ( 0 === strpos( $path, $basedir_path ) ) {
			return true;
		}
	}

	return false;

}
