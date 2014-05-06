<?php $schedules = HMBKP_Schedules::get_instance()->get_schedules();

if ( ! empty( $_GET['hmbkp_schedule_id'] ) ) {
	$current_schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );
} else {
	$current_schedule = reset( $schedules );
} ?>

<h2 class="nav-tab-wrapper">

	<?php foreach ( $schedules as $schedule ) : ?>
		<a class="nav-tab<?php if ( $schedule->get_status() ) { ?> hmbkp-running<?php } ?><?php if ( ! empty ( $current_schedule ) && $schedule->get_id() === $current_schedule->get_id() ) { ?> nav-tab-active<?php } ?>" <?php if ( $schedule->get_status() ) { ?>title="<?php echo esc_attr( strip_tags( $schedule->get_status() ) ); ?>"<?php } ?> href="<?php echo esc_url( add_query_arg( 'hmbkp_schedule_id', $schedule->get_id(), HMBKP_ADMIN_URL ) ); ?> "><?php echo esc_html( hmbkp_translated_schedule_title( $schedule->get_slug(), $schedule->get_name() ) ); ?> <span class="count">(<?php echo esc_html( count( $schedule->get_backups() ) ); ?>)</span></a>
	<?php endforeach; ?>

	<a class="nav-tab" href="<?php esc_attr_e( esc_url( add_query_arg( array( 'action' => 'hmbkp_add_schedule_load' ), is_multisite() ? network_admin_url( 'admin-ajax.php' ) : admin_url( 'admin-ajax.php' ) ) ) ); ?>"> + <?php _e( 'add schedule', 'hmbkp' ); ?></a>

	<!--
<?php if ( get_option( 'hmbkp_enable_support' ) ) { ?>
		<a id="intercom" class="add-new-h2" href="mailto:support@hmn.md"><?php _e( 'Support', 'hmbkp' ); ?></a>
	<?php } else { ?>
		<a id="intercom-info" class="colorbox add-new-h2" href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'load_enable_support' ), is_multisite() ? admin_url( 'admin-ajax.php' ) : network_admin_url( 'admin-ajax.php' ) ), 'hmbkp_nonce' ); ?>"><?php _e( 'Enable Support', 'hmbkp' ); ?></a>
	<?php } ?>
-->

</h2>

<?php // Don't continue if we don't have a schedule
if ( ! $schedule = $current_schedule ) {
	return;
} ?>

<div data-hmbkp-schedule-id="<?php echo esc_attr( $schedule->get_id() ); ?>" class="hmbkp_schedule">

	<?php require( HMBKP_PLUGIN_PATH . 'admin/schedule-sentence.php' ); ?>

	<?php require( HMBKP_PLUGIN_PATH . 'admin/backups-table.php'); ?>

</div>