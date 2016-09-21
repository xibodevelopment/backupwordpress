<?php

namespace HM\BackUpWordPress;

$excludes      = $schedule->get_excludes();
$user_excludes = $excludes->get_user_excludes(); ?>

<div class="hmbkp-exclude-settings">

	<h3>
		<?php esc_html_e( 'Currently Excluded', 'backupwordpress' ); ?>
	</h3>

	<p>
		<?php esc_html_e( 'We automatically detect and ignore common Version Control Systems folders and other backup plugin folders.', 'backupwordpress' ); ?>
	</p>

	<table class="widefat">

		<tbody>

			<?php foreach ( $user_excludes as $key => $exclude ) :

				$exclude_path = new \SplFileInfo( trailingslashit( Path::get_root() ) . ltrim( str_ireplace( Path::get_root(), '', $exclude ), '/' ) ); ?>

				<tr>

					<th scope="row">

						<?php if ( $exclude_path->isFile() ) : ?>

							<div class="dashicons dashicons-media-default"></div>

						<?php elseif ( $exclude_path->isDir() ) : ?>

							<div class="dashicons dashicons-portfolio"></div>

						<?php endif; ?>

					</th>

					<td>

						<code><?php echo esc_html( str_ireplace( Path::get_root(), '', $exclude ) ); ?></code>

					</td>

					<td>

						<?php if (
							( in_array( $exclude, $excludes->get_default_excludes() ) ) ||
							( Path::get_path() === trailingslashit( Path::get_root() ) . untrailingslashit( $exclude ) )
						) : ?>

							<?php esc_html_e( 'Default rule', 'backupwordpress' ); ?>

						<?php elseif ( defined( 'HMBKP_EXCLUDE' ) && false !== strpos( HMBKP_EXCLUDE, $exclude ) ) : ?>

							<?php printf( esc_html__( 'Defined in %s', 'backupwordpress' ), 'wp-config.php' ); ?>

						<?php else : ?>

							<a href="<?php echo admin_action_url( 'remove_exclude_rule', array(
								'hmbkp_remove_exclude' => $exclude,
								'hmbkp_schedule_id'    => $schedule->get_id(),
							) ); ?>" class="delete-action">
								<?php esc_html_e( 'Stop excluding', 'backupwordpress' ); ?>
							</a>

						<?php endif; ?>

					</td>

				</tr>

			<?php endforeach; ?>

		</tbody>

	</table>

	<h3 id="directory-listing">
		<?php esc_html_e( 'Your Site', 'backupwordpress' ); ?>
	</h3>

	<p>
		<?php esc_html_e( 'Here\'s a directory listing of all files on your site, you can browse through and exclude files or folders that you don\'t want included in your backup.', 'backupwordpress' ); ?>
	</p>

	<?php

	// The directory to display.
	$directory = Path::get_root();

	if ( isset( $_GET['hmbkp_directory_browse'] ) ) {

		$untrusted_directory = urldecode( $_GET['hmbkp_directory_browse'] );

		// Only allow real sub-directories of the site root to be browsed.
		if (
			false !== strpos( $untrusted_directory, Path::get_root() ) &&
			is_dir( $untrusted_directory )
		) {
			$directory = $untrusted_directory;
		}
	}

	$site_size          = new Site_Size( 'file' );
	$excluded_site_size = new Site_Size( 'file', $excludes );

	// Kick off a recursive filesize scan.
	$files = list_directory_by_total_filesize( $directory, $excludes );
	?>

	<table class="widefat">

		<thead>

			<tr>
				<th></th>
				<th scope="col"><?php esc_html_e( 'Name', 'backupwordpress' ); ?></th>
				<th scope="col" class="column-format"><?php esc_html_e( 'Included Size', 'backupwordpress' ); ?></th>
				<th scope="col" class="column-format"><?php esc_html_e( 'Permissions', 'backupwordpress' ); ?></th>
				<th scope="col" class="column-format"><?php esc_html_e( 'Type', 'backupwordpress' ); ?></th>
				<th scope="col" class="column-format"><?php esc_html_e( 'Status', 'backupwordpress' ); ?></th>
			</tr>

			<tr>

				<th scope="row">
					<div class="dashicons dashicons-admin-home"></div>
				</th>

				<th scope="col">

					<?php if ( Path::get_root() !== $directory ) : ?>

						<a href="<?php echo esc_url( remove_query_arg( 'hmbkp_directory_browse' ) ); ?>">
							<?php echo esc_html( Path::get_root() ); ?>
						</a>
						<code>/</code>

						<?php
						$parents = array_filter( explode(
							'/',
							str_replace( trailingslashit( Path::get_root() ), '', trailingslashit( dirname( $directory ) ) )
						) );

						foreach ( $parents as $directory_basename ) : ?>

							<a href="<?php echo esc_url( add_query_arg( 'hmbkp_directory_browse', urlencode( substr( $directory, 0, strpos( $directory, $directory_basename ) ) . $directory_basename ) ) ); ?>">
								<?php echo esc_html( $directory_basename ); ?>
							</a>
							<code>/</code>

						<?php endforeach; ?>

						<?php echo esc_html( basename( $directory ) ); ?>

					<?php else : ?>

						<?php echo esc_html( Path::get_root() ); ?>

					<?php endif; ?>

				</th>

				<td class="column-filesize">

					<?php if ( Site_Size::is_site_size_being_calculated() ) : ?>

						<span class="spinner is-active"></span>

					<?php else :

						$root          = new \SplFileInfo( Path::get_root() );
						$size          = $site_size->filesize( $root );
						$excluded_size = $excluded_site_size->filesize( $root );
						$excluded_size = is_same_size_format( $size, $excluded_size ) ? (int) size_format( $excluded_size ) : size_format( $excluded_size );
						?>

							<code>
								<?php
								/* translators: 1: Excluded size 2: Overall site size */
								printf(
									esc_html__( '%1$s of %2$s', 'backupwordpress' ),
									$excluded_size,
									size_format( $size )
								);
								?>

								<a class="dashicons dashicons-update" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'hmbkp_recalculate_directory_filesize',  urlencode( Path::get_root() ) ), 'hmbkp-recalculate_directory_filesize' ) ); ?>">
									<span><?php esc_html_e( 'Refresh', 'backupwordpress' ); ?></span>
								</a>
							</code>

					<?php endif; ?>

				<td>
					<code><?php echo esc_html( substr( sprintf( '%o', fileperms( Path::get_root() ) ), - 4 ) ); ?></code>
				</td>

				<td>

					<code>

						<?php if ( is_link( Path::get_root() ) ) :
							esc_html_e( 'Symlink', 'backupwordpress' );
						elseif ( is_dir( Path::get_root() ) ) :
							esc_html_e( 'Folder', 'backupwordpress' );
						endif; ?>

					</code>

				</td>

				<td></td>

			</tr>

		</thead>

		<tbody>

		<?php if ( $files ) :

			foreach ( $files as $size => $file ) :

				$is_excluded = $is_unreadable = false;

				// Check if the file is excluded.
				if ( $excludes->is_file_excluded( $file ) ) :
					$is_excluded = true;
				endif;

				// Skip unreadable files.
				if ( ! @realpath( $file->getPathname() ) || ! $file->isReadable() ) :
					$is_unreadable = true;
				endif;
				?>

				<tr>

					<td>

						<?php if ( $is_unreadable ) : ?>

							<div class="dashicons dashicons-dismiss"></div>

						<?php elseif ( $file->isFile() ) : ?>

							<div class="dashicons dashicons-media-default"></div>

						<?php elseif ( $file->isDir() ) : ?>

							<div class="dashicons dashicons-portfolio"></div>

						<?php endif; ?>

					</td>

					<td>

						<?php if ( $is_unreadable ) : ?>

							<code class="strikethrough" title="<?php echo esc_attr( wp_normalize_path( $file->getRealPath() ) ); ?>">
								<?php echo esc_html( $file->getBasename() ); ?>
							</code>

						<?php elseif ( $file->isFile() ) : ?>

							<code title="<?php echo esc_attr( wp_normalize_path( $file->getRealPath() ) ); ?>">
								<?php echo esc_html( $file->getBasename() ); ?>
							</code>

						<?php elseif ( $file->isDir() ) : ?>

							<code title="<?php echo esc_attr( wp_normalize_path( $file->getRealPath() ) ); ?>">
								<a href="<?php echo esc_url( add_query_arg( 'hmbkp_directory_browse', urlencode( wp_normalize_path( $file->getPathname() ) ) ) ); ?>">
									<?php echo esc_html( $file->getBasename() ); ?>
								</a>
							</code>

						<?php endif; ?>

					</td>

					<td class="column-format column-filesize">

						<?php if ( $file->isDir() && Site_Size::is_site_size_being_calculated() ) : ?>
							<span class="spinner is-active"></span>
						<?php else :
							$size = $site_size->filesize( $file );

							if ( false !== $size ) :

								$size          = $size;
								$excluded_size = $excluded_site_size->filesize( $file ); ?>

								<code>

									<?php
									// Display `included of total size` info for directories and excluded files only.
									if ( $file->isDir() || ( $file->isFile() && $is_excluded ) ) :

										if ( $excluded_size ) {
											$excluded_size = is_same_size_format( $size, $excluded_size ) ? (int) size_format( $excluded_size ) : size_format( $excluded_size );
										}

										if ( $size ) {
											$size = size_format( $size );
										}

										/* translators: 1: Excluded size 2: Overall directory/file size */
										printf(
											esc_html__( '%1$s of %2$s', 'backupwordpress' ),
											$excluded_size,
											$size
										);

									elseif ( ! $is_unreadable ) :
										echo esc_html( size_format( $size ) );
									else :
										echo '-';
									endif; ?>

								</code>

							<?php else : ?>

								<code>--</code>

							<?php endif;
						endif;
						?>

					</td>

					<td>
						<code>
							<?php if ( ! $is_unreadable ) :
								echo esc_html( substr( sprintf( '%o', $file->getPerms() ), - 4 ) );
							else :
								echo '-';
							endif; ?>
						</code>
					</td>

					<td>
						<code>
						<?php if ( $file->isLink() ) : ?>
							<span title="<?php echo esc_attr( wp_normalize_path( $file->getRealPath() ) ); ?>">
								<?php esc_html_e( 'Symlink', 'backupwordpress' ); ?>
							</span>
						<?php elseif ( $file->isDir() ) :
							esc_html_e( 'Folder', 'backupwordpress' );
						else :
							esc_html_e( 'File', 'backupwordpress' );
						endif; ?>
						</code>
					</td>

					<td class="column-format">

						<?php if ( $is_unreadable ) : ?>

							<strong title="<?php esc_attr_e( 'Unreadable files won\'t be backed up.', 'backupwordpress' ); ?>">
								<?php esc_html_e( 'Unreadable', 'backupwordpress' ); ?>
							</strong>

						<?php elseif ( $is_excluded ) : ?>

							<strong><?php esc_html_e( 'Excluded', 'backupwordpress' ); ?></strong>

						<?php else :

							$exclude_path = $file->getPathname();

							// Excluded directories need to be trailingslashed.
							if ( $file->isDir() ) :
								$exclude_path = trailingslashit( wp_normalize_path( $file->getPathname() ) );
							endif; ?>

							<a href="<?php echo esc_url( wp_nonce_url(
								add_query_arg( array(
									'hmbkp_schedule_id'      => $schedule->get_id(),
									'action'                 => 'hmbkp_add_exclude_rule',
									'hmbkp_exclude_pathname' => urlencode( $exclude_path ),
									),
									admin_url( 'admin-post.php' )
								),
								'hmbkp-add-exclude-rule',
								'hmbkp-add-exclude-rule-nonce'
							) ); ?>" class="button-secondary">
								<?php esc_html_e( 'Exclude &rarr;', 'backupwordpress' ); ?>
							</a>

						<?php endif; ?>

					</td>

				</tr>

			<?php endforeach; ?>

		<?php else : ?>

			<tr>
				<td colspan="5">
					<span class="description"><?php esc_html_e( 'This folder is empty', 'backupwordpress' ); ?></span>
				</td>
			</tr>

		<?php endif; ?>

		</tbody>

	</table>

	<p class="submit">
		<a href="<?php echo esc_url( get_settings_url() ) ?>" class="button-primary">
			<?php esc_html_e( 'Done', 'backupwordpress' ); ?>
		</a>
	</p>

</div>
