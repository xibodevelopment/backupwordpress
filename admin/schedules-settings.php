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

		register_setting(
			$schedule->get_id() . '_section',
			'hmbkp_schedule_' . $schedule->get_id(),
			'hmbkp_sanitize_schedule_options'
		);

		add_settings_section(
			$schedule->get_id() . '_section',
			sprintf( __( '%s settings', 'hmbkp' ), $schedule->get_name() ),
			'hmbkp_plugin_schedules_description_display',
			'hmbkp_schedule_' . $schedule->get_id() . '_options'
		);

		add_settings_field(
			'hmbkp_schedule_type_' . $schedule->get_id(),
			__( 'Schedule type', 'hmbkp' ),
			'hmbkp_schedule_type_display',
			'hmbkp_schedule_' . $schedule->get_id() . '_options',
			$schedule->get_id() . '_section'
		);

		add_settings_field(
			'hmbkp_schedule_recurrence_' . $schedule->get_id(),
			__( 'Recurrence', 'hmbkp' ),
			'hmbkp_schedule_recurrence_display',
			'hmbkp_schedule_' . $schedule->get_id() . '_options',
			$schedule->get_id() . '_section'
		);

		add_settings_field(
			'hmbkp_schedule_max_backups_' . $schedule->get_id(),
			__( 'Max Backups', 'hmbkp' ),
			'hmbkp_schedule_max_backups_display',
			'hmbkp_schedule_' . $schedule->get_id() . '_options',
			$schedule->get_id() . '_section'
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

		<?php hmbkp_display_backups_table( $current_schedule ); ?>
		
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

	<select name="<?php echo "hmbkp_schedule_$current_schedule" ?>[type]" id="hmbkp_schedule_type">
		<option<?php selected( $schedule->get_type(), 'complete' ); ?> value="complete"><?php _e( 'Both Database &amp; files', 'hmbkp' ); ?></option>
		<option<?php selected( $schedule->get_type(), 'file' ); ?> value="file"><?php _e( 'Files only', 'hmbkp' ); ?></option>
		<option<?php selected( $schedule->get_type(), 'database' ); ?> value="database"><?php _e( 'Database only', 'hmbkp' ); ?></option>
	</select>

<?php
}

function hmbkp_schedule_recurrence_display() {

	$current_schedule = hmbkp_get_current_schedule_id();
	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $current_schedule ) );
	?>
	<select name="<?php echo "hmbkp_schedule_$current_schedule" ?>[reoccurrence]" id="hmbkp_schedule_reoccurrence">

		<option value="manually"><?php _e( 'Manual Only', 'hmbkp' ); ?></option>

		<?php foreach ( $schedule->get_cron_schedules() as $cron_schedule => $cron_details ) : ?>

			<option <?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo esc_attr( $cron_schedule ); ?>">

				<?php esc_html_e( $cron_details['display'], 'hmbkp' ); ?>

			</option>

		<?php endforeach; ?>

	</select>
<?php
}

function hmbkp_schedule_max_backups_display() {

	$current_schedule = hmbkp_get_current_schedule_id();
	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $current_schedule ) );
?>
	<input class="small-text" type="number" name="<?php echo "hmbkp_schedule_$current_schedule" ?>[max_backups]" min="1" step="1" value="<?php echo esc_attr( $schedule->get_max_backups() ); ?>" />

	<p class="description"><?php printf( __( 'Past this limit older backups will be deleted automatically. This schedule will store a maximum of %s of backups', 'hmbkp' ), '<code>' . size_format( $schedule->get_filesize() * $schedule->get_max_backups() ) . '</code>' ); ?></p>
<?php
}

function hmbkp_sanitize_schedule_options( $valid ) {
	return $valid;
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

function hmbkp_display_backups_table( $schedule_id ) {

	$schedule = new HMBKP_Scheduled_Backup( sanitize_text_field( $schedule_id ) );
	?>

	<h3 class="title"><?php _e( 'Backups', 'hmbkp' ); ?></h3>

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

<?php
}

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
