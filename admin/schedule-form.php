<form method="post" class="hmbkp-form" novalidate data-schedule-action="<?php if ( isset( $is_new_schedule ) ) { ?>add<?php } else { ?>edit<?php } ?>">

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />

	<table class="form-table">

		<tbody>

			<tr valign="top">

				<th scope="row">
					<label for="hmbkp_schedule_type"><?php _e( 'Backup', 'hmbkp' ); ?></label>
				</th>

				<td>

					<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">

						<option<?php selected( $schedule->get_type(), 'complete' ); ?> value="complete"><?php _e( 'Both Database &amp; files', 'hmbkp' ); ?></option>

						<option<?php selected( $schedule->get_type(), 'file' ); ?> value="file"><?php _e( 'Files only', 'hmbkp' ); ?></option>

						<option<?php selected( $schedule->get_type(), 'database' ); ?> value="database"><?php _e( 'Database only', 'hmbkp' ); ?></option>

					</select>

				</td>

			</tr>

			<tr>

				<th scope="row">
					<label for="hmbkp_schedule_recurrence_type"><?php _e( 'Schedule', 'hmbkp' ); ?></label>
				</th>

				<td>

					<select name="hmbkp_schedule_recurrence[hmbkp_type]" id="hmbkp_schedule_recurrence_type">

						<option value="manually"><?php _e( 'Manual Only', 'hmbkp' ); ?></option>

				  <?php foreach ( $schedule->get_cron_schedules() as $cron_schedule => $cron_details ) : ?>

						<option <?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo esc_attr( $cron_schedule ); ?>">

							<?php esc_html_e( $cron_details['display'], 'hmbkp' ); ?>

						</option>

				  <?php endforeach; ?>

					</select>

				</td>

			</tr>

			<?php if ( ! $start_time = $schedule->get_schedule_start_time() )
				$start_time = time(); ?>

			<tr id="start-day" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_day_of_week"><?php _e( 'Start Day', 'hmbkp' ); ?></label>
				</th>

				<td>

					<select id="hmbkp_schedule_start_day_of_week" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_week]">

						<?php $weekdays = array(
							'monday' => __( 'Monday', 'hmbkp' ),
							'tuesday' => __( 'Tuesday', 'hmbkp' ),
							'wednesday' => __( 'Wednesday', 'hmbkp' ),
							'thursday' => __( 'Thursday', 'hmbkp' ),
							'friday' => __( 'Friday', 'hmbkp' ),
							'saturday' => __( 'Saturday', 'hmbkp' ),
							'sunday' => __( 'Sunday', 'hmbkp' )
						);

						foreach ( $weekdays as $key => $day ) : ?>

							<option value="<?php echo esc_attr( $key ) ?>" <?php selected( strtolower( date( 'l', $start_time ) ), $key ); ?>><?php echo esc_html( $day ); ?></option>

						<?php endforeach; ?>

					</select>

				</td>

			</tr>

			<tr id="start-date" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_day_of_month"><?php _e( 'Start Day of Month', 'hmbkp' ); ?></label>
				</th>

				<td>
					<input type="number" min="0" max="31" step="1" id="hmbkp_schedule_start_day_of_month" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_month]" value="<?php echo esc_attr( date( 'j', $start_time ) ); ?>">
				</td>

			</tr>

			<tr id="schedule-start" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_hours"><?php _e( 'Start time', 'hmbkp' ); ?></label>
				</th>

				<td>
					<span class="field-group">

						<label for="hmbkp_schedule_start_hours"><input type="number" min="0" max="23" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_hours]" id="hmbkp_schedule_start_hours" value="<?php echo esc_attr( date( 'G', $start_time ) ); ?>">

						<?php _e( 'Hours', 'hmbkp' ); ?></label>

						<label for="hmbkp_schedule_start_minutes"><input type="number" min="0" max="59" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_minutes]" id="hmbkp_schedule_start_minutes" value="<?php echo esc_attr( (float) date( 'i', $start_time ) ); ?>">

						<?php _e( 'Minutes', 'hmbkp' ); ?></label>

					</span>

					<p class="twice-js description"><?php _e( 'The second backup will run 12 hours after the first', 'hmbkp' ); ?></p>

				</td>

			</tr>

			<tr>

				<th scope="row">
					<label for="hmbkp_schedule_max_backups"><?php _e( 'Number of backups to store on this server', 'hmbkp' ); ?></label>
				</th>

				<td>

					<input type="number" id="hmbkp_schedule_max_backups" name="hmbkp_schedule_max_backups" min="1" step="1" value="<?php echo esc_attr( $schedule->get_max_backups() ); ?>" />

					<p class="description">

						<?php printf( __( 'Past this limit older backups will be deleted automatically.', 'hmbkp' ) ); ?>

						<?php if ( $schedule->is_filesize_cached() ) {
							printf( __( 'This schedule will store a maximum of %s of backups', 'hmbkp' ), '<code>' . size_format( $schedule->get_filesize() * $schedule->get_max_backups() ) . '</code>' );
						} ?>

					</p>

				</td>

			</tr>

			<?php foreach ( HMBKP_Services::get_services( $schedule ) as $service )
				$service->field(); ?>

		</tbody>

	</table>

	<p class="submit">
		<button type="button" class="button-secondary" href="<?php echo esc_url( remove_query_arg( array( 'hmbkp_panel', 'action' ) ) ); ?>">Close</button>

		<button type="submit" class="button-primary"><?php _e( 'Save Changes', 'hmbkp' ); ?></button>
	</p>

</form>