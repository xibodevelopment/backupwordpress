<?php

/**
 * Displays a row in the manage backups table
 *
 * @param string                 $file
 * @param HMBKP_Scheduled_Backup $schedule
 */
function hmbkp_get_backup_row( $file, HMBKP_Scheduled_Backup $schedule ) {

	$encoded_file = urlencode( base64_encode( $file ) );
	$offset       = get_option( 'gmt_offset' ) * 3600;

	if ( is_multisite() ) {
		$delete_action_url = network_admin_url( 'settings.php?page=' . HMBKP_PLUGIN_SLUG . '&amp;hmbkp_delete_backup=' . $encoded_file . '&amp;hmbkp_schedule_id=' . $schedule->get_id() );
		$download_action_url = network_admin_url( 'settings.php?page=' . HMBKP_PLUGIN_SLUG . '&amp;hmbkp_download_backup=' . $encoded_file . '&amp;hmbkp_schedule_id=' . $schedule->get_id() );
	} else {
		$delete_action_url =  admin_url( 'tools.php?page=' . HMBKP_PLUGIN_SLUG . '&amp;hmbkp_delete_backup=' . $encoded_file . '&amp;hmbkp_schedule_id=' . $schedule->get_id() );
		$download_action_url =  admin_url( 'tools.php?page=' . HMBKP_PLUGIN_SLUG . '&amp;hmbkp_download_backup=' . $encoded_file . '&amp;hmbkp_schedule_id=' . $schedule->get_id() );
	}
	?>

	<tr class="hmbkp_manage_backups_row">

		<th scope="row">
			<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), @filemtime( $file ) + $offset ) ); ?>
		</th>

		<td class="code">
			<?php echo esc_html( size_format( @filesize( $file ) ) ); ?>
		</td>

		<td><?php echo esc_html( hmbkp_human_get_type( $file, $schedule ) ); ?></td>

		<td>

			<?php if (  hmbkp_is_path_accessible( hmbkp_path() )  ) : ?>
			<a href="<?php echo wp_nonce_url( $download_action_url, 'hmbkp-download_backup' ); ?>"><?php _e( 'Download', 'hmbkp' ); ?></a> |
			<?php endif; ?>

			<a href="<?php echo wp_nonce_url( $delete_action_url, 'hmbkp-delete_backup' ); ?>" class="delete-action"><?php _e( 'Delete', 'hmbkp' ); ?></a>

		</td>

	</tr>

<?php }

/**
 * Displays admin notices for various error / warning
 * conditions
 *
 * @return void
 */
function hmbkp_admin_notices() {

	// If the backups directory doesn't exist and can't be automatically created
	if ( ! is_dir( hmbkp_path() ) ) :

		function hmbkp_path_exists_warning() {
			$php_user  = exec( 'whoami' );
			$php_group = reset( explode( ' ', exec( 'groups' ) ) );
			echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress is almost ready.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'The backups directory can\'t be created because your %1$s directory isn\'t writable, run %2$s or %3$s or create the folder yourself.', 'hmbkp' ), '<code>wp-content</code>', '<code>chown ' . esc_html( $php_user ) . ':' . esc_html( $php_group ) . ' ' . esc_html( dirname( hmbkp_path() ) ) . '</code>', '<code>chmod 777 ' . esc_html( dirname( hmbkp_path() ) ) . '</code>' ) . '</p></div>';
		}

		add_action( 'admin_notices', 'hmbkp_path_exists_warning' );

	endif;

	// If the backups directory exists but isn't writable
	if ( is_dir( hmbkp_path() ) && ! wp_is_writable( hmbkp_path() ) ) :

		function hmbkp_writable_path_warning() {
			$php_user  = exec( 'whoami' );
			$php_group = reset( explode( ' ', exec( 'groups' ) ) );
			echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress is almost ready.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'Your backups directory isn\'t writable, run %1$s or %2$s or set the permissions yourself.', 'hmbkp' ), '<code>chown -R ' . esc_html( $php_user ) . ':' . esc_html( $php_group ) . ' ' . esc_html( hmbkp_path() ) . '</code>', '<code>chmod -R 777 ' . esc_html( hmbkp_path() ) . '</code>' ) . '</p></div>';
		}

		add_action( 'admin_notices', 'hmbkp_writable_path_warning' );

	endif;

	// If safe mode is active
	if ( HM_Backup::is_safe_mode_active() ) :

		function hmbkp_safe_mode_warning() {
			echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%1$s is running in %2$s, please contact your host and ask them to disable it. BackUpWordPress may not work correctly whilst %3$s is on.', 'hmbkp' ), '<code>PHP</code>', sprintf( '<a href="%1$s">%2$s</a>', __( 'http://php.net/manual/en/features.safe-mode.php', 'hmbkp' ), __( 'Safe Mode', 'hmbkp' ) ), '<code>' . __( 'Safe Mode', 'hmbkp' ) . '</code>' ) . '</p></div>';
		}

		add_action( 'admin_notices', 'hmbkp_safe_mode_warning' );

	endif;

	// If a custom backups directory is defined and it doesn't exist and can't be created
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && ! is_dir( HMBKP_PATH ) ) :

		function hmbkp_custom_path_exists_warning() {
			echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'Your custom backups directory %1$s doesn\'t exist and can\'t be created, your backups will be saved to %2$s instead.', 'hmbkp' ), '<code>' . esc_html( HMBKP_PATH ) . '</code>', '<code>' . esc_html( hmbkp_path() ) . '</code>' ) . '</p></div>';
		}

		add_action( 'admin_notices', 'hmbkp_custom_path_exists_warning' );

	endif;

	// If a custom backups directory is defined and exists but isn't writable
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && is_dir( HMBKP_PATH ) && ! wp_is_writable( HMBKP_PATH ) ) :

		function hmbkp_custom_path_writable_notice() {
			echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'Your custom backups directory %1$s isn\'t writable, new backups will be saved to %2$s instead.', 'hmbkp' ), '<code>' . esc_html( HMBKP_PATH ) . '</code>', '<code>' . esc_html( hmbkp_path() ) . '</code>' ) . '</p></div>';
		}

		add_action( 'admin_notices', 'hmbkp_custom_path_writable_notice' );

	endif;

	// If there are any errors reported in the backup
	if ( hmbkp_backup_errors_message() ) :

		function hmbkp_backup_errors_notice() {
			echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress detected issues with your last backup.', 'hmbkp' ) . '</strong><a href="' . esc_url( add_query_arg( 'action', 'hmbkp_dismiss_error' ) ) . '" style="float: right;" class="button">Dismiss</a></p>' . hmbkp_backup_errors_message() . '</div>';
		}

		add_action( 'admin_notices', 'hmbkp_backup_errors_notice' );

	endif;

	$test_backup = new HMBKP_Scheduled_Backup( 'test_backup' );

	if ( ! is_readable( $test_backup->get_root() ) ) :

		function hmbkp_backup_root_unreadable_notice() {
			$test_backup = new HMBKP_Scheduled_Backup( 'test_backup' );
			echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong>' . sprintf( __( 'Your backup root path %s isn\'t readable.', 'hmbkp' ), '<code>' . $test_backup->get_root() . '</code>' ) . '</p></div>';
		}

		add_action( 'admin_notices', 'hmbkp_backup_root_unreadable_notice' );

	endif;

}

add_action( 'admin_head', 'hmbkp_admin_notices' );

/**
 * Hook in an change the plugin description when BackUpWordPress is activated
 *
 * @param array $plugins
 * @return array $plugins
 */
function hmbkp_plugin_row( $plugins ) {

	if ( isset( $plugins[HMBKP_PLUGIN_SLUG . '/backupwordpress.php'] ) )
		$plugins[HMBKP_PLUGIN_SLUG . '/backupwordpress.php']['Description'] = str_replace( 'Once activated you\'ll find me under <strong>Tools &rarr; Backups</strong>', 'Find me under <strong><a href="' . esc_url( hmbkp_get_settings_url() ) . '">Tools &rarr; Backups</a></strong>', $plugins[HMBKP_PLUGIN_SLUG . '/backupwordpress.php']['Description'] );

	return $plugins;

}

add_filter( 'all_plugins', 'hmbkp_plugin_row', 10 );

/**
 * Parse the json string of errors and
 * output as a human readable message
 *
 * @access public
 * @return null
 */
function hmbkp_backup_errors_message() {

	$message = '';

	foreach ( (array) json_decode( hmbkp_backup_errors() ) as $key => $errors ) {
		foreach ( $errors as $error ) {
			$message .= '<p><strong>' . esc_html( $key ) . '</strong>: <code>' . implode( ':', array_map( 'esc_html', (array) $error ) ) . '</code></p>';
		}
	}

	return $message;

}

/*
 *
 *
 */
function hmbkp_directory_by_total_filesize( $directory ) {

	$files = $files_with_no_size = $empty_files = $files_with_size = $unreadable_files = array();

	clearstatcache();

	$handle = opendir( $directory );

	while ( $file_handle = readdir( $handle ) ) {

		// Ignore current dir and containing dir
		if ( $file_handle === '.' || $file_handle === '..' )
			continue;

		$file = new SplFileInfo( HM_Backup::conform_dir( trailingslashit( $directory ) . $file_handle ) );

		// Unreadable files are moved to the bottom
		if ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) {
			$unreadable_files[] = $file;
			continue;
		}

		$filesize = hmbkp_total_filesize( $file );

		if ( $filesize ) {

			// If there are two files with exactly the same filesize then let's keep increasing the filesize until we don't have a clash
			while ( array_key_exists( $filesize, $files_with_size ) ) {
				$filesize++;
			}

			$files_with_size[ $filesize ] = $file;

		} elseif ( $filesize === 0 ) {

			$empty_files[] = $file;

		} else {

			$files_with_no_size[] = $file;

		}

	}

	closedir( $handle );

	// Sort files largest first
	krsort( $files_with_size );

	// Add 0 byte files / directories to the bottom
	$files = $files_with_size + array_merge( $empty_files, $unreadable_files );

	// Add directories that are still calculating to the top
	if ( $files_with_no_size ) {
		foreach ( $files_with_no_size as $file ) {
			array_unshift( $files, $file );
		}
	}

	return $files;

}

function hmbkp_recursive_directory_filesize_scanner( $directory ) {

	$sanitized_directory = substr( sanitize_key( $directory ), -30 );

	// Use the cached directory size if available
	$directory_size = get_transient( 'hmbkp_' . $sanitized_directory . '_filesize' );

	if ( $directory_size !== false ) {

		delete_option( 'hmbkp_filesize_scan_running_on_' . $sanitized_directory );

		return $directory_size;

	}

	update_option( 'hmbkp_filesize_scan_running_on_' . $sanitized_directory, true );

	$total_filesize = 0;
	$files = array();

	clearstatcache();

	$handle = opendir( $directory );

	while ( $file_handle = readdir( $handle ) ) :

		// Ignore current dir and containing dir
		if ( $file_handle === '.' || $file_handle === '..' )
			continue;

		$file = new SplFileInfo( HM_Backup::conform_dir( trailingslashit( $directory ) . $file_handle ) );

		$total_filesize += $file->getSize();

		// We need to recursively calculate the size of all files in a subdirectory
		if ( $file->isDir() ) {
			$total_filesize += hmbkp_recursive_directory_filesize_scanner( $file );
		}

	endwhile;

	closedir( $handle );

	// If we have a filesize then let's cache it
	if ( $total_filesize !== false ) {
		set_transient( 'hmbkp_' . $sanitized_directory . '_filesize', (string) $total_filesize, WEEK_IN_SECONDS );
	}

	delete_option( 'hmbkp_filesize_scan_running_on_' . $sanitized_directory );

	return $total_filesize;

}
add_action( 'wp_async_hmbkp_dir_scan', 'hmbkp_recursive_directory_filesize_scanner', 10, 2 );

function hmbkp_total_filesize( SplFileInfo $file ) {

	if ( ! file_exists( $file->getPathname() ) ) {
		return false;
	}

	if ( $file->isFile() ) {
		return $file->getSize();
	}

	if ( $file->isDir() ) {

		$size = get_transient( 'hmbkp_' . substr( sanitize_key( $file->getPathname() ), -30 ) . '_filesize' );

		if ( $size !== false ) {
			return (int) $size;

		} else {

			$sanitized_directory = substr( sanitize_key( $file->getPathname() ), -30 );

			update_option( 'hmbkp_filesize_scan_running_on_' . $sanitized_directory, true );

			// Fire an action to trigger a scan of this sub directory
			do_action( 'hmbkp_dir_scan', $file->getPathname() );

			return false;

		}

	}

}

function hmbkp_is_total_filesize_being_calculated( $pathname ) {
	return (bool) get_option( 'hmbkp_filesize_scan_running_on_' . substr( sanitize_key( $pathname ), -30 ) );
}

class HMBKP_Async_Task extends WP_Async_Task {

	protected $action = 'hmbkp_dir_scan';

	/**
	 * Prepare data for the asynchronous request
	 *
	 * @throws Exception If for any reason the request should not happen
	 *
	 * @param array $data An array of data sent to the hook
	 *
	 * @return array
	 */
	protected function prepare_data( $data ) {

		$directory = $data[0];

		/**
		 * Internally, the library uses a protected property $_body_data
		 * to store request data during a request lifetime, since the
		 * async request doesn't happen until shutdown. We can use data
		 * already stored there in subsequent runs of the action that
		 * triggers requests.
		 */
		$real_data = array(
			'directories' => array(),
		);

		if ( ! empty( $this->_body_data['directories'] ) ) {
			$real_data['directories'] = $this->_body_data['directories'];
		}

		// Store post ids in an array inside the body data
		$real_data['directories'][] = $directory;

		return $real_data;

	}

	/**
	 * Run the async task action
	 */
	protected function run_action() {

		$directories = $_POST['directories'];

		foreach ( $directories as $directory ) {

			do_action(
				"wp_async_$this->action",
				$directory
			);

		}

	}

}


/**
 * Display a html list of files
 *
 * @param HMBKP_Scheduled_Backup $schedule
 * @param mixed                  $excludes    (default: null)
 * @param string                 $file_method (default: 'get_included_files')
 * @return void
 */
function hmbkp_file_list( HMBKP_Scheduled_Backup $schedule, $excludes = null, $file_method = 'get_included_files' ) {

	if ( ! is_null( $excludes ) )
		$schedule->set_excludes( $excludes );

	$exclude_string = $schedule->exclude_string( 'regex' ); ?>

	<ul class="hmbkp_file_list code">

		<?php foreach ( $schedule->get_files() as $file ) :

			if ( ! is_null( $excludes ) && strpos( $file, str_ireplace( $schedule->get_root(), '', $schedule->get_path() ) ) !== false )
				continue;

			// Skip dot files, they should only exist on versions of PHP between 5.2.11 -> 5.3
			if ( method_exists( $file, 'isDot' ) && $file->isDot() )
				continue;

			// Show only unreadable files
			if ( $file_method === 'get_unreadable_files' && @realpath( $file->getPathname() ) && $file->isReadable() )
				continue;

			// Skip unreadable files
			elseif ( $file_method !== 'get_unreadable_files' && ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) )
				continue;

			// Show only included files
			if ( $file_method === 'get_included_files' )
				if ( $exclude_string && preg_match( '(' . $exclude_string . ')', str_ireplace( trailingslashit( $schedule->get_root() ), '', HM_Backup::conform_dir( $file->getPathname() ) ) ) )
					continue;

			// Show only excluded files
			if ( $file_method === 'get_excluded_files' )
				if ( ! $exclude_string || ! preg_match( '(' . $exclude_string . ')', str_ireplace( trailingslashit( $schedule->get_root() ), '', HM_Backup::conform_dir( $file->getPathname() ) ) ) )
					continue;

			if ( @realpath( $file->getPathname() ) && ! $file->isReadable() && $file->isDir() ) {
				?>

				<li title="<?php echo esc_attr( HM_Backup::conform_dir( trailingslashit( $file->getPathName() ) ) ); ?>"><?php echo esc_html( ltrim( trailingslashit( str_ireplace( HM_Backup::conform_dir( trailingslashit( $schedule->get_root() ) ), '', HM_Backup::conform_dir( $file->getPathName() ) ) ), '/' ) ); ?></li>

			<?php } else { ?>

				<li title="<?php echo esc_attr( HM_Backup::conform_dir( $file->getPathName() ) ); ?>"><?php echo esc_html( ltrim( str_ireplace( HM_Backup::conform_dir( trailingslashit( $schedule->get_root() ) ), '', HM_Backup::conform_dir( $file->getPathName() ) ), '/' ) ); ?></li>

			<?php }

		endforeach; ?>

	</ul>

<?php }

/**
 * Get the human readable backup type in.
 *
 * @access public
 * @param string                 $type
 * @param HMBKP_Scheduled_Backup $schedule (default: null)
 * @return string
 */
function hmbkp_human_get_type( $type, HMBKP_Scheduled_Backup $schedule = null ) {

	if ( strpos( $type, 'complete' ) !== false )
		return __( 'Database and Files', 'hmbkp' );

	if ( strpos( $type, 'file' ) !== false )
		return __( 'Files', 'hmbkp' );

	if ( strpos( $type, 'database' ) !== false )
		return __( 'Database', 'hmbkp' );

	if ( ! is_null( $schedule ) )
		return hmbkp_human_get_type( $schedule->get_type() );

	return __( 'Legacy', 'hmbkp' );

}

/**
 * Display the row of actions for a schedule
 *
 * @access public
 * @param HMBKP_Scheduled_Backup $schedule
 * @return void
 */
function hmbkp_schedule_status( HMBKP_Scheduled_Backup $schedule ) { ?>

	<span class="hmbkp-status"<?php if ( $schedule->get_status() ) { ?> title="<?php printf( __( 'Started %s ago', 'hmbkp' ), human_time_diff( $schedule->get_schedule_running_start_time() ) ); ?>"<?php } ?>>
		<?php echo $schedule->get_status() ? wp_kses_data( $schedule->get_status() ) : __( 'Starting Backup', 'hmbkp' ); ?>
		<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_cancel', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php _e( 'cancel', 'hmbkp' ); ?></a>
	</span>

<?php }

/**
 * Load the backup errors file
 *
 * @return string
 */
function hmbkp_backup_errors() {

	if ( ! file_exists( hmbkp_path() . '/.backup_errors' ) )
		return '';

	return file_get_contents( hmbkp_path() . '/.backup_errors' );

}

/**
 * Load the backup warnings file
 *
 * @return string
 */
function hmbkp_backup_warnings() {

	if ( ! file_exists( hmbkp_path() . '/.backup_warnings' ) )
		return '';

	return file_get_contents( hmbkp_path() . '/.backup_warnings' );

}

function hmbkp_backups_number( $schedule, $zero = false, $one = false, $more = false ) {

	$number = count( $schedule->get_backups() );

	if ( $number > 1 )
		$output = str_replace( '%', number_format_i18n( $number ), ( false === $more ) ? __( '% Backups Completed', 'hmbkp' ) : $more );
	elseif ( $number == 0 )
		$output = ( false === $zero ) ? __( 'No Backups Completed', 'hmbkp' ) : $zero;
	else // must be one
		$output = ( false === $one ) ? __( '1 Backup Completed', 'hmbkp' ) : $one;

	echo apply_filters( 'hmbkp_backups_number', $output, $number );
}

function hmbkp_translated_schedule_title( $slug, $title ) {

	$titles = array(
		'complete-hourly'      => esc_html__( 'Complete Hourly', 'hmbkp' ),
		'file-hourly'          => esc_html__( 'File Hourly', 'hmbkp' ),
		'database-hourly'      => esc_html__( 'Database Hourly', 'hmbkp' ),
		'complete-twicedaily'  => esc_html__( 'Complete Twicedaily', 'hmbkp' ),
		'file-twicedaily'      => esc_html__( 'File Twicedaily', 'hmbkp' ),
		'database-twicedaily'  => esc_html__( 'Database Twicedaily', 'hmbkp' ),
		'complete-daily'       => esc_html__( 'Complete Daily', 'hmbkp' ),
		'file-daily'           => esc_html__( 'File Daily', 'hmbkp' ),
		'database-daily'       => esc_html__( 'Database Daily', 'hmbkp' ),
		'complete-weekly'      => esc_html__( 'Complete Weekly', 'hmbkp' ),
		'file-weekly'          => esc_html__( 'File Weekly', 'hmbkp' ),
		'database-weekly'      => esc_html__( 'Database Weekly', 'hmbkp' ),
		'complete-fortnightly' => esc_html__( 'Complete Fortnightly', 'hmbkp' ),
		'file-fortnightly'     => esc_html__( 'File Fortnightly', 'hmbkp' ),
		'database-fortnightly' => esc_html__( 'Database Fortnightly', 'hmbkp' ),
		'complete-monthly'     => esc_html__( 'Complete Monthly', 'hmbkp' ),
		'file-monthly'         => esc_html__( 'File Monthly', 'hmbkp' ),
		'database-monthly'     => esc_html__( 'Database Monthly', 'hmbkp' ),
		'complete-manually'    => esc_html__( 'Complete Manually', 'hmbkp' ),
		'file-manually'        => esc_html__( 'File Manually', 'hmbkp' ),
		'database-manually'    => esc_html__( 'Database Manually', 'hmbkp' )
	);

	if ( isset( $titles[ $slug ] ) ) {
		return $titles[ $slug ];
	}

	return $title;

}

function hmbkp_get_settings_url() {

	if ( is_multisite() ) {
		$url = network_admin_url( 'settings.php?page=' . HMBKP_PLUGIN_SLUG );
	}

	$url = admin_url( 'tools.php?page=' . HMBKP_PLUGIN_SLUG );

	HMBKP_schedules::get_instance()->refresh_schedules();

	if ( ! empty( $_REQUEST['hmbkp_schedule_id'] ) && HMBKP_schedules::get_instance()->get_schedule( sanitize_text_field( $_REQUEST['hmbkp_schedule_id'] ) ) ) {
		$url = add_query_arg( 'hmbkp_schedule_id', sanitize_text_field( $_REQUEST['hmbkp_schedule_id'] ), $url );
	}

	return $url;

}