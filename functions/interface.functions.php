<?php
/**
 * Displays a row in the manage backups table
 *
 * @param string $file
 */
function hmbkp_get_backup_row( $file ) {

	$encode = base64_encode( $file ); ?>

	<tr class="hmbkp_manage_backups_row<?php if ( file_exists( hmbkp_path() . '/.backup_complete' ) ) : ?> completed<?php unlink( hmbkp_path() . '/.backup_complete' ); endif; ?>">

		<th scope="row">
			<?php echo date( get_option('date_format'), filemtime( $file ) ) . ' ' . date( 'H:i', filemtime($file ) ); ?>
		</th>

		<td>
			<?php echo hmbkp_size_readable( filesize( $file ) ); ?>
		</td>

		<td>

			<a href="tools.php?page=<?php echo HMBKP_PLUGIN_SLUG; ?>&amp;hmbkp_download=<?php echo $encode; ?>"><?php _e( 'Download', 'hmbkp' ); ?></a> |
			<a href="tools.php?page=<?php echo HMBKP_PLUGIN_SLUG; ?>&amp;hmbkp_delete=<?php echo $encode ?>" class="delete"><?php _e( 'Delete', 'hmbkp' ); ?></a>

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
	if ( !is_dir( hmbkp_path() ) ) :

	    function hmbkp_path_exists_warning() {
		    $php_user = exec( 'whoami' );
			$php_group = reset( explode( ' ', exec( 'groups' ) ) );
	    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress is almost ready.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'The backups directory can\'t be created because your %s directory isn\'t writable, run %s or %s or create the folder yourself.', 'hmbkp' ), '<code>wp-content</code>', '<code>chown ' . $php_user . ':' . $php_group . ' ' . WP_CONTENT_DIR . '</code>', '<code>chmod 777 ' . WP_CONTENT_DIR . '</code>' ) . '</p></div>';
	    }
	    add_action( 'admin_notices', 'hmbkp_path_exists_warning' );

	endif;

	// If the backups directory exists but isn't writable
	if ( is_dir( hmbkp_path() ) && !is_writable( hmbkp_path() ) ) :

	    function hmbkp_writable_path_warning() {
			$php_user = exec( 'whoami' );
			$php_group = reset( explode( ' ', exec( 'groups' ) ) );
	    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress is almost ready.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'Your backups directory isn\'t writable. run %s or %s or set the permissions yourself.', 'hmbkp' ), '<code>chown -R ' . $php_user . ':' . $php_group . ' ' . hmbkp_path() . '</code>', '<code>chmod -R 777 ' . hmbkp_path() . '</code>' ) . '</p></div>';
	    }
	    add_action( 'admin_notices', 'hmbkp_writable_path_warning' );

	endif;

	// If safe mode is active
	if ( hmbkp_is_safe_mode_active() ) :

	    function hmbkp_safe_mode_warning() {
	    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( ' %s is running in %s. Please contact your host and ask them to disable %s.', 'hmbkp' ), '<code>PHP</code>', '<a href="http://php.net/manual/en/features.safe-mode.php"><code>Safe Mode</code></a>', '<code>Safe Mode</code>' ) . '</p></div>';
	    }
	    add_action( 'admin_notices', 'hmbkp_safe_mode_warning' );

	endif;

	// If both HMBKP_FILES_ONLY & HMBKP_DATABASE_ONLY are defined at the same time
	if ( defined( 'HMBKP_FILES_ONLY' ) && HMBKP_FILES_ONLY && defined( 'HMBKP_DATABASE_ONLY' ) && HMBKP_DATABASE_ONLY ) :

	    function hmbkp_nothing_to_backup_warning() {
	    	echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'You have both %s and %s defined so there isn\'t anything to back up.', 'hmbkp' ), '<code>HMBKP_DATABASE_ONLY</code>', '<code>HMBKP_FILES_ONLY</code>' ) . '</p></div>';
	    }
	    add_action( 'admin_notices', 'hmbkp_nothing_to_backup_warning' );

	endif;

	// If the email address is invalid
	if ( defined( 'HMBKP_EMAIL' ) && !is_email( HMBKP_EMAIL ) ) :

		function hmbkp_email_invalid_warning() {
			echo '<div id="hmbkp-email_invalid" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( '%s is not a valid email address.', 'hmbkp' ), '<code>' . HMBKP_EMAIL . '</code>' ) . '</p></div>';
		}
		add_action( 'admin_notices', 'hmbkp_email_invalid_warning' );

	endif;

	// If the email failed to send
	if ( defined( 'HMBKP_EMAIL' ) && get_option( 'hmbkp_email_error' ) ) :

		function hmbkp_email_failed_warning() {
			echo '<div id="hmbkp-email_invalid" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . __( 'The last backup email failed to send.', 'hmbkp' ) . '</p></div>';
		}
		add_action( 'admin_notices', 'hmbkp_email_failed_warning' );

	endif;

	// If a custom backups directory is defined and it doesn't exist and can't be created
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && !is_dir( HMBKP_PATH ) ) :

		function hmbkp_custom_path_exists_warning() {
			echo '<div id="hmbkp-email_invalid" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'Your custom backups directory %s doesn\'t exist and can\'t be created, your backups will be saved to %s instead.', 'hmbkp' ), '<code>' . HMBKP_PATH . '</code>', '<code>' . hmbkp_path() . '</code>' ) . '</p></div>';
		}
		add_action( 'admin_notices', 'hmbkp_custom_path_exists_warning' );

	endif;

	// If a custom backups directory is defined and exists but isn't writable
	if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH && is_dir( HMBKP_PATH ) && !is_writable( HMBKP_PATH ) ) :

		function hmbkp_custom_path_writable_notice() {
			echo '<div id="hmbkp-email_invalid" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'Your custom backups directory %s isn\'t writable, new backups will be saved to %s instead.', 'hmbkp' ), '<code>' . HMBKP_PATH . '</code>', '<code>' . hmbkp_path() . '</code>' ) . '</p></div>';
		}
		add_action( 'admin_notices', 'hmbkp_custom_path_writable_notice' );

	endif;

	// If there are custom excludes defined and any of the files or directories don't exist
	if ( hmbkp_invalid_custom_excludes() ) :

		function hmbkp_invalid_exclude_notice() {
			echo '<div id="hmbkp-email_invalid" class="updated fade"><p><strong>' . __( 'BackUpWordPress has detected a problem.', 'hmbkp' ) . '</strong> ' . sprintf( __( 'You have defined a custom exclude list but the following paths don\'t exist %s, are you sure you entered them correctly?', 'hmbkp' ), '<code>' . implode( '</code>, <code>', (array) hmbkp_invalid_custom_excludes() ) . '</code>' ) . '</p></div>';
		}
		add_action( 'admin_notices', 'hmbkp_invalid_exclude_notice' );

	endif;

}
add_action( 'admin_head', 'hmbkp_admin_notices' );

/**
 * Hook in an change the plugin description when BackUpWordPress is activated
 *
 * @param array $plugins
 * @return $plugins
 */
function hmbkp_plugin_row( $plugins ) {

	if ( isset( $plugins[HMBKP_PLUGIN_SLUG . '/plugin.php'] ) )
		$plugins[HMBKP_PLUGIN_SLUG . '/plugin.php']['Description'] = str_replace( 'Once activated you\'ll find me under <strong>Tools &rarr; Backups</strong>', 'Find me under <strong><a href="' . admin_url( 'tools.php?page=' . HMBKP_PLUGIN_SLUG ) . '">Tools &rarr; Backups</a></strong>', $plugins[HMBKP_PLUGIN_SLUG . '/plugin.php']['Description'] );

	return $plugins;

}
add_filter( 'all_plugins', 'hmbkp_plugin_row', 10 );