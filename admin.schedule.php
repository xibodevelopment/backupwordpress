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

// Backup Reoccurrence
switch ( $schedule->get_reoccurrence() ) :

	case 'hourly' :

		$reoccurrence = __( 'hour', 'hmbkp' );

	break;

	case 'daily' :

		$reoccurrence = __( 'day', 'hmbkp' );

	break;


	case 'twicedaily' :

		$reoccurrence = __( '12 hours', 'hmbkp' );

	break;

	case 'weekly' :

		$reoccurrence = __( 'week', 'hmbkp' );

	break;

	case 'fortnightly' :

		$reoccurrence = __( '2 weeks', 'hmbkp' );

	break;


	case 'monthly' :

		$reoccurrence = __( 'month', 'hmbkp' );

	break;

endswitch;

// Backup Time
$day = date_i18n( 'l', $schedule->get_next_occurrence() );
$time = date_i18n( 'H:i', $schedule->get_next_occurrence() ); ?>

<p>

	<?php printf( __( 'Backup my %s every %s on %s at %s', 'hmbkp' ), '<code>' . $type . '</code>', '<code>' . $reoccurrence . '</code>', '<code>' . $day . '</code>', '<code>' . $time . '</code>' ); ?>

	<button type="button" class="fancybox button-secondary" href="<?php echo add_query_arg( array( 'action' => 'hmbkp_edit_schedule_load', 'hmbkp_schedule' => $schedule->get_slug() ), HMBKP_ADMIN_URL ); ?>">Edit</button>

	<button type="button" class="fancybox button-secondary" href="<?php echo add_query_arg( array( 'action' => 'hmbkp_run_schedule' ), HMBKP_ADMIN_URL ); ?>">Run</button>

</p>