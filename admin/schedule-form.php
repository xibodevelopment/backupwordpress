<form method="post" class="hmbkp-form" novalidate data-schedule-action="<?php if ( isset( $is_new_schedule ) ) { ?>add<?php } else { ?>edit<?php } ?>">

	<input type="hidden" name="hmbkp_schedule_id" value="<?php esc_attr_e( $schedule->get_id() ); ?>" />

	<fieldset class="hmbkp-edit-schedule-form">

		<legend><?php _e( 'Schedule Settings', 'hmbkp' ); ?></legend>

    	<div>

    		<label for="hmbkp_schedule_type"><?php _e( 'Backup', 'hmbkp' ); ?></label>

				<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">

    			<option<?php selected( $schedule->get_type(), 'complete' ); ?> value="complete"><?php _e( 'Both Database &amp; files', 'hmbkp' ); ?></option>

    			<option<?php selected( $schedule->get_type(), 'file' ); ?> value="file"><?php _e( 'Files only', 'hmbkp' ); ?></option>

    			<option<?php selected( $schedule->get_type(), 'database' ); ?> value="database"><?php _e( 'Database only', 'hmbkp' ); ?></option>

    		</select>

    	</div>

    	<div>

    		<label for="hmbkp_schedule_recurrence_type"><?php _e( 'Schedule', 'hmbkp' ); ?></label>

    		<select name="hmbkp_schedule_recurrence[hmbkp_type]" id="hmbkp_schedule_recurrence_type">

    			<option value="manually"><?php _e( 'Manual Only', 'hmbkp' ); ?></option>

                <?php foreach ( $schedule->get_cron_schedules() as $cron_schedule => $cron_details ) : ?>

                    <option<?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php esc_attr_e( $cron_schedule ); ?>"><?php esc_html_e( $cron_details['display'], 'hmbkp' ); ?></option>

                <?php endforeach; ?>

    		</select>

			</div>

		<?php $recurrence_settings = $schedule->get_recurrence_settings(); ?>

		<div id="start-day" class="recurring-setting">

			<label for="hmbkp_schedule_start_day_of_week"><?php _e( 'Start Day', 'hmbkp' ); ?></label>

			<select id="hmbkp_schedule_start_day_of_week" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_week]">

				<?php
				$weekdays = array(
					'monday',
					'tuesday',
					'wednesday',
					'thursday',
					'friday',
					'saturday',
					'sunday'
				);

				foreach ( $weekdays as $day ) : ?>

					<option value="<?php echo esc_attr( $day ) ?>" <?php selected( ( isset( $recurrence_settings['day_of_week'] ) ) ? $recurrence_settings['day_of_week'] : '', $day ); ?>><?php echo esc_html( ucwords( $day )); ?></option>

				<?php endforeach; ?>

			</select>

		</div>

		<div id="start-date" class="recurring-setting">

			<label for="hmbkp_schedule_start_day_of_month"><?php _e( 'Start Day of month', 'hmbkp' ); ?></label>

			<input type="number" min="0" max="31" step="1" value="1" id="hmbkp_schedule_start_day_of_month" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_month]" value="<?php echo esc_attr( ( isset( $recurrence_settings['day_of_month'] ) ) ? $recurrence_settings['day_of_month'] : '1' ); ?>">

		</div>

		<div id="schedule-start" class="recurring-setting">

			<p><?php _e( 'Start time', 'backupwordpress' ); ?></p>

			<input type="number" min="1" max="12" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_hours]" id="hmbkp_schedule_start_hours" value="<?php echo esc_attr( ( isset( $recurrence_settings['hours'] ) ) ? $recurrence_settings['hours'] : '11' ); ?>">

			<label for="hmbkp_schedule_start_hours"><?php _e( 'Hours', 'hmbkp' ); ?></label>

			<input type="number" min="0" max="59" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_minutes]" id="hmbkp_schedule_start_minutes" value="<?php echo esc_attr( ( isset( $recurrence_settings['minutes'] ) ) ? $recurrence_settings['minutes'] : '00' ); ?>">

			<label for="hmbkp_schedule_start_minutes"><?php _e( 'Minutes', 'hmbkp' ); ?></label>

			<select id="hmbkp_schedule_start_ampm" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_ampm]">

				<option value="am" <?php selected( ( isset( $recurrence_settings['ampm'] ) ) ? $recurrence_settings['ampm'] : '', 'am' ); ?>><?php _e( 'AM', 'hmbkp' ); ?></option>

				<option value="pm" <?php selected( ( isset( $recurrence_settings['ampm'] ) ) ? $recurrence_settings['ampm'] : '', 'pm' ); ?>><?php _e( 'PM', 'hmbkp' ); ?></option>

			</select>

			<label for="hmbkp_schedule_start_ampm"><?php _e( 'AM/PM', 'hmbkp' ); ?></label>

			<p class="description">If twice daily, second backup will fire 12 hours after set time.</p>
		</div>

		<div>

    		<label for="hmbkp_schedule_max_backups"><?php _e( 'Number of backups to store on this server', 'hmbkp' ); ?></label>

    		<input type="number" id="hmbkp_schedule_max_backups" name="hmbkp_schedule_max_backups" min="1" step="1" value="<?php esc_attr_e( $schedule->get_max_backups() ); ?>" />

            <p class="description"><?php printf( __( 'Past this limit older backups will be deleted automatically. This schedule will store a maximum of %s of backups', 'hmbkp' ), '<code>' . size_format( $schedule->get_filesize() * $schedule->get_max_backups() ) . '</code>' ); ?></p>

    	</div>

        <?php

				foreach ( HMBKP_Services::get_services( $schedule ) as $service )
					$service->field();

				?>

    	<p class="submit">

		    <button type="submit" class="button-primary"><?php _e( 'Update', 'hmbkp' ); ?></button>

		</p>

	</fieldset>

</form>
