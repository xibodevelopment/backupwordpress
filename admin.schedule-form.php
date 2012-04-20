<form method="post" class="hmbkp-form">

	<input type="hidden" name="hmbkp_schedule_slug" value="<?php echo $schedule->get_slug(); ?>" />

	<fieldset class="hmbkp-edit-schedule-form">

		<legend>Edit Backup Schedule</legend>

    	<label>

    		Name

    		<input type="text" name="hmbkp_schedule_name" placeholder="Complete Weekly" value="<?php echo $schedule->get_name(); ?>" />

    	</label>

    	<label>

    		Backup

    		<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">
    			<option<?php selected( $schedule->get_type(), 'complete'); ?> value="complete">Both Database &amp; files</option>
    			<option<?php selected( $schedule->get_type(), 'file'); ?> value="file">Files only</option>
    			<option<?php selected( $schedule->get_type(), 'database'); ?> value="database">Database only</option>
    		</select>

    	</label>

    	<label class="hmbkp-excludes<?php echo $schedule->get_type() == 'database' ? ' hidden' : ''; ?>">

    		Files <coda><?php echo $schedule->get_filesize(); ?></code>

    		<button type="button" class="button-secondary hmbkp-toggle-fieldset" data-hmbkp-fieldset="hmbkp-edit-schedule-excludes-form">Manage Excludes &rarr;</button>

    	</label>


    	<label>

    		Schedule

    		<select name="hmbkp_schedule_reoccurrence" id="hmbkp_schedule_reoccurrence">

<?php foreach( wp_get_schedules() as $cron_schedule => $cron_details ) : ?>

    		    <option<?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo $cron_schedule; ?>"><?php echo $cron_details['display']; ?></option>

<?php endforeach; ?>

    		</select>

    	</label>

    	<label>

    		From

    		<input type="datetime-local" name="hmbkp_schedule_date" value="<?php echo $schedule->get_next_occurrence(); ?>" />

    	</label>

<!--
    	<label>

    		Services

    		<button type="button" class="button-secondary hmbkp-toggle-fieldset" data-hmbkp-fieldset="hmbkp-edit-schedule-services-form">Manage Services &rarr;</button>

    	</label>
-->

    	<label>

    		Number of backups to keep

    		<input type="number" name="hmbkp_schedule_max_backups" min="1" step="1" value="<?php echo $schedule->get_max_backups(); ?>" />

    	</label>

    	<p class="submit">

		    <button type="submit" class="button-primary">Update</button>

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