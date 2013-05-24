<?php

// Calculated filesize
$filesize = $schedule->is_filesize_cached() || isset( $recalculate_filesize ) ? '<code title="' . __( 'Backups will be compressed and should be smaller than this.', 'hmbkp' ) . '">' . esc_attr( $schedule->get_formatted_file_size() ) . '</code>' : '<code class="calculating" title="' . __( 'this shouldn\'t take long&hellip;', 'hmbkp' ) . '">' . __( 'calculating the size of your site&hellip;', 'hmbkp' ) . '</code>';

// Backup Type
$type = strtolower( hmbkp_human_get_type( $schedule->get_type() ) );

// Backup Time
$day = date_i18n( 'l', $schedule->get_next_occurrence( false ) );

$next_backup = 'title="' . esc_attr( sprintf( __( 'The next backup will be on %1$s at %2$s', 'hmbkp' ), date_i18n( get_option( 'date_format' ), $schedule->get_next_occurrence( false ) ), date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) ) . '"';

// Backup Re-occurrence
switch ( $schedule->get_reoccurrence() ) :

	case 'hmbkp_hourly' :

		$reoccurrence = date_i18n( 'i', $schedule->get_next_occurrence( false ) ) === '00' ? '<span ' . $next_backup . '>' . __( 'hourly on the hour', 'hmbkp' ) . '</span>' : sprintf( __( 'hourly at %s minutes past the hour', 'hmbkp' ), '<span ' . $next_backup . '>' . str_replace( '0', '', date_i18n( 'i', $schedule->get_next_occurrence( false ) ) ) ) . '</span>';

	break;

	case 'hmbkp_daily' :

		$reoccurrence = sprintf( __( 'daily at %s', 'hmbkp' ), '<span ' . $next_backup . '>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;


	case 'hmbkp_twicedaily' :

		$times[] = date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) );
		$times[] = date_i18n( get_option( 'time_format' ), strtotime( '+ 12 hours', $schedule->get_next_occurrence( false ) ) );

		sort( $times );

		$reoccurrence = sprintf( __( 'every 12 hours at %1$s &amp; %2$s', 'hmbkp' ), '<span ' . $next_backup . '>' . esc_html( reset( $times ) ) . '</span>', '<span>' . esc_html( end( $times ) ) ) . '</span>';

	break;

	case 'hmbkp_weekly' :

		$reoccurrence = sprintf( __( 'weekly on %1$s at %2$s', 'hmbkp' ), '<span ' . $next_backup . '>' .esc_html( $day ) . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'hmbkp_fortnightly' :

		$reoccurrence = sprintf( __( 'fortnightly on %1$s at %2$s', 'hmbkp' ), '<span ' . $next_backup . '>' . $day . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;


	case 'hmbkp_monthly' :

		$reoccurrence = sprintf( __( 'on the %1$s of each month at %2$s', 'hmbkp' ), '<span ' . $next_backup . '>' . esc_html( date_i18n( 'jS', $schedule->get_next_occurrence( false ) ) ) . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'manually' :

		$reoccurrence = __( 'manually', 'hmbkp' );

	break;

	default:
		wp_die( 'Invalid reoccurence' );

endswitch;

$server = '<span title="' . esc_attr( hmbkp_path() ) . '">' . __( 'this server', 'hmbkp' ) . '</span>';

// Backup to keep
switch ( $schedule->get_max_backups() ) :

	case 1 :

		$backup_to_keep = sprintf( __( 'store only the last backup on %s', 'hmbkp' ), $server );

	break;

	case 0 :

		$backup_to_keep = sprintf( __( 'don\'t store any backups on %s', 'hmbkp' ), $server );

	break;

	default :

		$backup_to_keep = sprintf( __( 'store only the last %1$s backups on %2$s', 'hmbkp' ), esc_html( $schedule->get_max_backups() ), $server );

endswitch;

foreach ( HMBKP_Services::get_services( $schedule ) as $file => $service )
	$services[] = $service->display(); ?>

<div class="hmbkp-schedule-sentence<?php if ( $schedule->get_status() ) { ?> hmbkp-running<?php } ?>">

	<?php printf( __( 'Backup my %1$s %2$s %3$s, %4$s. %5$s', 'hmbkp' ), $filesize, '<span>' . $type . '</span>', $reoccurrence, $backup_to_keep, implode( '. ', array_filter( $services ) ) ); ?>

	<?php hmbkp_schedule_actions( $schedule ); ?>

</div>