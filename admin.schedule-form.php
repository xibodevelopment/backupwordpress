<?php

$schedule_slug = 'new';

if ( ! empty( $schedule ) )
	$schedule_slug = $schedule->get_slug(); ?>

<form method="post" class="hmbkp-add-new-schedule-form">

    <label>
    	
    	Name
    	
    	<input type="text" placeholder="Complete Weekly" />
    	
    </label>

    <label for="hmbkp_<?php echo $schedule_slug; ?>_schedule_type">
    
    	Backup
    	
    	<select name="hmbkp_<?php echo $schedule_slug; ?>_schedule_type" id="hmbkp_<?php echo $schedule_slug; ?>_schedule_type">
    		<option<?php selected( $schedule->get_type(), 'complete'); ?> value="complete">Complete (database &amp; files)</option>
    		<option<?php selected( $schedule->get_type(), 'files'); ?> value="files">Files</option>
    		<option<?php selected( $schedule->get_type(), 'database'); ?> value="database">Database</option>
    	</select>

    </label>
    
    <label for="hmbkp_<?php echo $schedule_slug; ?>_schedule_excludes">
    
    	Exclude
    	
    	<button type="button" class="button-secondary">Manage Excludes</button>
    					
    </label>
    
    
    <label for="hmbkp_<?php echo $schedule_slug; ?>_schedule_reoccurrence">
    
    	Schedule
    	
    	<select name="hmbkp_<?php echo $schedule_slug; ?>_schedule_reoccurrence" id="hmbkp_<?php echo $schedule_slug; ?>_schedule_reoccurrence">
    	
    		<?php foreach( wp_get_schedules() as $cron_schedule => $cron_details ) : ?>
    		
    			<option<?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo $cron_schedule; ?>"><?php echo $cron_details['display']; ?></option>
    			
    		<?php endforeach; ?>
    	
    	</select>
    	
    </label>
    
    <label>
    
    	From
    	
    	<input validate type="datetime-local" value="<?php echo $schedule->get_next_occurrence(); ?>" />
    	
    </label>
    
    <label>
    	
    	Services
    	
    	<button type="button" class="button-secondary">Manage Services</button>
    
    </label>
    
    <label>
    	
    	Number of previous backups to keep
    	
    	<input type="number" min="1" step="1" />
    
    </label>
    
    <button type="submit" class="button-secondary">Update</button>

</form>