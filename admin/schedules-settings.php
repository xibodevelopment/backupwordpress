<?php

function hmbkp_add_options_page() {

	//Creates a top level menu page called 'Backups'
	add_menu_page(
		__( 'BackUpWordPress Settings', 'hmbkp' ),
		__( 'Backups', 'hmbkp' ),
		'manage_options',
		'backupwordpress',
		'hmbkp_schedule_options_display'
	);

	$schedules = HMBKP_Schedules::get_instance();

	foreach ( $schedules->get_schedules() as $schedule ) {
		add_submenu_page(
			'backupwordpress',
			$schedule->get_name(),
			$schedule->get_name(),
			'manage_options',
			'hmbkp_schedule_' . $schedule->get_id() . '_options',
			'hmbkp_schedule_options_display'
		);
	}

}
add_action( 'admin_menu', 'hmbkp_add_options_page' );

function hmbkp_initialize_plugin_options() {

	$schedules = HMBKP_Schedules::get_instance();

	foreach ( $schedules->get_schedules() as $schedule ) {
		add_settings_section(
			$schedule->get_id() . '_section',
			sprintf( __( '%s settings', 'hmbkp' ), $schedule->get_name() ),
			'hmbkp_plugin_schedules_description_display',
			'hmbkp_schedule_' . $schedule->get_id() . '_options'
		);

		add_settings_field(
			'hmbkp_schedule_type_' . $schedule->get_id(),
			'Schedule type',
			'hmbkp_schedule_type_display',
			'hmbkp_schedule_' . $schedule->get_id() . '_options',
			$schedule->get_id() . '_section'
		);

		register_setting(
			$schedule->get_id() . '_section',
			'hmbkp_schedule_' . $schedule->get_id(),
			'hmbkp_sanitize_schedule_options'
		);
	}

}
add_action( 'admin_init', 'hmbkp_initialize_plugin_options' );

function hmbkp_schedule_options_display() { ?>

	<div class="wrap">

		<h2><?php esc_html_e( 'Schedules', 'hmbkp' ); ?></h2>

		<?php settings_errors();

		$schedules = HMBKP_Schedules::get_instance(); ?>

		<h2 class="nav-tab-wrapper">

		<?php
		$current_schedule = hmbkp_get_current_schedule_id();

		foreach ( $schedules->get_schedules() as $schedule ) {
		?>
			<a href="?page=hmbkp_schedule_<?php echo $schedule->get_id(); ?>_options" class="nav-tab <?php echo ( $current_schedule === $schedule->get_id() ) ? 'nav-tab-active' : ''; ?>"><?php printf( esc_html__( '%s', 'hmbkp' ), $schedule->get_name() ); ?></a>
		<?php }
		?>

		</h2>

		<form method="post" action="options.php">
			<?php
			settings_fields( $current_schedule . '_section' );
			do_settings_sections( 'hmbkp_schedule_' . $current_schedule . '_options' );
			submit_button();
			?>
		</form>

	</div>

<?php
}

function hmbkp_plugin_schedules_description_display() {

	$current_schedule = hmbkp_get_current_schedule_id();
	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $current_schedule ) );
	?>
	<p><?php esc_html_e( 'These are the settings for ' . $schedule->get_name() . '.', 'hmbkp' ); ?></p>
<?php }

function hmbkp_schedule_type_display() {

	$current_schedule = hmbkp_get_current_schedule_id();
	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $current_schedule ) );
	?>

	<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">
		<option<?php selected( $schedule->get_type(), 'complete' ); ?> value="complete"><?php _e( 'Both Database &amp; files', 'hmbkp' ); ?></option>
		<option<?php selected( $schedule->get_type(), 'file' ); ?> value="file"><?php _e( 'Files only', 'hmbkp' ); ?></option>
		<option<?php selected( $schedule->get_type(), 'database' ); ?> value="database"><?php _e( 'Database only', 'hmbkp' ); ?></option>
	</select>

<?php
}

/**
 * Gets the ID of the schedule for the current tab
 *
 * @return string
 */
function hmbkp_get_current_schedule_id() {

	if ( isset( $_GET['page'] ) ) {
		$parts = explode( '_', $_GET['page'] );
		return $parts[2];
	}

	return 'default-1';
}