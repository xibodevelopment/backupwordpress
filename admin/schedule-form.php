<h3><?php esc_html_e( 'Settings', 'backupwordpress' ); ?></h3>

<?php $hmbkp_form_errors = hmbkp_get_settings_errors(); ?>

<?php if ( ! empty( $hmbkp_form_errors ) ) { ?>

	<div id="hmbkp-warning" class="error settings-error">

		<?php foreach ( $hmbkp_form_errors as $error ) { ?>
			<p><strong><?php echo esc_html( $error ); ?></strong></p>
		<?php } ?>

	</div>

<?php } ?>

<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />
	<input type="hidden" name="action" value="hmbkp_edit_schedule_submit" />

	<?php wp_nonce_field( 'hmbkp-edit-schedule', 'hmbkp-edit-schedule-nonce' ); ?>

	<table class="form-table">

		<tbody>

			<tr valign="top">

				<th scope="row">
					<label for="hmbkp_schedule_type"><?php _e( 'Backup', 'backupwordpress' ); ?></label>
				</th>

				<td>

					<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">

						<option<?php selected( $schedule->get_type(), 'complete' ); ?> value="complete"><?php _e( 'Both Database &amp; files', 'backupwordpress' ); ?></option>

						<option<?php selected( $schedule->get_type(), 'file' ); ?> value="file"><?php _e( 'Files only', 'backupwordpress' ); ?></option>

						<option<?php selected( $schedule->get_type(), 'database' ); ?> value="database"><?php _e( 'Database only', 'backupwordpress' ); ?></option>

					</select>

				</td>

			</tr>

			<tr>

				<th scope="row">
					<label for="hmbkp_schedule_recurrence_type"><?php _e( 'Schedule', 'backupwordpress' ); ?></label>
				</th>

				<td>

					<select name="hmbkp_schedule_recurrence[hmbkp_type]" id="hmbkp_schedule_recurrence_type">

						<option value="manually"><?php _e( 'Manual Only', 'backupwordpress' ); ?></option>

						<?php foreach ( $schedule->get_cron_schedules() as $cron_schedule => $cron_details ) : ?>

								<option <?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo esc_attr( $cron_schedule ); ?>">

									<?php esc_html_e( $cron_details['display'], 'backupwordpress' ); ?>

								</option>

						<?php endforeach; ?>

					</select>

				</td>

			</tr>

			<?php if ( ! $start_time = $schedule->get_schedule_start_time( false ) ) {
				$start_time = time();
			} ?>

			<tr id="start-day" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_day_of_week"><?php _e( 'Start Day', 'backupwordpress' ); ?></label>
				</th>

				<td>

					<select id="hmbkp_schedule_start_day_of_week" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_week]">

						<?php $weekdays = array(
							'monday'    => __( 'Monday',    'backupwordpress' ),
							'tuesday'   => __( 'Tuesday',   'backupwordpress' ),
							'wednesday' => __( 'Wednesday', 'backupwordpress' ),
							'thursday'  => __( 'Thursday',  'backupwordpress' ),
							'friday'    => __( 'Friday',    'backupwordpress' ),
							'saturday'  => __( 'Saturday',  'backupwordpress' ),
							'sunday'    => __( 'Sunday',    'backupwordpress' )
						);

						foreach ( $weekdays as $key => $day ) : ?>

							<option value="<?php echo esc_attr( $key ) ?>" <?php selected( strtolower( date_i18n( 'l', $start_time ) ), $key ); ?>><?php echo esc_html( $day ); ?></option>

						<?php endforeach; ?>

					</select>

				</td>

			</tr>

			<tr id="start-date" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_day_of_month"><?php _e( 'Start Day of Month', 'backupwordpress' ); ?></label>
				</th>

				<td>
					<input type="number" min="0" max="31" step="1" id="hmbkp_schedule_start_day_of_month" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_month]" value="<?php echo esc_attr( date_i18n( 'j', $start_time ) ); ?>">
				</td>

			</tr>

			<tr id="schedule-start" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_hours"><?php _e( 'Start Time', 'backupwordpress' ); ?></label>
				</th>

				<td>

					<span class="field-group">

						<label for="hmbkp_schedule_start_hours"><input type="number" min="0" max="23" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_hours]" id="hmbkp_schedule_start_hours" value="<?php echo esc_attr( date_i18n( 'G', $start_time ) ); ?>">

						<?php _e( 'Hours', 'backupwordpress' ); ?></label>

						<label for="hmbkp_schedule_start_minutes"><input type="number" min="0" max="59" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_minutes]" id="hmbkp_schedule_start_minutes" value="<?php echo esc_attr( (float) date_i18n( 'i', $start_time ) ); ?>">

						<?php _e( 'Minutes', 'backupwordpress' ); ?></label>

					</span>

					<p class="twice-js description<?php if ( $schedule->get_reoccurrence() !== 'hmbkp_fortnightly' ) { ?> hidden<?php } ?>"><?php _e( 'The second backup will run 12 hours after the first', 'backupwordpress' ); ?></p>

				</td>

			</tr>

			<tr>

				<th scope="row">
					<label for="hmbkp_schedule_max_backups"><?php _e( 'Number of backups to store on this server', 'backupwordpress' ); ?></label>
				</th>

				<td>

					<input type="number" id="hmbkp_schedule_max_backups" name="hmbkp_schedule_max_backups" min="1" step="1" value="<?php echo esc_attr( $schedule->get_max_backups() ); ?>" />

					<p class="description">

						<?php printf( __( 'Past this limit older backups will be deleted automatically.', 'backupwordpress' ) ); ?>

						<?php if ( $schedule->is_site_size_cached() ) {
							printf( __( 'This schedule will store a maximum of %s of backups.', 'backupwordpress' ), '<code>' . size_format( $schedule->get_site_size() * $schedule->get_max_backups() ) . '</code>' );
						} ?>

					</p>

				</td>

			</tr>

			<?php foreach ( HMBKP_Services::get_services( $schedule ) as $service ) {
				$service->field();
			} ?>

		</tbody>

	</table>

	<p class="submit">
		<button type="submit" class="button-primary"><?php _e( 'Done', 'backupwordpress' ); ?></button>
	</p>


</form>