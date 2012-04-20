<?php

// Backup type
switch ( $schedule->get_type() ) :

	case 'complete' :

		$type = __( 'database and files', 'hmbkp' );

	break;

	case 'file' :

		$type = __( 'files', 'hmbkp' );

	break;

	case 'database' :

		$type = __( 'database', 'hmbkp' );

	break;

endswitch;

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

endswitch; ?>

<p>

	<?php printf( __( 'Backup my %s %s', 'hmbkp' ), '<span>' . $type . '</span>', $reoccurrence ); ?>

	<button type="button" class="fancybox button-secondary" href="<?php echo add_query_arg( array( 'action' => 'hmbkp_edit_schedule_load', 'hmbkp_schedule_slug' => $schedule->get_slug() ), HMBKP_ADMIN_URL ); ?>">Edit</button>

	<button type="button" class="fancybox button-secondary" href="<?php echo add_query_arg( array( 'action' => 'hmbkp_run_schedule' ), HMBKP_ADMIN_URL ); ?>">Run</button>

</p>