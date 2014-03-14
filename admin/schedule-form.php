<form method="post" class="hmbkp-form" novalidate data-schedule-action="<?php if ( isset( $is_new_schedule ) ) { ?>add<?php } else { ?>edit<?php } ?>">

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />

	<fieldset class="hmbkp-edit-schedule-form">

		<legend><?php _e( 'Schedule Settings', 'backupwordpress' ); ?></legend>

		<div>

			<label for="hmbkp_schedule_type"><?php _e( 'Backup', 'backupwordpress' ); ?></label>

				<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">

				<option<?php selected( $schedule->get_type(), 'complete' ); ?> value="complete"><?php _e( 'Both Database &amp; files', 'backupwordpress' ); ?></option>

				<option<?php selected( $schedule->get_type(), 'file' ); ?> value="file"><?php _e( 'Files only', 'backupwordpress' ); ?></option>

				<option<?php selected( $schedule->get_type(), 'database' ); ?> value="database"><?php _e( 'Database only', 'backupwordpress' ); ?></option>

			</select>

		</div>

		<div>

			<label for="hmbkp_schedule_recurrence_type"><?php _e( 'Schedule', 'backupwordpress' ); ?></label>

			<select name="hmbkp_schedule_recurrence[hmbkp_type]" id="hmbkp_schedule_recurrence_type">

				<option value="manually"><?php _e( 'Manual Only', 'backupwordpress' ); ?></option>

		  <?php foreach ( $schedule->get_cron_schedules() as $cron_schedule => $cron_details ) : ?>

				<option <?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo esc_attr( $cron_schedule ); ?>">

					<?php esc_html_e( $cron_details['display'], 'backupwordpress' ); ?>

				</option>

		  <?php endforeach; ?>

			</select>

		</div>

		<?php if ( ! $start_time = $schedule->get_schedule_start_time() )
			$start_time = time(); ?>

		<div id="start-day" class="recurring-setting">

			<label for="hmbkp_schedule_start_day_of_week"><?php _e( 'Start Day', 'backupwordpress' ); ?></label>

			<select id="hmbkp_schedule_start_day_of_week" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_week]">

				<?php $weekdays = array(
					'monday' => __( 'Monday', 'backupwordpress' ),
					'tuesday' => __( 'Tuesday', 'backupwordpress' ),
					'wednesday' => __( 'Wednesday', 'backupwordpress' ),
					'thursday' => __( 'Thursday', 'backupwordpress' ),
					'friday' => __( 'Friday', 'backupwordpress' ),
					'saturday' => __( 'Saturday', 'backupwordpress' ),
					'sunday' => __( 'Sunday', 'backupwordpress' )
				);

				foreach ( $weekdays as $key => $day ) : ?>

					<option value="<?php echo esc_attr( $key ) ?>" <?php selected( strtolower( date( 'l', $start_time ) ), $key ); ?>><?php echo esc_html( $day ); ?></option>

				<?php endforeach; ?>

			</select>

		</div>

		<div id="start-date" class="recurring-setting">

			<label for="hmbkp_schedule_start_day_of_month"><?php _e( 'Start Day of Month', 'backupwordpress' ); ?></label>

			<input type="number" min="0" max="31" step="1" id="hmbkp_schedule_start_day_of_month" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_month]" value="<?php echo esc_attr( date( 'j', $start_time ) ); ?>">

		</div>

		<div id="schedule-start" class="recurring-setting">

			<label for="hmbkp_schedule_start_hours"><?php _e( 'Start time', 'backupwordpress' ); ?></label>

			<span class="field-group">

				<label for="hmbkp_schedule_start_hours"><input type="number" min="0" max="23" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_hours]" id="hmbkp_schedule_start_hours" value="<?php echo esc_attr( date( 'G', $start_time ) ); ?>">

				<?php _e( 'Hours', 'backupwordpress' ); ?></label>

				<label for="hmbkp_schedule_start_minutes"><input type="number" min="0" max="59" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_minutes]" id="hmbkp_schedule_start_minutes" value="<?php echo esc_attr( (float) date( 'i', $start_time ) ); ?>">

				<?php _e( 'Minutes', 'backupwordpress' ); ?></label>

			</span>

			<p class="twice-js description"><?php _e( 'The second backup will run 12 hours after the first', 'backupwordpress' ); ?></p>

		</div>

		<div>

			<label for="hmbkp_schedule_max_backups"><?php _e( 'Number of backups to store on this server', 'backupwordpress' ); ?></label>

			<input type="number" name="hmbkp_schedule_max_backups" min="1" step="1" value="<?php echo esc_attr( $schedule->get_max_backups() ); ?>" />

			<p class="description">

				<?php printf( __( 'Past this limit older backups will be deleted automatically.', 'hmnkp' ) ); ?>

				<?php if ( $schedule->is_filesize_cached() ) {
					printf( __( 'This schedule will store a maximum of %s of backups', 'backupwordpress' ), '<code>' . size_format( $schedule->get_filesize() * $schedule->get_max_backups() ) . '</code>' );
				} ?>

			</p>

		</div>

		<?php foreach ( HMBKP_Services::get_services( $schedule ) as $service )
			$service->field(); ?>

		<p class="submit">

			<button type="submit" class="button-primary"><?php _e( 'Update', 'backupwordpress' ); ?></button>

		</p>

	</fieldset>

</form>
