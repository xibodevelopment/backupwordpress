<table class="widefat">

	<thead>

		<tr>

			<th scope="col"><?php hmbkp_backups_number( $schedule ); ?></th>
			<th scope="col"><?php _e( 'Size', 'backupwordpress' ); ?></th>
			<th scope="col"><?php _e( 'Type', 'backupwordpress' ); ?></th>
			<th scope="col"><?php _e( 'Actions', 'backupwordpress' ); ?></th>

		</tr>

	</thead>

	<tbody>

		<?php if ( $schedule->get_backups() ) {

			$schedule->delete_old_backups();

			foreach ( $schedule->get_backups() as $file ) {

				if ( ! file_exists( $file ) ) {
					continue;
				}

				hmbkp_get_backup_row( $file, $schedule );

			}

		} else { ?>

			<tr>
				<td class="hmbkp-no-backups" colspan="4"><?php _e( 'This is where your backups will appear once you have some.', 'backupwordpress' ); ?></td>
			</tr>

		<?php } ?>

	</tbody>

</table>