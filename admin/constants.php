<div id="hmbkp-constants">

	<p><?php printf( __( 'You can %1$s any of the following %2$s in your %3$s to control advanced settings. %4$s. Defined %5$s will be highlighted.', 'backupwordpress' ), '<code>define</code>', '<code>' . __( 'Constants', 'backupwordpress' ) . '</code>', '<code>wp-config.php</code>', '<a href="http://codex.wordpress.org/Editing_wp-config.php">' . __( 'The Codex can help', 'backupwordpress' ) . '</a>', '<code>' . __( 'Constants', 'backupwordpress' ) . '</code>' ); ?></p>

	<table class="widefat">

		<tr<?php if ( defined( 'HMBKP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_PATH</code></td>

			<td>

				<?php if ( defined( 'HMBKP_PATH' ) ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'backupwordpress' ), '<code>' . esc_html( HMBKP_PATH ) . '</code>' ); ?></p>
				<?php } ?>

				<p><?php printf( __( 'The path to folder you would like to store your backup files in, defaults to %s.', 'backupwordpress' ), '<code>' . esc_html( hmbkp_path() ) . '</code>' ); ?> <?php _e( 'e.g.', 'backupwordpress' ); ?> <code>define( 'HMBKP_PATH', '/home/willmot/backups' );</code></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_MYSQLDUMP_PATH</code></td>

			<td>

				<?php if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'backupwordpress' ), '<code>' . esc_html( HMBKP_MYSQLDUMP_PATH ) . '</code>' ); ?></p>
				<?php } ?>

				<p><?php printf( __( 'The path to your %1$s executable. Will be used for the %2$s part of the back up if available.', 'backupwordpress' ), '<code>mysqldump</code>', '<code>' . __( 'database', 'backupwordpress' ) . '</code>' ); ?> <?php _e( 'e.g.', 'backupwordpress' ); ?> <code>define( 'HMBKP_MYSQLDUMP_PATH', '/opt/local/bin/mysqldump' );</code></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_ZIP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_ZIP_PATH</code></td>

			<td>

				<?php if ( defined( 'HMBKP_ZIP_PATH' ) ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'backupwordpress' ), '<code>' . esc_html( HMBKP_ZIP_PATH ) . '</code>' ); ?></p>
				<?php } ?>

				<p><?php printf( __( 'The path to your %1$s executable. Will be used to zip up your %2$s and %3$s if available.', 'backupwordpress' ), '<code>zip</code>', '<code>' . __( 'files', 'backupwordpress' ) . '</code>', '<code>' . __( 'database', 'backupwordpress' ) . '</code>' ); ?> <?php _e( 'e.g.', 'backupwordpress' ); ?> <code>define( 'HMBKP_ZIP_PATH', '/opt/local/bin/zip' );</code></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_EXCLUDE' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_EXCLUDE</code></td>

			<td>

				<?php if ( defined( 'HMBKP_EXCLUDE' ) ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'backupwordpress' ), '<code>' . esc_html( HMBKP_EXCLUDE ) . '</code>' ); ?></p>
				<?php } ?>

				<p><?php _e( 'Comma separated list of files or directories to exclude, the backups directory is automatically excluded.', 'backupwordpress' ); ?> <?php _e( 'e.g.', 'backupwordpress' ); ?> <code>define( 'HMBKP_EXCLUDE', '/wp-content/uploads/, /stats/, .svn/, *.txt' );</code></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_CAPABILITY' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_CAPABILITY</code></td>

			<td>

				<?php if ( defined( 'HMBKP_CAPABILITY' ) ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'backupwordpress' ), '<code>' . esc_html( HMBKP_CAPABILITY ) . '</code>' ); ?></p>
				<?php } ?>

				<p><?php printf( __( 'The capability to use when calling %1$s. Defaults to %2$s.', 'backupwordpress' ), '<code>add_menu_page</code>', '<code>manage_options</code>' ); ?> <?php _e( 'e.g.', 'backupwordpress' ); ?> <code>define( 'HMBKP_CAPABILITY', 'edit_posts' );</code></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_ROOT' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_ROOT</code></td>

			<td>

				<?php if ( defined( 'HMBKP_ROOT' ) ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'backupwordpress' ), '<code>' . esc_html( HMBKP_ROOT ) . '</code>' ); ?></p>
				<?php } ?>

				<p><?php printf( __( 'The root directory that is backed up. Defaults to %s.', 'backupwordpress' ), '<code>' . HM\BackUpWordPress\Backup::get_home_path() . '</code>' ); ?> <?php _e( 'e.g.', 'backupwordpress' ); ?> <code>define( 'HMBKP_ROOT', ABSPATH . 'wp/' );</code></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_SCHEDULE_TIME' ) && HMBKP_SCHEDULE_TIME !== '11pm' ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_SCHEDULE_TIME</code></td>

			<td>

				<?php if ( defined( 'HMBKP_SCHEDULE_TIME' ) && HMBKP_SCHEDULE_TIME !== '11pm' ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'backupwordpress' ), '<code>' . esc_html( HMBKP_SCHEDULE_TIME ) . '</code>' ); ?></p>
				<?php } ?>

				<p><?php printf( __( 'The time that your schedules should run. Defaults to %s.', 'backupwordpress' ), '<code>23:00</code>' ); ?> <?php _e( 'e.g.', 'backupwordpress' ); ?> <code>define( 'HMBKP_SCHEDULE_TIME', '07:30' );</code></p>

			</td>

		</tr>

		<?php foreach ( HM\BackUpWordPress\Services::get_services() as $file => $service ) {
			echo wp_kses_post( call_user_func( array( $service, 'constant' ) ) );
		} ?>

	</table>

</div>