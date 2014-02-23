<form method="post" class="hmbkp-form" novalidate data-schedule-action="<?php if ( isset( $is_new_schedule ) ) { ?>add<?php } else { ?>edit<?php } ?>">

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />

	<fieldset class="hmbkp-edit-schedule-form">

		<legend><?php _e( 'Schedule Settings', 'backupwordpress' ); ?></legend>

    	<label>

    		<?php _e( 'Backup', 'backupwordpress' ); ?>

    		<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">
    			<option<?php selected( $schedule->get_type(), 'complete' ); ?> value="complete"><?php _e( 'Both Database &amp; files', 'backupwordpress' ); ?></option>
    			<option<?php selected( $schedule->get_type(), 'file' ); ?> value="file"><?php _e( 'Files only', 'backupwordpress' ); ?></option>
    			<option<?php selected( $schedule->get_type(), 'database' ); ?> value="database"><?php _e( 'Database only', 'backupwordpress' ); ?></option>
    		</select>

    	</label>

    	<label>

    		<?php _e( 'Schedule', 'backupwordpress' ); ?>

    		<select name="hmbkp_schedule_reoccurrence" id="hmbkp_schedule_reoccurrence">

    			<option value="manually"><?php _e( 'Manual Only', 'backupwordpress' ); ?></option>

          <?php foreach ( $schedule->get_cron_schedules() as $cron_schedule => $cron_details ) : ?>

         		<option <?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo esc_attr( $cron_schedule ); ?>">

					<?php esc_html_e( $cron_details['display'], 'backupwordpress' ); ?>

				</option>

          <?php endforeach; ?>

    		</select>

    	</label>

    	<label>

    		<?php _e( 'Number of backups to store on this server', 'backupwordpress' ); ?>

    		<input type="number" name="hmbkp_schedule_max_backups" min="1" step="1" value="<?php echo esc_attr( $schedule->get_max_backups() ); ?>" />

            <p class="description"><?php printf( __( 'Past this limit older backups will be deleted automatically. This schedule will store a maximum of %d of backups', 'backupwordpress' ), '<code>' . size_format( $schedule->get_filesize() * $schedule->get_max_backups() ) . '</code>' ); ?></p>

    	</label>

        <?php

				foreach ( HMBKP_Services::get_services( $schedule ) as $service )
					$service->field();

				?>

    	<p class="submit">

		    <button type="submit" class="button-primary"><?php _e( 'Update', 'backupwordpress' ); ?></button>

		</p>

	</fieldset>

</form>