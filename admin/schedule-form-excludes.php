<form method="post" class="hmbkp-form hmbkp-edit-schedule-excludes-form" novalidate>

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />

	<?php if ( $schedule->get_excludes() ) : ?>

		<table class="widefat">

			<thead>
				<tr>
					<th><?php _e( 'Excluded', 'hmbkp' ); ?></th>
				</tr>
			</thead>

			<tbody>

				<?php foreach ( $schedule->get_excludes() as $key => $exclude ) : ?>

					<tr>

						<td data-hmbkp-exclude-rule="<?php echo esc_attr( $exclude ); ?>">

						<span class="code"><?php echo esc_attr( str_ireplace( untrailingslashit( $schedule->get_root() ), '', $exclude ) ); ?></span>

						<span><?php echo size_format( hmbkp_get_exclude_rule_file_size( $exclude, $schedule ) ); ?></span>

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

			<?php hmbkp_file_list( $schedule, null, 'get_included_files' ); ?>

		</div>

		<p><?php printf( __( 'Your site is now %s. Backups will be compressed and so will be smaller.', 'hmbkp' ), '<code>' . esc_html( $schedule->get_formatted_file_size( false ) ) . '</code>' ); ?></p>

</form>