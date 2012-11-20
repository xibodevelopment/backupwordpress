<?php $schedules = new HMBKP_Schedules; ?>

<div>

	<ul class="subsubsub">

	<?php foreach ( $schedules->get_schedules() as $schedule ) : ?>

		<li<?php if ( $schedule->get_status() ) { ?> class="hmbkp-running"<?php } ?>><a<?php if ( ! empty ( $_GET['hmbkp_schedule_id'] ) && $schedule->get_id() == $_GET['hmbkp_schedule_id'] ) { ?> class="current"<?php } ?> href="<?php echo esc_url( add_query_arg( 'hmbkp_schedule_id', $schedule->get_id(), HMBKP_ADMIN_URL ) ); ?> "><?php echo esc_attr( $schedule->get_name() ); ?> <span class="count">(<?php echo count( $schedule->get_backups() ); ?>)</span></a></li>

	<?php endforeach; ?>

		<li><a class="fancybox" href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_add_schedule_load' ), admin_url( 'admin-ajax.php' ) ) ); ?>"> + <?php _e( 'add schedule', 'hmbkp' ); ?></a></li>

	</ul>

<?php if ( ! empty( $_GET['hmbkp_schedule_id'] ) )
	$schedule = new HMBKP_Scheduled_Backup( $_GET['hmbkp_schedule_id'] );

else
	$schedule = reset( $schedules->get_schedules() );

	if ( ! $schedule )
		return; ?>

	<div data-hmbkp-schedule-id="<?php echo esc_attr( $schedule->get_id() ); ?>" class="hmbkp_schedule">

		<?php require( HMBKP_PLUGIN_PATH . '/admin/schedule.php' ); ?>

		<table class="widefat">

		    <thead>

				<tr>

					<th scope="col"><?php printf( _n( '1 backup completed', '%d backups completed', count( $schedule->get_backups() ),  'hmbkp' ), count( $schedule->get_backups() ) ); ?></th>
		    		<th scope="col"><?php _e( 'Size', 'hmbkp' ); ?></th>
		    		<th scope="col"><?php _e( 'Type', 'hmbkp' ); ?></th>
		    		<th scope="col"><?php _e( 'Actions', 'hmbkp' ); ?></th>

				</tr>

		    </thead>

		    <tbody>

    	<?php if ( $schedule->get_backups() ) :

    	    foreach ( $schedule->get_backups() as $file ) :

    	        if ( ! file_exists( $file ) )
    	    		continue;

    	        hmbkp_get_backup_row( $file, $schedule );

    	    endforeach;

    	else : ?>

    	<tr>

    		<td class="hmbkp-no-backups" colspan="3"><?php _e( 'This is where your backups will appear once you have one.', 'hmbkp' ); ?></td>

    	</tr>

    	<?php endif; ?>

		    </tbody>

		</table>

	</div>

</div>