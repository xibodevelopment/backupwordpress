<div class="wrap<?php if ( hmbkp_is_in_progress() ) { ?> hmbkp_running<?php } ?>">

	<?php screen_icon( 'tools' ); ?>

	<h2>

		<?php _e( 'Manage Backups', 'hmbkp' ); ?>

<?php if ( hmbkp_is_in_progress() ) : ?>
		<a class="button add-new-h2" <?php disabled( true ); ?>><img src="<?php echo site_url( 'wp-admin/images/wpspin_light.gif' ); ?>" width="16" height="16" /><?php echo get_option( 'hmbkp_status' ); ?></a>

<?php elseif ( !is_writable( hmbkp_path() ) || !is_dir( hmbkp_path() ) || ini_get( 'safe_mode' ) ) : ?>
		<a class="button add-new-h2" <?php disabled( true ); ?>><?php _e( 'Back Up Now', 'hmbkp' ); ?></a>

<?php else : ?>
		<a class="button add-new-h2" href="tools.php?page=<?php echo $_GET['page']; ?>&amp;action=hmbkp_backup_now"><?php _e( 'Back Up Now', 'hmbkp' ); ?></a>

<?php endif; ?>

		<a href="#hmbkp_advanced-options" class="button add-new-h2 hmbkp_advanced-options-toggle"><?php _e( 'Advanced Options' ); ?></a>

	</h2>

<?php if ( is_dir( hmbkp_path() ) && is_writable( hmbkp_path() ) && !ini_get( 'safe_mode' ) ) : ?>

	<p>

	<?php if ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) && HMBKP_DISABLE_AUTOMATIC_BACKUP && !wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) ) : ?>

		<?php printf( __( 'Automatic backups are %s.', 'hmbkp' ), '<strong>' . __( 'disabled', 'hmbkp' ) . '</strong>' ); ?>

	<?php else :

		if ( ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY || !defined( 'HMBKP_FILES_ONLY' ) ) && ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY || !defined( 'HMBKP_DATABASE_ONLY' ) ) )
			$what_to_backup = '<code>' . __( 'database', 'hmbkp' ) . '</code> ' . __( '&amp;', 'hmbkp' ) . ' <code>' . __( 'files', 'hmbkp' ) . '</code>';

		elseif( defined( 'HMBKP_DATABASE_ONLY' ) && HMBKP_DATABASE_ONLY )
			$what_to_backup = '<code>' . __( 'database', 'hmbkp' ) . '</code>';

		else
			$what_to_backup = '<code>' . __( 'files', 'hmbkp' ) . '</code>'; ?>

		<?php printf( __( 'Your %s will be automatically backed up every day at %s into %s.', 'hmbkp' ), $what_to_backup , '<code title="' . sprintf( __( 'It\'s currently %s on the server.', 'hmbkp' ), date( 'H:i' ) ) . '">' . date( 'H:i', wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) ) . '</code>', '<code>' . hmbkp_path() . '</code>' ); ?>

	<?php endif; ?>

		<span class="hmbkp_estimated-size"><?php printf( __( 'Each backup will be roughly %s.', 'hmbkp' ), get_transient( 'hmbkp_estimated_filesize' ) ? '<code>' . hmbkp_calculate() . '</code>' : '<code class="calculate">' . __( 'Calculating Size...', 'hmbkp' ) . '</code>' ); ?></span>

	</p>

<?php if ( !hmbkp_shell_exec_available() ) : ?>
	<p>&#10007; <?php printf( __( '%s is disabled which means we have to use the slower PHP fallbacks, you could try contacting your host and asking them to enable it.', 'hmbkp' ), '<code>shell_exec</code>' ); ?>
<?php endif; ?>

<?php if ( hmbkp_shell_exec_available() ) : ?>

	<?php if ( hmbkp_zip_path() && ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY || !defined( 'HMBKP_DATABASE_ONLY' ) ) ) : ?>
	<p>&#10003; <?php printf( __( 'Your %s will be backed up using the %s command.', 'hmbkp' ), '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<code>' . hmbkp_zip_path() . '</code>' ); ?></p>
	<?php endif; ?>

	<?php if ( hmbkp_mysqldump_path() && ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY || !defined( 'HMBKP_FILES_ONLY' ) ) ) : ?>
	<p>&#10003; <?php printf( __( 'Your %s will be backed up using the %s command.', 'hmbkp' ), '<code>' . __( 'database', 'hmbkp' ) . '</code>', '<code>' . hmbkp_mysqldump_path() . '</code>' ); ?></p>
	<?php endif; ?>

<?php endif; ?>

<?php if ( defined( 'HMBKP_PATH' ) && HMBKP_PATH ) :
	if ( !is_dir( HMBKP_PATH ) || !is_writable( HMBKP_PATH ) ) : ?>
		<p>&#10007; <code><?php echo HMBKP_PATH; ?></code><?php printf( __( 'doesn\'t exist or isn\'t writable. your backups will be saved to %s.', 'hmbkp' ), '<code>' . hmbkp_path() . '</code>' ); ?>.</p>

	<?php else : ?>
		<p>&#10003; <?php printf( __( 'Your backups will be saved to %s.', 'hmbkp' ), '<code>' . hmbkp_path() . '</code>' ); ?></p>

	<?php endif; ?>
<?php endif ; ?>

	<?php $backup_archives = hmbkp_get_backups();
	if ( count( $backup_archives ) ) : ?>

	<table class="widefat" id="hmbkp_manage_backups_table">
		<thead>
			<tr>
				<th scope="col"><?php _e( 'Completed Backups', 'hmbkp' ); ?></th>
				<th scope="col"><?php _e( 'Size', 'hmbkp' ); ?></th>
				<th scope="col"><?php _e( 'Actions', 'hmbkp' ); ?></th>
			</tr>
		</thead>

		<tfoot>
			<tr>
				<th><?php printf( _n( 'Only the most recent backup will be saved.', 'The %d most recent backups will be saved.', hmbkp_max_backups(), 'hmbkp' ), hmbkp_max_backups() ); ?></th>
				<th><?php printf( __( 'Total %s, %s available', 'hmbkp' ), hmbkp_total_filesize(), hmbkp_size_readable( disk_free_space( ABSPATH ), null, '%01u %s' ) ); ?></th>
				<th></th>
			</tr>
		</tfoot>

		<tbody id="the-list">

		<?php foreach ( (array) $backup_archives as $file ) :

		    if ( !file_exists( $file['file'] ) )
		    	continue;

		    hmbkp_get_backup_row( $file );

		endforeach; ?>

		</tbody>
	</table>

	<?php endif; ?>

<?php else : ?>

	<p><strong><?php _e( 'You need to fix the issues detailed above before BackUpWordPress can start.', 'hmbkp' ); ?></strong></p>
<?php endif; ?>

	<div id="hmbkp_advanced-options">

		<h4><?php _e( 'Advanced Options', 'hmbkp' ); ?></h4>

		<p><?php printf( __( 'You can %s any of the following %s in your %s to control advanced options. %s. Defined %s will be highlighted.', 'hmbkp' ), '<code>define</code>', '<code>Constants</code>', '<code>wp-config.php</code>', '<a href="http://codex.wordpress.org/Editing_wp-config.php">' . __( 'The Codex can help', 'hmbkp' ) . '</a>', '<code>Constants</code>' ); ?></p>

		<dl>

		    <dt<?php if ( defined( 'HMBKP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_PATH</code></dt>
		    <dd><?php printf( __( 'The path to folder you would like to store your backup files in, defaults to %s.', 'hmbkp' ), '<code>' . hmbkp_path() . '</code>' ); ?></dd>

		    <dt<?php if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_MYSQLDUMP_PATH</code></dt>
		    <dd><?php printf( __( 'The path to your %s executable. Will be used for the %s part of the back up if available.', 'hmbkp' ), '<code>mysqldump</code>', '<code>' . __( 'database', 'hmbkp' ) . '</code>' ); ?></dd>

		    <dt<?php if ( defined( 'HMBKP_ZIP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_ZIP_PATH</code></dt>
		    <dd><?php printf( __( 'The path to your %s executable. Will be used to zip up your %s and %s if available.', 'hmbkp' ), '<code>zip</code>', '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<code>' . __( 'database', 'hmbkp' ) . '</code>' ); ?></dd>

		    <dt<?php if ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_DISABLE_AUTOMATIC_BACKUP</code></dt>
		    <dd><?php printf( __( 'Completely disables the automatic back up. You can still back up using the "Back Up Now" button. Defaults to %s.', 'hmbkp' ), '<code>(bool) false</code>' ); ?></dd>

		    <dt<?php if ( defined( 'HMBKP_MAX_BACKUPS' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_MAX_BACKUPS</code></dt>
		    <dd><?php printf( __( 'Number of backups to keep, older backups will be deleted automatically when a new backup is completed. Detaults to %s.', 'hmbkp' ), '<code>(int) 10</code>' ); ?></dd>

		    <dt<?php if ( defined( 'HMBKP_FILES_ONLY' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_FILES_ONLY</code></dt>
		    <dd><?php printf( __( 'Backup %s only, your %s will %s be backed up. Defaults to %s.', 'hmbkp' ), '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<code>' . __( 'database', 'hmbkp' ) . '</code>', '<strong>' . __( 'not', 'hmbkp' ) . '</strong>', '<code>(bool) false</code>' ); ?></dd>

		    <dt<?php if ( defined( 'HMBKP_DATABASE_ONLY' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_DATABASE_ONLY</code></dt>
		    <dd><?php printf( __( 'Backup %s only, your %s will %s be backed up. Defaults to %s.', 'hmbkp' ), '<code>' . __( 'database', 'hmbkp' ) . '</code>', '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<strong>' . __( 'not', 'hmbkp' ) . '</strong>', '<code>(bool) false</code>' ); ?></dd>

		    <dt<?php if ( defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_DAILY_SCHEDULE_TIME</code></dt>
		    <dd><?php printf( __( 'The time that the daily back up should run. Defaults to %s.', 'hmbkp' ), '<code>23:00</code>' ); ?></dd>

		</dl>

	</div>

	<p class="howto"><?php printf( __( 'If you need help getting things working you are more than welcome to email us at %s and we\'ll do what we can to help.', 'hmbkp' ), '<a href="mailto:support@humanmade.co.uk">support@humanmade.co.uk</a>' ); ?></p>

</div>