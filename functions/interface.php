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

			<a href="<?php echo esc_url( wp_nonce_url(
				add_query_arg( array(
					'hmbkp_backup_archive' => $encoded_file,
					'hmbkp_schedule_id'    => $schedule->get_id(),
					'action'               => 'hmbkp_request_delete_backup',
					),
					admin_url( 'admin-post.php' )
				),
				'hmbkp_delete_backup',
				'hmbkp_delete_backup_nonce'
			) ); ?>" class="delete-action">
				<?php esc_html_e( 'Delete', 'backupwordpress' ); ?>
			</a>

		</td>

	</tr>

<?php }

/**
 * Displays admin notices for various error / warning conditions.
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

		</div>

	<?php endif; ?>

	<?php if ( ! empty( $notices['server_config'] ) ) : ?>

		<div id="hmbkp-warning-server" class="error notice">

			<ul>

				<?php foreach ( $notices['server_config'] as $notice ) : ?>

					<li>
						<?php print_whitelist_html(
							$notice,
							'strong, b, i, em, code, a'
						); ?>
					</li>

				<?php endforeach; ?>

			</ul>

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

				</div>

			<?php endif; ?>

		<?php endforeach; ?>

	<?php endif; ?>

	<?php echo ob_get_clean();

}
add_action( 'admin_notices', 'HM\BackUpWordPress\admin_notices' );
add_action( 'network_admin_notices', 'HM\BackUpWordPress\admin_notices' );

function set_server_config_notices() {

	$notices  = Notices::get_instance();
	$messages = array();

	if ( Backup_Utilities::is_safe_mode_on() ) {

		$messages[] = sprintf(
			/* translators: 1: The `PHP` abbreviation. */
			__( '%1$s is running in <a href="http://php.net/manual/en/features.safe-mode.php">Safe Mode</a>, please contact your host and ask them to disable it. BackUpWordPress may not work correctly whilst <code>Safe Mode</code> is on.', 'backupwordpress' ),
			'<code>PHP</code>'
		);
	}

	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH ) {

		// Suppress open_basedir warning https://bugs.php.net/bug.php?id=53041
		if ( ! path_in_php_open_basedir( HMBKP_PATH ) ) {

			$messages[] = sprintf(
				__( 'Your server has an %1$s restriction in effect and your custom backups directory (%2$s) is not within the allowed path(s): (%3$s).', 'backupwordpress' ),
				'<code>open_basedir</code>',
				'<code>' . esc_html( HMBKP_PATH ) . '</code>',
				'<code>' . esc_html( @ini_get( 'open_basedir' ) ) . '</code>'
			);

		} elseif ( ! is_dir( HMBKP_PATH ) ) {

			$messages[] = sprintf(
				__( 'Your custom backups directory (%1$s) doesn&apos;t exist, your backups will be saved to %2$s instead.', 'backupwordpress' ),
				'<code>' . esc_html( HMBKP_PATH ) . '</code>',
				'<code>' . esc_html( Path::get_path() ) . '</code>'
			);

		} elseif ( is_dir( HMBKP_PATH ) && ! wp_is_writable( HMBKP_PATH ) ) {

			$messages[] = sprintf(
				__( 'Your custom backups directory (%1$s) isn&apos;t writable, new backups will be saved to %2$s instead.', 'backupwordpress' ),
				'<code>' . esc_html( HMBKP_PATH ) . '</code>',
				'<code>' . esc_html( Path::get_path() ) . '</code>'
			);
		}
	}

	if ( ! is_dir( Path::get_path() ) || is_dir( Path::get_path() ) && ! wp_is_writable( Path::get_path() ) ) {

		if ( isset( $_GET['creation_error'] ) ) {

			$messages[] = sprintf(
				/* translators: 1: URL to BackupWordPress docs. */
				__( 'We connected to your server successfully but still weren&apos;t able to automatically create the directory. You&apos;ll need to <a href="%1$s">manually specify a valid directory</a>', 'backupwordpress' ),
				'https://bwp.hmn.md/support-center/backupwordpress-faqs/#where'
			);

		} else {

			$messages[] = sprintf(
				/* translators: 1: Path to backup directory. 2: URL to BackupWordPress docs. */
				__( 'We couldn&apos;t create the backups directory (%1$s). You&apos;ll need to <a href="%2$s">manually specify a valid directory</a> or you can have WordPress do it automatically by entering your server details below. This is a one time thing.', 'backupwordpress' ),
				'<code>' . esc_html( Path::get_path() ) . '</code>',
				'https://bwp.hmn.md/support-center/backupwordpress-faqs/#where'
			);
		}
	}

	if ( ! is_readable( Path::get_root() ) ) {

		$messages[] = sprintf(
			__( 'Your site&apos;s root path (%s) isn&apos;t readable. Please contact support.', 'backupwordpress' ),
			'<code>' . Path::get_root() . '</code>'
		);
	}

	if ( ! Requirement_Mysqldump_Command_Path::test() && ! Requirement_PDO::test() ) {

		$messages[] = sprintf(
			/* translators: FYI: specified MySQL features. */
			__( 'Your site cannot be backed up because your server doesn&apos;t support %1$s or %2$s. Please contact your host and ask them to enable them.', 'backupwordpress' ),
			'<code>mysqldump</code>',
			'<code>PDO::mysql</code>'
		);
	}

	if ( ! Requirement_Zip_Command_Path::test() && ! Requirement_Zip_Archive::test() ) {

		$messages[] = sprintf(
			/* translators: FYI: specified zip archiving features. */
			__( 'Your site cannot be backed up because your server doesn&apos;t support %1$s or %2$s. Please contact your host and ask them to enable them.', 'backupwordpress' ),
			'<code>zip</code>',
			'<code>ZipArchive</code>'
		);
	}

	if ( disk_space_low() ) {

		$messages[] = sprintf(
			__( 'Your server only has %s of disk space left which probably isn&apos;t enough to complete a backup. Try deleting some existing backups or other files to free up space.', 'backupwordpress' ),
			'<code>' . size_format( disk_free_space( Path::get_path() ) ) . '</code>'
		);
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

	if ( isset( $plugins[ HMBKP_PLUGIN_SLUG . '/backupwordpress.php' ] ) ) {
		$plugins[ HMBKP_PLUGIN_SLUG . '/backupwordpress.php' ]['Description'] = str_replace( 'Once activated you\'ll find me under <strong>' . $menu . ' &rarr; Backups</strong>', 'Find me under <strong><a href="' . esc_url( get_settings_url() ) . '">' . $menu . ' &rarr; Backups</a></strong>', $plugins[ HMBKP_PLUGIN_SLUG . '/backupwordpress.php' ]['Description'] );
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
		'database-manually'    => esc_html__( 'Database Manually', 'backupwordpress' ),
	);

	if ( isset( $titles[ $slug ] ) ) {
		return $titles[ $slug ];
	}

	return $title;

}

function get_settings_url( $slug = HMBKP_PLUGIN_SLUG ) {

	$url = is_multisite() ? network_admin_url( 'settings.php?page=' . $slug ) : admin_url( 'tools.php?page=' . $slug );

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
function clear_settings_errors() {
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

/**
 * Check if two filesizes are of the same size format
 *
 * E.g. 22 MB and 44 MB are both MB so return true. Whereas
 * 22 KB and 12 TB are not so return false.
 *
 * @param  int  $size
 * @param  int  $other_size
 *
 * @return boolean             Whether the two filesizes are of the same magnitude
 */
function is_same_size_format( $size, $other_size ) {

	if ( ! is_int( $size ) || ! is_int( $other_size ) ) {
		return false;
	}

	return preg_replace( '/[0-9]+/', '', size_format( $size ) ) === preg_replace( '/[0-9]+/', '', size_format( $other_size ) );
}

/**
 * Check whether the server is low on disk space.
 *
 * @return bool Whether there's less disk space less than 2 * the entire size of the site.
 */
function disk_space_low( $backup_size = false ) {

	$disk_space = @disk_free_space( Path::get_path() );

	if ( ! $disk_space ) {
		return false;
	}

	if ( ! $backup_size ) {

		$site_size = new Site_Size( 'complete', new Excludes() );

		if ( ! $site_size->is_site_size_cached() ) {
			return false;
		}

		$backup_size = $site_size->get_site_size() * 2;

	}

	if ( ! is_readable( Path::get_path() ) ) {
		return false;
	}

	$disk_space = disk_free_space( Path::get_path() );

	return $disk_space && $backup_size >= $disk_space;
}
