<?php $schedules = HMBKP_Schedules::get_instance()->get_schedules(); ?>

	<ul class="subsubsub">

		<?php foreach ( $schedules as $schedule ) : ?>
			<li<?php if ( $schedule->get_status() ) { ?> class="hmbkp-running" title="<?php echo esc_attr( strip_tags( $schedule->get_status() ) ); ?>"<?php } ?>><a<?php if ( ! empty ( $_GET['hmbkp_schedule_id'] ) && $schedule->get_id() === $_GET['hmbkp_schedule_id'] ) { ?> class="current"<?php } ?> href="<?php echo esc_url( add_query_arg( 'hmbkp_schedule_id', $schedule->get_id(), HMBKP_ADMIN_URL ) ); ?> "><?php echo esc_html( hmbkp_translated_schedule_title( $schedule->get_slug(), $schedule->get_name() ) ); ?> <span class="count">(<?php echo esc_html( count( $schedule->get_backups() ) ); ?>)</span></a></li>
		<?php endforeach; ?>

		<li><a class="colorbox" href="<?php esc_attr_e( esc_url( add_query_arg( array( 'action' => 'hmbkp_add_schedule_load' ), is_multisite() ? network_admin_url( 'admin-ajax.php' ) : admin_url( 'admin-ajax.php' ) ) ) ); ?>"> + <?php _e( 'add schedule', 'hmbkp' ); ?></a></li>

	</ul>

<?php if ( ! empty( $_GET['hmbkp_schedule_id'] ) )
	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );
else {
	$schedule = reset( $schedules );
}

// Don't continue if we don't have a schedule
if ( ! $schedule )
	return; ?>

<div data-hmbkp-schedule-id="<?php echo esc_attr( $schedule->get_id() ); ?>" class="hmbkp_schedule">

	<?php require( HMBKP_PLUGIN_PATH . 'admin/schedule-sentence.php' ); ?>

	<?php require( HMBKP_PLUGIN_PATH . 'admin/backups-table.php'); ?>

</div>