<?php

$filesize = hmbkp_get_site_size_text( $schedule );

// Backup Type
$type = strtolower( hmbkp_human_get_type( $schedule->get_type() ) );

// Backup Time
$day = date_i18n( 'l', $schedule->get_next_occurrence( false ) );

$next_backup = 'title="' . esc_attr( sprintf( __( 'The next backup will be on %1$s at %2$s %3$s', 'backupwordpress' ), date_i18n( get_option( 'date_format' ), $schedule->get_next_occurrence( false ) ), date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ), date_i18n( 'T', $schedule->get_next_occurrence( false ) ) ) ) . '"';

// Backup Re-occurrence
switch ( $schedule->get_reoccurrence() ) :

	case 'hmbkp_hourly' :

		$reoccurrence = date_i18n( 'i', $schedule->get_next_occurrence( false ) ) === '00' ? '<span ' . $next_backup . '>' . __( 'hourly on the hour', 'backupwordpress' ) . '</span>' : sprintf( __( 'hourly at %s minutes past the hour', 'backupwordpress' ), '<span ' . $next_backup . '>' . intval( date_i18n( 'i', $schedule->get_next_occurrence( false ) ) ) ) . '</span>';

	break;

	case 'hmbkp_daily' :

		$reoccurrence = sprintf( __( 'daily at %s', 'backupwordpress' ), '<span ' . $next_backup . '>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'hmbkp_twicedaily' :

		$times[] = date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) );
		$times[] = date_i18n( get_option( 'time_format' ), strtotime( '+ 12 hours', $schedule->get_next_occurrence( false ) ) );

		sort( $times );

		$reoccurrence = sprintf( __( 'every 12 hours at %1$s &amp; %2$s', 'backupwordpress' ), '<span ' . $next_backup . '>' . esc_html( reset( $times ) ) . '</span>', '<span>' . esc_html( end( $times ) ) ) . '</span>';

	break;

	case 'hmbkp_weekly' :

		$reoccurrence = sprintf( __( 'weekly on %1$s at %2$s', 'backupwordpress' ), '<span ' . $next_backup . '>' .esc_html( $day ) . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'hmbkp_fortnightly' :

		$reoccurrence = sprintf( __( 'biweekly on %1$s at %2$s', 'backupwordpress' ), '<span ' . $next_backup . '>' . $day . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'hmbkp_monthly' :

		$reoccurrence = sprintf( __( 'on the %1$s of each month at %2$s', 'backupwordpress' ), '<span ' . $next_backup . '>' . esc_html( date_i18n( 'jS', $schedule->get_next_occurrence( false ) ) ) . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'manually' :

		$reoccurrence = __( 'manually', 'backupwordpress' );

	break;

	default :

		$schedule->set_reoccurrence( 'manually' );

endswitch;

$server = '<span title="' . esc_attr( hmbkp_path() ) . '">' . __( 'this server', 'backupwordpress' ) . '</span>';
$server = '<code>' . esc_attr( str_replace( $schedule->get_home_path(), '', hmbkp_path() ) ) . '</code>';

// Backup to keep
switch ( $schedule->get_max_backups() ) :

	case 1 :

		$backup_to_keep = sprintf( __( 'store the most recent backup in %s', 'backupwordpress' ), $server );

	break;

	case 0 :

		$backup_to_keep = sprintf( __( 'don\'t store any backups in on this server', 'backupwordpress' ), hmbkp_path() );

	break;

	default :

		$backup_to_keep = sprintf( __( 'store the last %1$s backups in %2$s', 'backupwordpress' ), esc_html( $schedule->get_max_backups() ), $server );

endswitch;

$email_msg = $services = '';

foreach ( HMBKP_Services::get_services( $schedule ) as $file => $service ) {

	if ( 'Email' === $service->name ) {
		$email_msg = wp_kses_post( $service->display() );

	} elseif ( $service->is_service_active() ) {
		$services[] = esc_html( $service->display() );

	}

}

if ( ! empty( $services ) && count( $services ) > 1 ) {

	$services[count( $services ) -2] .= ' & ' . $services[count( $services ) -1];

	array_pop( $services );

} ?>

<div class="hmbkp-schedule-sentence<?php if ( $schedule->get_status() ) { ?> hmbkp-running<?php } ?>">

	<?php $sentence = sprintf( _x( 'Backup my %1$s %2$s %3$s, %4$s.', '1: Backup Type 2: Total size of backup 3: Schedule 4: Number of backups to store', 'backupwordpress' ), '<span>' . $type . '</span>', $filesize, $reoccurrence, $backup_to_keep );

	if ( $email_msg ) {
		$sentence .= sprintf( __( '%s. ', 'backupwordpress' ), $email_msg );
	}

	if ( $services ) {
		$sentence .= sprintf( __( 'Send a copy of each backup to %s.', 'backupwordpress' ), implode( ', ', array_filter( $services ) ) );
	}

	echo $sentence; ?>

	<?php if ( HMBKP_Schedules::get_instance()->get_schedule( $schedule->get_id() ) ) {
		hmbkp_schedule_status( $schedule );
	} ?>

	<?php require( HMBKP_PLUGIN_PATH . 'admin/schedule-settings.php' ); ?>

</div>

<?php

/**
 * Returns a formatted string containing the calculated total site size or a message
 * to indicate it is being calculated.
 *
 * @param HMBKP_Scheduled_Backup $schedule
 *
 * @return string
 */
function hmbkp_get_site_size_text( HMBKP_Scheduled_Backup $schedule ) {

	if ( isset( $_GET['hmbkp_add_schedule'] ) ) {
		return '';
	} elseif (  ( 'database' === $schedule->get_type() ) || $schedule->is_site_size_cached() ) {
		return sprintf( '(<code title="' . __( 'Backups will be compressed and should be smaller than this.', 'backupwordpress' ) . '">%s</code>)', esc_attr( $schedule->get_formatted_site_size() ) );
	} else {
		return sprintf('(<code class="calculating" title="' . __( 'this shouldn\'t take long&hellip;', 'backupwordpress' ) . '">' . __( 'calculating the size of your backup&hellip;', 'backupwordpress' ) . '</code>)');
	}

}