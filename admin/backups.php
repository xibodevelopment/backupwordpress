<?php $schedules = HMBKP_Schedules::get_instance(); ?>

<div>

	<ul class="subsubsub">

		<?php
		// possible titles
		$titles = array(
			'complete-hourly'      => __( 'Complete Hourly', 'hmbkp' ),
			'file-hourly'          => __( 'File Hourly', 'hmbkp' ),
			'database-hourly'      => __( 'Database Hourly', 'hmbkp' ),
			'complete-twicedaily'  => __( 'Complete Twicedaily', 'hmbkp' ),
			'file-twicedaily'      => __( 'File Twicedaily', 'hmbkp' ),
			'database-twicedaily'  => __( 'Database Twicedaily', 'hmbkp' ),
			'complete-daily'       => __( 'Complete Daily', 'hmbkp' ),
			'file-daily'           => __( 'File Daily', 'hmbkp' ),
			'database-daily'       => __( 'Database Daily', 'hmbkp' ),
			'complete-weekly'      => __( 'Complete Weekly', 'hmbkp' ),
			'file-weekly'          => __( 'File Weekly', 'hmbkp' ),
			'database-weekly'      => __( 'Database Weekly', 'hmbkp' ),
			'complete-fortnightly' => __( 'Complete Fortnightly', 'hmbkp' ),
			'file-fortnightly'     => __( 'File Fortnightly', 'hmbkp' ),
			'database-fortnightly' => __( 'Database Fortnightly', 'hmbkp' ),
			'complete-monthly'     => __( 'Complete Monthly', 'hmbkp' ),
			'file-monthly'         => __( 'File Monthly', 'hmbkp' ),
			'database-monthly'     => __( 'Database Monthly', 'hmbkp' ),
			'complete-manually'    => __( 'Complete Manually', 'hmbkp' ),
			'file-manually'        => __( 'File Manually', 'hmbkp' ),
			'database-manually'    => __( 'Database Manually', 'hmbkp' )
		);


		?>
	<?php foreach ( $schedules->get_schedules() as $schedule ) : ?>
		<li<?php if ( $schedule->get_status() ) { ?> class="hmbkp-running"<?php } ?>><a<?php if ( ! empty ( $_GET['hmbkp_schedule_id'] ) && $schedule->get_id() == $_GET['hmbkp_schedule_id'] ) { ?> class="current"<?php } ?> href="<?php echo esc_url( add_query_arg( 'hmbkp_schedule_id', $schedule->get_id(), HMBKP_ADMIN_URL ) ); ?> "><?php echo esc_html( $titles[$schedule->get_slug()] ); ?> <span class="count">(<?php echo count( $schedule->get_backups() ); ?>)</span></a></li>

	<?php endforeach; ?>

		<li><a class="colorbox" href="<?php esc_attr_e( esc_url( add_query_arg( array( 'action' => 'hmbkp_add_schedule_load' ), admin_url( 'admin-ajax.php' ) ) ) ); ?>"> + <?php _e( 'add schedule', 'hmbkp' ); ?></a></li>

	</ul>

<?php

if ( ! empty( $_GET['hmbkp_schedule_id'] ) )
	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );

else {

	$schedules = $schedules->get_schedules();

	$schedule = reset( $schedules );

}

	if ( ! $schedule )
		return; ?>

	<div data-hmbkp-schedule-id="<?php esc_attr_e( $schedule->get_id() ); ?>" class="hmbkp_schedule">

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

    		$schedule->delete_old_backups();

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