<?php $schedules = HMBKP_Schedules::get_instance(); ?>

<div>

	<ul class="subsubsub">

		<?php
		// possible titles
		$titles = array(
			'complete-hourly'      => esc_html__( 'Complete Hourly', 'hmbkp' ),
			'file-hourly'          => esc_html__( 'File Hourly', 'hmbkp' ),
			'database-hourly'      => esc_html__( 'Database Hourly', 'hmbkp' ),
			'complete-twicedaily'  => esc_html__( 'Complete Twicedaily', 'hmbkp' ),
			'file-twicedaily'      => esc_html__( 'File Twicedaily', 'hmbkp' ),
			'database-twicedaily'  => esc_html__( 'Database Twicedaily', 'hmbkp' ),
			'complete-daily'       => esc_html__( 'Complete Daily', 'hmbkp' ),
			'file-daily'           => esc_html__( 'File Daily', 'hmbkp' ),
			'database-daily'       => esc_html__( 'Database Daily', 'hmbkp' ),
			'complete-weekly'      => esc_html__( 'Complete Weekly', 'hmbkp' ),
			'file-weekly'          => esc_html__( 'File Weekly', 'hmbkp' ),
			'database-weekly'      => esc_html__( 'Database Weekly', 'hmbkp' ),
			'complete-fortnightly' => esc_html__( 'Complete Fortnightly', 'hmbkp' ),
			'file-fortnightly'     => esc_html__( 'File Fortnightly', 'hmbkp' ),
			'database-fortnightly' => esc_html__( 'Database Fortnightly', 'hmbkp' ),
			'complete-monthly'     => esc_html__( 'Complete Monthly', 'hmbkp' ),
			'file-monthly'         => esc_html__( 'File Monthly', 'hmbkp' ),
			'database-monthly'     => esc_html__( 'Database Monthly', 'hmbkp' ),
			'complete-manually'    => esc_html__( 'Complete Manually', 'hmbkp' ),
			'file-manually'        => esc_html__( 'File Manually', 'hmbkp' ),
			'database-manually'    => esc_html__( 'Database Manually', 'hmbkp' )
		);


		?>
	<?php foreach ( $schedules->get_schedules() as $schedule ) : ?>
		<li<?php if ( $schedule->get_status() ) { ?> class="hmbkp-running" title="<?php echo esc_attr( strip_tags( $schedule->get_status() ) ); ?>"<?php } ?>><a<?php if ( ! empty ( $_GET['hmbkp_schedule_id'] ) && $schedule->get_id() == $_GET['hmbkp_schedule_id'] ) { ?> class="current"<?php } ?> href="<?php echo esc_url( add_query_arg( 'hmbkp_schedule_id', $schedule->get_id(), HMBKP_ADMIN_URL ) ); ?> "><?php printf( $titles[$schedule->get_slug()] ); ?> <span class="count">(<?php echo esc_html( count( $schedule->get_backups() ) ); ?>)</span></a></li>

	<?php endforeach; ?>

		<li><a class="colorbox" href="<?php esc_attr_e( esc_url( add_query_arg( array( 'action' => 'hmbkp_add_schedule_load' ), is_multisite() ? network_admin_url( 'admin-ajax.php' ) : admin_url( 'admin-ajax.php' ) ) ) ); ?>"> + <?php _e( 'add schedule', 'hmbkp' ); ?></a></li>

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

	<div data-hmbkp-schedule-id="<?php echo esc_attr( $schedule->get_id() ); ?>" class="hmbkp_schedule">

		<?php require( HMBKP_PLUGIN_PATH . '/admin/schedule.php' ); ?>

		<table class="widefat">

		    <thead>

				<tr>

					<th scope="col"><?php hmbkp_backups_number( $schedule ); ?></th>
		    		<th scope="col"><?php _e( 'Size', 'hmbkp' ); ?></th>
		    		<th scope="col"><?php _e( 'Type', 'hmbkp' ); ?></th>
		    		<th scope="col"><?php _e( 'Actions', 'hmbkp' ); ?></th>

				</tr>

		    </thead>

		    <tbody>

    	<?php

			if ( $schedule->get_backups() ) :

				$schedule->delete_old_backups();

					foreach ( $schedule->get_backups() as $file ) :

						if ( ! file_exists( $file ) )
							continue;

							hmbkp_get_backup_row( $file, $schedule );

					endforeach;

			else : ?>

    	<tr>

    		<td class="hmbkp-no-backups" colspan="4"><?php _e( 'This is where your backups will appear once you have some.', 'hmbkp' ); ?></td>

    	</tr>

    	<?php endif; ?>

		    </tbody>

		</table>

	</div>

</div>

<?php
function hmbkp_backups_number( $schedule, $zero = false, $one = false, $more = false ) {

	$number = count( $schedule->get_backups() );

	if ( $number > 1 )
		$output = str_replace( '%', number_format_i18n( $number ), ( false === $more ) ? __( '% Backups Completed', 'hmbkp' ) : $more );
	elseif ( $number == 0 )
		$output = ( false === $zero ) ? __( 'No Backups Completed', 'hmbkp' ) : $zero;
	else // must be one
		$output = ( false === $one ) ? __( '1 Backup Completed', 'hmbkp' ) : $one;

	echo apply_filters( 'hmbkp_backups_number', $output, $number );
}
