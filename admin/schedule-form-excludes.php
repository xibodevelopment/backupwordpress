<form method="post" class="hmbkp-form hmbkp-edit-schedule-excludes-form" novalidate>

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />

	<?php if ( $schedule->get_excludes() ) : ?>

		<h3><?php _e( 'Currently Excluded', 'hmbkp' ); ?></h3>

		<table class="widefat">

			<tbody>

				<?php foreach ( $schedule->get_excludes() as $key => $exclude ) : ?>

					<tr>

						<td data-hmbkp-exclude-rule="<?php echo esc_attr( $exclude ); ?>">

							<span class="code"><?php echo esc_html( str_ireplace( $schedule->get_root(), '', $exclude ) ); ?></span>

						</td>


						<td>

							<span><?php //echo size_format( hmbkp_get_exclude_rule_file_size( $exclude, $schedule ) ); ?></span>

						</td>

						<td>

							<?php if ( ( $schedule->get_path() === untrailingslashit( $exclude ) ) || ( in_array( $exclude, $schedule->default_excludes() ) ) ) : ?>

								<span class="reason"><?php _e( 'default', 'hmbkp' ); ?></span>

							<?php elseif ( defined( 'HMBKP_EXCLUDE' ) && strpos( HMBKP_EXCLUDE, $exclude ) !== false ) : ?>

								<span class="reason"><?php _e( 'defined', 'hmbkp' ); ?></span>

							<?php else : ?>

								<a href="<?php echo wp_nonce_url( add_query_arg( 'hmbkp_remove_exclude', $exclude ), 'hmbkp-remove_exclude_rule' ); ?>" class="delete-action"><?php _e( 'Remove', 'hmbkp' ); ?></a>

							<?php endif; ?>

						</td>

					</tr>

				<?php endforeach; ?>

			</tbody>

		</table>

	<?php endif; ?>

	<div class="hmbkp-directory-list">

		<h3>Directory Listing</h3>

		<p class="description">Here's a directory listing of all files on your site, you can browse through and exclude files or folders that you don't want included in your backup.</p>

		<?php

		/* TODO
		 *
		 * - JS enhance
		 * - Visually de-emphasise small files, especially in a long list (ala Daisy Disk)
		 * - Switch to Backdrop
		 * - Calculate site size should use the same mechanism, that way excludes tree will mostly already be cached
		 * - Translations
		 */

		// The directory to display
		$directory = $schedule->get_root();

		if ( isset( $_GET['hmbkp_directory_browse'] )  ) {

			$untrusted_directory = urldecode( $_GET['hmbkp_directory_browse'] );

			// Only allow real sub directories of the site root to be browsed
			if ( strpos( $untrusted_directory, $schedule->get_root() ) !== false && is_dir( $untrusted_directory ) )
				$directory = $untrusted_directory;

		}

		$exclude_string = $schedule->exclude_string( 'regex' );

		// Kick off a recursive filesize scan
		$files = hmbkp_directory_by_total_filesize( $directory );

		if ( $files ) { ?>

			<table class="widefat">

				<thead>

					<tr>
						<th></th>
						<th scope="col">Name</th>
						<th scope="col" class="column-format">Size</th>
						<th scope="col" class="column-format">Permissions</th>
						<th scope="col" class="column-format">Type</th>
						<th scope="col" class="column-format">Status</th>
					<tr>

					<tr>

						<th scope="row">
							<div class="dashicons dashicons-admin-home"></div>
						</th>

						<th scope="col" colspan="2">

							<?php if ( $schedule->get_root() !== $directory ) { ?>

								<a href="<?php echo remove_query_arg( 'hmbkp_directory_browse' ); ?>"><?php echo esc_html( $schedule->get_root() ); ?></a> <code>/</code>

								<?php $parents = array_filter( explode( '/', str_replace( trailingslashit( $schedule->get_root() ), '', trailingslashit( dirname( $directory ) ) ) ) );

								foreach ( $parents as $directory_basename ) { ?>

									<a href="<?php echo add_query_arg( 'hmbkp_directory_browse', urlencode( substr( $directory, 0, strpos( $directory, $directory_basename ) ) . $directory_basename ) ); ?>"><?php echo esc_html( $directory_basename ); ?></a> <code>/</code>

								<?php } ?>

								<?php echo esc_html( basename( $directory ) ); ?>

							<?php } else { ?>

								<?php echo esc_html( $schedule->get_root() ); ?>

							<?php } ?>

						</th>

						<td>
							<?php echo esc_html( substr( sprintf( '%o', fileperms( $schedule->get_root() ) ), -4 ) ); ?>
						</td>

						<td>

							<?php if ( is_link( $schedule->get_root() ) ) {

								_e( 'Symlink', 'hmbkp' );

							} elseif ( is_dir( $schedule->get_root() ) ) {

								_e( 'Folder', 'hmbkp' );

							} ?>

						</td>

						<td></td>

					</tr>

				</thead>

				<tbody>

					<?php foreach ( $files as $size => $file ) {

						$is_excluded = $is_unreadable = false;

						// Check if the file is excluded
						if ( $exclude_string && preg_match( '(' . $exclude_string . ')', str_ireplace( trailingslashit( $schedule->get_root() ), '', HM_Backup::conform_dir( $file->getPathname() ) ) ) ) {
							$is_excluded = true;
						}

						// Skip unreadable files
						if ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) {
							$is_unreadable = true;
						} ?>

						<tr>

							<td>

								<?php if ( $is_unreadable ) { ?>

									<div class="dashicons dashicons-dismiss"></div>

								<?php } elseif ( $file->isFile() ) { ?>

									<div class="dashicons dashicons-media-default"></div>

								<?php } elseif ( $file->isDir() ) { ?>

									<div class="dashicons dashicons-portfolio"></div>

								<?php } ?>

							</td>

							<td>

								<?php if ( $is_unreadable ) { ?>

									<code class="strikethrough" title="<?php echo esc_attr( $file->getRealPath() ); ?>"><?php echo esc_html( $file->getBasename() ); ?></code>

								<?php } elseif ( $file->isFile() ) { ?>

									<code title="<?php echo esc_attr( $file->getRealPath() ); ?>"><?php echo esc_html( $file->getBasename() ); ?></code>

								<?php } elseif ( $file->isDir() ) { ?>

									<code title="<?php echo esc_attr( $file->getRealPath() ); ?>"><a href="<?php echo add_query_arg( 'hmbkp_directory_browse', urlencode( $file->getPathname() ) ); ?>"><?php echo esc_html( $file->getBasename() ); ?></a></code>

								<?php } ?>

							</td>

							<td class="column-format column-filesize">

								<?php if ( $file->isDir() && hmbkp_is_total_filesize_being_calculated( $file->getPathname() ) ) { ?>

									<span class="spinner"></span>

								<?php } else {

									$size = hmbkp_total_filesize( $file );

									if ( $size !== false ) {

										$size = size_format( $size );

										if ( ! $size ) {
											$size = '0 B';
										} ?>

										<code>

											<?php echo esc_html( $size ); ?>

											<?php if ( $file->isDir() ) { ?>

												<a class="dashicons dashicons-update" href="<?php echo wp_nonce_url( add_query_arg( 'hmbkp_recalculate_directory_filesize', urlencode( $file->getPathname() ) ), 'hmbkp-recalculate_directory_filesize' ); ?>"><span>Refresh</span></a>

											<?php }  ?>

										</code>


									<?php } else { ?>

										--

									<?php }
								} ?>

							</td>

							<td>
								<?php echo esc_html( substr( sprintf( '%o', $file->getPerms() ), -4 ) ); ?>
							</td>

							<td>

								<?php if ( $file->isLink() ) { ?>

									<span title="<?php echo esc_attr( $file->GetRealPath() ); ?>"><?php _e( 'Symlink', 'hmbkp' ); ?></span>

								<?php } elseif ( $file->isDir() ) {

									_e( 'Folder', 'hmbkp' );

								} else {

									_e( 'File', 'hmbkp' );

								} ?>

							</td>

							<td class="column-format">

								<?php if ( $is_unreadable ) { ?>

									<strong title="<?php _e( 'Unreadable files won\'t be backed up.', 'hmbkp' ); ?>"><?php _e( 'Unreadable', 'hmbkp' ); ?></strong>

								<?php } elseif ( $is_excluded ) { ?>

									<strong><?php _e( 'Excluded', 'hmbkp' ); ?></strong>

								<?php } else { ?>

									<a href="<?php echo wp_nonce_url( add_query_arg( 'hmbkp_exclude_pathname', urlencode( $file->getPathname() ) ), 'hmbkp-add_exclude_rule' ); ?>" class="button-secondary"><?php _e( 'Exclude &rarr;', 'hmbkp' ); ?></a>

								<?php } ?>

							</td>

						</tr>

					<?php } ?>

				</tbody>

			</table>

		<?php } ?>

	</div>

</form>