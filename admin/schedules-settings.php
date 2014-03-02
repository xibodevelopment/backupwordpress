<?php

defined( 'WPINC' ) or die;

function hmbkp_add_options_page() {

	if ( is_multisite() )
		add_submenu_page(
			'settings.php',
			__( 'Manage Backups','hmbkp' ),
			__( 'Backups','hmbkp' ),
			( defined( 'HMBKP_CAPABILITY' ) && HMBKP_CAPABILITY ) ? HMBKP_CAPABILITY : 'manage_options',
			HMBKP_PLUGIN_SLUG,
			'hmbkp_schedule_options_display'
		);
	else
		add_management_page(
			__( 'Manage Backups','hmbkp' ),
			__( 'Backups','hmbkp' ),
			( defined( 'HMBKP_CAPABILITY' ) && HMBKP_CAPABILITY ) ? HMBKP_CAPABILITY : 'manage_options',
			HMBKP_PLUGIN_SLUG,
			'hmbkp_schedule_options_display'
		);

	/*
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
	}*/

}
add_action( 'admin_menu', 'hmbkp_add_options_page' );

/**
 * Sets up the plugin setting fields and sections
 */
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

/**
 * Renders the settings page and tabs
 */
function hmbkp_schedule_options_display() { ?>

	<div class="wrap">

		<h2>
			<?php _e( 'Manage Backups', 'hmbkp' ); ?>

			<?php if ( get_option( 'hmbkp_enable_support' ) ) { ?>

				<a id="intercom" class="add-new-h2" href="mailto:support@hmn.md"><?php _e( 'Support', 'hmbkp' ); ?></a>

			<?php } else { ?>

				<a id="intercom-info" class="colorbox add-new-h2" href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'load_enable_support' ), is_multisite() ? admin_url( 'admin-ajax.php' ) : network_admin_url( 'admin-ajax.php' ) ), 'hmbkp_nonce' ); ?>">Enable Support</a>

			<?php } ?>
		</h2>

		<?php settings_errors(); ?>

		<h2 class="nav-tab-wrapper">

		<?php

		$active_tab = hmbkp_get_current_schedule_id();

		$schedules = HMBKP_Schedules::get_instance();

		$current_schedule = $schedules->get_schedule( $active_tab );

		foreach ( $schedules->get_schedules() as $schedule ) {

			$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab' => $schedule->get_id()
				) );

			// Tab is active if value of $_GET['tab'] is equal to $schedule->get_id()
			$active = $active_tab === $schedule->get_id() ? ' nav-tab-active' : '';

			echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $schedule->get_name() ) . '" class="nav-tab' . $active . '">';
			echo esc_html( $schedule->get_name() );
			echo '</a>';
      }
		?>

			<a href="" class="nav-tab"><?php esc_html_e( 'Add schedule', 'hmbkp' ); ?></a>
		</h2>

		<div id="tab_container">
		<?php require_once( HMBKP_PLUGIN_PATH . '/admin/schedule.php' ); ?>

		<form method="post" action="options.php">
			<?php
			settings_fields( $current_schedule->get_id() . '_section' );
			do_settings_sections( 'hmbkp_schedule_' . $current_schedule->get_id() . '_options' );
			submit_button();
			?>
		</form>

		<?php hmbkp_display_backups_table( $current_schedule ); ?>
		</div>
	</div>

<?php
}

/**
 * Renders a title for each setting tab
 */
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

	$schedules = HMBKP_Schedules::get_instance();

	$active_tab = 'default-1';

	if ( ! empty( $_GET['tab'] ) ) {

		$tab = sanitize_text_field( $_GET['tab'] );

		$current_schedule = $schedules->get_schedule( $tab );

		if ( $current_schedule )
			$active_tab = $tab;

	} else {

		foreach ( $schedules->get_schedules() as $schedule )
			$ids[] = $schedule->get_id();

		$active_tab = reset( $ids );

	}

	return $active_tab;
}

function hmbkp_display_backups_table( $schedule ) { ?>

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
