<form method="post" class="hmbkp-form">

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo $schedule->get_id(); ?>" />

	<fieldset class="hmbkp-edit-schedule-form">

		<legend><?php _e( 'Backup Schedule', 'hmbkp' ); ?></legend>

    	<label>

    		<?php _e( 'Name', 'hmbkp' ); ?>

    		<input type="text" name="hmbkp_schedule_name" placeholder="<?php _e( 'Complete Weekly', 'hmbkp' ); ?>&hellip;" value="<?php echo $schedule->get_name(); ?>" />

    	</label>

    	<label>

    		<?php _e( 'Backup', 'hmbkp' ); ?>

    		<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">
    			<option<?php selected( $schedule->get_type(), 'complete'); ?> value="complete"><?php _e( 'Both Database &amp; files', 'hmbkp' ); ?></option>
    			<option<?php selected( $schedule->get_type(), 'file'); ?> value="file"><?php _e( 'Files only', 'hmbkp' ); ?></option>
    			<option<?php selected( $schedule->get_type(), 'database'); ?> value="database"><?php _e( 'Database only', 'hmbkp' ); ?></option>
    		</select>

    	</label>

    	<label class="hmbkp-excludes<?php echo $schedule->get_type() == 'database' ? ' hidden' : ''; ?>">

    		<?php _e( 'Files', 'hmbkp' ); ?>

    		<button type="button" class="button-secondary hmbkp-toggle-fieldset" data-hmbkp-fieldset="hmbkp-edit-schedule-excludes-form"><?php _e( 'Manage Excludes', 'hmbkp' ); ?> &rarr;</button>

    	</label>


    	<label>

    		<?php _e( 'Schedule', 'hmbkp' ); ?>

    		<select name="hmbkp_schedule_reoccurrence" id="hmbkp_schedule_reoccurrence">

<?php foreach( wp_get_schedules() as $cron_schedule => $cron_details ) : ?>

    		    <option<?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo $cron_schedule; ?>"><?php echo $cron_details['display']; ?></option>

<?php endforeach; ?>

    		</select>

    	</label>

    	<label>

    		<?php _e( 'From', 'hmbkp' ); ?>

    		<input type="datetime-local" name="hmbkp_schedule_date" value="<?php echo $schedule->get_next_occurrence(); ?>" />

    	</label>

<!--
    	<label>

    		<?php _e( 'Services', 'hmbkp' ); ?>

    		<button type="button" class="button-secondary hmbkp-toggle-fieldset" data-hmbkp-fieldset="hmbkp-edit-schedule-services-form"><?php _e( 'Manage Services', 'hmbkp' ); ?> &rarr;</button>

    	</label>
-->

    	<label>

    		<?php _e( 'Number of backups to keep', 'hmbkp' ); ?>

    		<input type="number" name="hmbkp_schedule_max_backups" min="1" step="1" value="<?php echo $schedule->get_max_backups(); ?>" />

    	</label>

    	<p class="submit">

		    <button type="submit" class="button-primary"><?php _e( 'Update', 'hmbkp' ); ?></button>

		</p>

	</fieldset>

	<?php include( HMBKP_PLUGIN_PATH . '/admin.schedule-form-excludes.php' ); ?>

<!--
	<fieldset class="hmbkp-edit-schedule-services-form">

		<legend>Manage Services</legend>

		<label>

			Services

			<textarea></textarea>

		</label>

	</fieldset>
-->

</form>