<?php

// Backup Type
$type = strtolower( hmbkp_human_get_type( $schedule->get_type() ) );

// Backup Time
$day = date_i18n( 'l', $schedule->get_next_occurrence() );

// Backup Reoccurrence
switch ( $schedule->get_reoccurrence() ) :

	case 'hourly' :

		$reoccurrence = date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence() ) == '00' ? sprintf( __( 'hourly %son the hour', 'hmbkp' ), '<span>' ) : sprintf( __( 'hourly at %s minutes past the hour', 'hmbkp' ), '<span>' . str_replace( '0', '', date_i18n( 'i', $schedule->get_next_occurrence() ) ) ) . '</span>';

	break;

	case 'daily' :

		$reoccurrence = sprintf( __( 'daily on %s at %s', 'hmbkp' ), '<span>' . $day . '</span>', '<span>' . date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence() ) . '</span>' );

	break;


	case 'twicedaily' :

		$times[] = date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence() );
		$times[] = date_i18n( get_option( 'time_format' ), strtotime( '+ 12 hours', $schedule->get_next_occurrence() ) );

		sort( $times );

		$reoccurrence = sprintf( __( 'every 12 hours at %s &amp; %s', 'hmbkp' ), '<span>' . reset( $times ) . '</span>', '<span>' . end( $times ) ) . '</span>';

	break;

	case 'weekly' :

		$reoccurrence = sprintf( __( 'weekly on %s at %s', 'hmbkp' ), '<span>' . $day . '</span>', '<span>' . date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence() ) . '</span>' );

	break;

	case 'fortnightly' :

		$reoccurrence = sprintf( __( 'fortnightly on %s at %s', 'hmbkp' ), '<span>' . $day . '</span>', '<span>' . date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence() ) . '</span>' );

	break;


	case 'monthly' :

		$reoccurrence = sprintf( __( 'on the %s of each month at %s', 'hmbkp' ), '<span>' . date_i18n( 'jS', $schedule->get_next_occurrence() ) . '</span>', '<span>' . date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence() ) . '</span>' );

	break;

endswitch;

// Backup to keep
switch( $schedule->get_max_backups() ) :

	case 1 :

		$backup_to_keep = __( 'store the only the last backup on this server', 'hmbkp' );

	break;

	case 0 :

		$backup_to_keep = 'don\'t store backups on this server';

	break;

	default :

		$backup_to_keep = sprintf( __( 'store the last %s backups on this server', 'hmbkp' ), $schedule->get_max_backups() );

endswitch; ?>

<div class="hmbkp-schedule-sentence">

	<?php printf( __( 'Backup my %s %s %s, %s.', 'hmbkp' ), '<span>' . $type . '</span>', '<code>' . $schedule->get_filesize() . '</code>', $reoccurrence, $backup_to_keep ); ?>

	<div class="hmbkp-schedule-actions row-actions">
		<a class="fancybox" href="<?php echo add_query_arg( array( 'action' => 'hmbkp_edit_schedule_load', 'hmbkp_schedule_id' => $schedule->get_id() ), HMBKP_ADMIN_URL ); ?>">Edit</a> |
		<a class="fancybox" href="<?php echo add_query_arg( array( 'action' => 'hmbkp_run_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), HMBKP_ADMIN_URL ); ?>">Run now</a>  |
		<a class="fancybox" href="<?php echo add_query_arg( array( 'action' => 'hmbkp_delete_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), HMBKP_ADMIN_URL ); ?>">Pause</a> |
		<a class="delete-action" href="<?php echo add_query_arg( array( 'action' => 'hmbkp_delete_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), HMBKP_ADMIN_URL ); ?>">Delete</a>
	</div>
</div>