<?php

$schedule_slug = 'new';

if ( ! empty( $schedule ) )
	$schedule_slug = $schedule->get_slug(); ?>

<form method="post" class="hmbkp-form">

	<h3>Edit Backup Schedule</h3>
	
	<input type="hidden" name="hmbkp_edit_schedule" value="<?php echo $schedule->get_slug(); ?>" />

    <label>
    	
    	Name
    	
    	<input type="text" placeholder="Complete Weekly" value="<?php echo $schedule->get_name(); ?>" />
    	
    </label>

    <label for="hmbkp_schedule_type">
    
    	Backup
    	
    	<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">
    		<option<?php selected( $schedule->get_type(), 'complete'); ?> value="complete">Database &amp; files</option>
    		<option<?php selected( $schedule->get_type(), 'file'); ?> value="files">Only files</option>
    		<option<?php selected( $schedule->get_type(), 'database'); ?> value="database">Only database</option>
    	</select>

    </label>
    
    <label for="hmbkp_schedule_excludes">
    
    	Exclude
    	
    	<button type="button" class="button-secondary">Manage Excludes &rarr;</button>
    					
    </label>
    
    
    <label for="hmbkp_schedule_reoccurrence">
    
    	Schedule
    	
    	<select name="hmbkp_schedule_reoccurrence" id="hmbkp_schedule_reoccurrence">
    	
    		<?php foreach( wp_get_schedules() as $cron_schedule => $cron_details ) : ?>
    		
    			<option<?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo $cron_schedule; ?>"><?php echo $cron_details['display']; ?></option>
    			
    		<?php endforeach; ?>
    	
    	</select>
    	
    </label>
    
    <label>
    
    	From
    	
    	<input validate type="date" value="<?php echo $schedule->get_next_occurrence(); ?>" />
    	
    </label>
    
    <label>
    	
    	Services
    	
    	<button type="button" class="button-secondary">Manage Services &rarr;</button>
    
    </label>
    
    <label>
    	
    	Number of previous backups to keep
    	
    	<input type="number" min="1" step="1" value="<?php echo $schedule->get_max_backups(); ?>" />
    
    </label>
    
    <p class="submit">
    
	    <button type="submit" class="button-primary">Update</button>
	    
	</p>

</form>