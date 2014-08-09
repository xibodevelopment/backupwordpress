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

							<a href="" class="button-secondary">Exclude</a>

							<?php if ( $file->isFile() ) { ?>

								<?php echo esc_html( $file->getBasename() ); ?>

								<span><?php echo esc_html( size_format( $file->getSize() ) ); ?></span>

							<?php } ?>

							<?php if ( $file->isDir() ) { ?>

								<a href=""><?php echo esc_html( $file->getBasename() ); ?></a>

								<span><?php echo esc_html( size_format( get_transient( 'hmbkp_' . substr( sanitize_key( $file->getPathname() ), -30 ) . '_filesize' ) ) ); ?></span>

							<?php } ?>

						</li>

					<?php } ?>

				</ul>

			<?php } ?>

		</div>

		<p><?php printf( __( 'Your site is now %s. Backups will be compressed and so will be smaller.', 'hmbkp' ), '<code>' . esc_html( $schedule->get_formatted_file_size( false ) ) . '</code>' ); ?></p>

</form>