<form method="post" class="hmbkp-form hmbkp-edit-schedule-excludes-form" novalidate>

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />

	<?php if ( $schedule->get_excludes() ) : ?>

		<table class="widefat">

			<thead>
				<tr>
					<th><?php _e( 'Currently Excluded', 'hmbkp' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<?php foreach ( $schedule->get_excludes() as $key => $exclude ) : ?>

					<tr>

						<td data-hmbkp-exclude-rule="<?php echo esc_attr( $exclude ); ?>">

							<span class="code"><?php echo esc_attr( str_ireplace( $schedule->get_root(), '', $exclude ) ); ?></span>

							<span><?php //echo size_format( hmbkp_get_exclude_rule_file_size( $exclude, $schedule ) ); ?></span>

							<?php if ( ( $schedule->get_path() === untrailingslashit( $exclude ) ) || ( in_array( $exclude, $schedule->default_excludes() ) ) ) : ?>

								<span class="reason"><?php _e( 'default', 'hmbkp' ); ?></span>

							<?php elseif ( defined( 'HMBKP_EXCLUDE' ) && strpos( HMBKP_EXCLUDE, $exclude ) !== false ) : ?>

								<span class="reason"><?php _e( 'defined', 'hmbkp' ); ?></span>

							<?php else : ?>

								<a href="#" class="delete-action"><?php _e( 'Remove', 'hmbkp' ); ?></a>

							<?php endif; ?>

						</td>

					</tr>

				<?php endforeach; ?>

			</tbody>

		</table>

	<?php endif; ?>

		<div id="hmbkp_included_files">

			<?php

			/* TODO
			 *
			 * - Excluded files should be greyed out
			 * - Exclude button should add an exclude rule for that exact path
			 * - Style the file list
			 * - Clicking a directory link should reload page with that path set as root
			 * - Ability to re-calculate any directory size
			 * - Ability to nagivate back up the tree
			 * - Design treatment for unreadable files
			 * - JS enhance
			 * - Show full path in title attribute
			 * - Visually de-emphasise small files, especially in a long list (ala Daisy Disk)
			 * - Switch to Backdrop
			 * - We need to way to track whether a directory tree is currently being analysed
			 * - Calculate site size should use the same mechanism, that way excludes tree will mostly already be cached
			 */

			$directory = $schedule->get_root();

			clearstatcache();

			$files = hmbkp_recursive_directory_scanner( $directory );

			foreach ( $files as $file ) {

				if ( $file->isFile() ) {
					$ordered_files[ $file->getSize() ] = $file;
				}

				if ( $file->isDir() ) {
					$ordered_files[ get_transient( 'hmbkp_' . substr( sanitize_key( $file->getPathname() ), -30 ) . '_filesize' ) ] = $file;
				}

			}

			krsort( $ordered_files );

			if ( $ordered_files ) { ?>

				<p>
					<?php echo esc_html( $directory ); ?>

					<span><?php echo esc_html( size_format( get_transient( 'hmbkp_' . substr( sanitize_key( $directory ), -30 ) . '_filesize' ) ) ); ?></span>

				</p>

				<ul>

					<?php foreach ( $ordered_files as $file ) { ?>

						<li>

							<p>

								<a href="" class="button-secondary">Exclude</a>

								<?php if ( $file->isFile() ) { ?>

									<?php echo esc_html( $file->getBasename() ); ?>

									<code><?php echo esc_html( size_format( $file->getSize() ) ); ?></code>

								<?php } ?>

								<?php if ( $file->isDir() ) { ?>

									<a href=""><?php echo esc_html( $file->getBasename() ); ?></a>

									<code><?php echo esc_html( size_format( get_transient( 'hmbkp_' . substr( sanitize_key( $file->getPathname() ), -30 ) . '_filesize' ) ) ); ?></code>

								<?php } ?>

							</p>

						</li>

					<?php } ?>

				</ul>

			<?php } ?>

		</div>

		<p><?php printf( __( 'Your site is now %s. Backups will be compressed and so will be smaller.', 'hmbkp' ), '<code>' . esc_html( $schedule->get_formatted_file_size( false ) ) . '</code>' ); ?></p>

</form>