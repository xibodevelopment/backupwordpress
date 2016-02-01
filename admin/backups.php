<?php

namespace HM\BackUpWordPress;

// Refresh the schedules from the database to make sure we have the latest changes
Schedules::get_instance()->refresh_schedules();

$schedules = Schedules::get_instance()->get_schedules();

if ( ! empty( $_GET['hmbkp_schedule_id'] ) ) {
	$current_schedule = new Scheduled_Backup( sanitize_text_field( $_GET['hmbkp_schedule_id'] ) );
} else {
	$current_schedule = reset( $schedules );
} ?>

<h2 class="nav-tab-wrapper">

	<?php foreach ( $schedules as $schedule ) :
		$status = new Backup_Status( $schedule->get_id() ); ?>

		<a class="nav-tab<?php if ( $status->get_status() ) { ?> hmbkp-running<?php } ?><?php if ( $schedule->get_id() === $current_schedule->get_id() ) { ?> nav-tab-active<?php } ?>" <?php if ( $status->get_status() ) { ?>title="<?php echo esc_attr( strip_tags( $status->get_status() ) ); ?>"<?php } ?> href="<?php echo esc_url( add_query_arg( 'hmbkp_schedule_id', $schedule->get_id(), HMBKP_ADMIN_URL ) ); ?> "><?php echo esc_html( translated_schedule_title( $schedule->get_slug(), $schedule->get_name() ) ); ?> <span class="count">(<?php echo esc_html( count( $schedule->get_backups() ) ); ?>)</span></a>

	<?php endforeach; ?>

	<a class="nav-tab<?php if ( ! Schedules::get_instance()->get_schedule( $current_schedule->get_id() ) ) { ?> nav-tab-active<?php } ?>" href="<?php echo esc_url( add_query_arg( array( 'hmbkp_add_schedule' => '1', 'action' => 'hmbkp_edit_schedule', 'hmbkp_schedule_id' => time(), 'hmbkp_panel' => 'hmbkp_edit_schedule_settings' ), HMBKP_ADMIN_URL ) ); ?>"> + <?php _e( 'add schedule', 'backupwordpress' ); ?></a>

</h2>

<?php // Don't continue if we don't have a schedule
if ( ! $schedule = $current_schedule ) {
	return;
} ?>

<div data-hmbkp-schedule-id="<?php echo esc_attr( $schedule->get_id() ); ?>" class="hmbkp_schedule">

	<?php require( HMBKP_PLUGIN_PATH . 'admin/schedule-sentence.php' ); ?>

	<?php require( HMBKP_PLUGIN_PATH . 'admin/backups-table.php' ); ?>

</div>
