<?php

$schedule_a = new HMBKP_Scheduled_Backup( 'test' );
$schedule_a->set_type( 'complete' );
$schedule_a->set_excludes = array( 'wp-admin', '/wp-content/' );

$schedule_b = new HMBKP_Scheduled_Backup( 'backup' );
$schedule_b->set_type( 'file' );
$schedule_b->set_excludes = array( 'wp-admin', '/wp-content/' );
$schedule_b->set_reoccurrence( 'weekly' );
$schedule_b->schedule();

?>

<?php $schedules = new HMBKP_Schedules; ?>

<?php if ( count( $schedules->get_schedules() ) > 1 ) : ?>

<h3>Backup Schedules <button class="add-new-h2 button-secondary" type="button">Add Schedule</button></h3>

<div class="hmbkp_schedule_tabs">

	<ul class="subsubsub">
	
	<?php foreach ( $schedules->get_schedules() as $schedule ) : ?>
	
		<li><a href="#hmbkp_schedule_<?php echo $schedule->get_slug(); ?>"><?php echo $schedule->get_name(); ?></a></li>
	
	<?php endforeach; ?>
	
	</ul>

<?php endif; ?>

<?php foreach ( $schedules->get_schedules() as $schedule ) : ?>

	<table id="hmbkp_schedule_<?php echo $schedule->get_slug(); ?>" class="widefat">
	
	    <thead>
	
			<tr>
	
				<th scope="col" colspan="3"><?php require( 'admin.schedule.php' ); ?></th>
	
			</tr>
	
	    </thead>
	    
	    <tfoot>
	    	<tr>
	    		<th scope="col"><?php printf( _n( '1 backup completed', '%d backups completed', count( $schedule->get_backups() ),  'hmbkp' ), count( $schedule->get_backups() ) ); ?></th>
	    		<th scope="col"><?php _e( 'Size', 'hmbkp' ); ?></th>
	    		<th scope="col"><?php _e( 'Actions', 'hmbkp' ); ?></th>
	    	</tr>
	    </tfoot>
	
	    <tbody>
	
    <?php if ( $schedule->get_backups() ) :

        foreach ( $schedule->get_backups() as $file ) :

            if ( ! file_exists( $file ) )
        		continue;

            hmbkp_get_backup_row( $file, $schedule );

        endforeach;
        
    else : ?>
    
    <tr>
    	
    	<td class="hmbkp-no-backups" colspan="3">This is where your backups will appear once you have one.</td>
    
    </tr>

    <?php endif; ?>
	
	    </tbody>
	
	</table>

<?php endforeach; ?>

</div>