<?php

namespace HM\BackUpWordPress;

$filesize = get_site_size_text( $schedule );

// Backup Type
$type = strtolower( human_get_type( $schedule->get_type() ) );

// Backup Time
$day = date_i18n( 'l', $schedule->get_next_occurrence( false ) );

// Next Backup
$next_backup = 'title="' . esc_attr( sprintf(
	/* translators: 1: Date 2: Time 3: Timezone abbreviation. Eg., EST, MDT */
	__( 'The next backup will be on %1$s at %2$s %3$s', 'backupwordpress' ),
	date_i18n( get_option( 'date_format' ), $schedule->get_next_occurrence( false ) ),
	date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ),
	date_i18n( 'T', $schedule->get_next_occurrence( false ) )
	) ) . '"';

// Backup status
$status = new Backup_Status( $schedule->get_id() );

// Backup Re-occurrence
switch ( $schedule->get_reoccurrence() ) :

	case 'hourly' :

		$reoccurrence = date_i18n( 'i', $schedule->get_next_occurrence( false ) ) === '00'
			? '<span ' . $next_backup . '>' . esc_html__( 'hourly on the hour', 'backupwordpress' ) . '</span>'
			: wp_kses(
				sprintf(
					/* translators: Number of minutes */
					__( 'hourly at %s minutes past the hour', 'backupwordpress' ),
					'<span ' . $next_backup . '>' . esc_html( intval( date_i18n( 'i', $schedule->get_next_occurrence( false ) ) ) ) . '</span>'
				),
				array(
					'span' => array(
						'title' => array(),
					)
				)
			);

	break;

	case 'daily' :

		$reoccurrence = wp_kses(
			sprintf(
				/* translators: Time */
				__( 'daily at %s', 'backupwordpress' ),
				'<span ' . $next_backup . '>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>'
			),
			array(
				'span' => array(
					'title' => array(),
				)
			)
		);

	break;

	case 'twicedaily' :

		$times[] = date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) );
		$times[] = date_i18n( get_option( 'time_format' ), strtotime( '+ 12 hours', $schedule->get_next_occurrence( false ) ) );

		sort( $times );

		$reoccurrence = wp_kses(
			sprintf(
				/* translators: 1: First time the back up runs 2: Second time backup runs */
				__( 'every 12 hours at %1$s &amp; %2$s', 'backupwordpress' ),
				'<span ' . $next_backup . '>' . esc_html( reset( $times ) ) . '</span>',
				'<span>' . esc_html( end( $times ) ) . '</span>'
			),
			array(
				'span' => array(
					'title' => array(),
				)
			)
		);

	break;

	case 'weekly' :

		$reoccurrence = wp_kses(
			sprintf(
				/* translators: 1: Full name of the week day, eg. Monday 2: Time */
				__( 'weekly on %1$s at %2$s', 'backupwordpress' ),
				'<span ' . $next_backup . '>' . esc_html( $day ) . '</span>',
				'<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>'
			),
			array(
				'span' => array(
					'title' => array(),
				)
			)
		);

	break;

	case 'fortnightly' :

		$reoccurrence = wp_kses(
			sprintf(
				/* translators: 1: Full name of the week day, eg. Monday 2: Time */
				__( 'every two weeks on %1$s at %2$s', 'backupwordpress' ),
				'<span ' . $next_backup . '>' . esc_html( $day ) . '</span>',
				'<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>'
			),
			array(
				'span' => array(
					'title' => array(),
				)
			)
		);

	break;

	case 'monthly' :

		$reoccurrence = wp_kses(
			sprintf(
				/* translators: 1: Ordinal number of a day of a month, eg. 1st, 10th 2: Time */
				__( 'on the %1$s of each month at %2$s', 'backupwordpress' ),
				'<span ' . $next_backup . '>' . esc_html( date_i18n( 'jS', $schedule->get_next_occurrence( false ) ) ) . '</span>',
				'<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>'
			),
			array(
				'span' => array(
					'title' => array(),
				)
			)
		);

	break;

	case 'manually' :

		$reoccurrence = esc_html__( 'manually', 'backupwordpress' );

	break;

	default :

		$reoccurrence = esc_html__( 'manually', 'backupwordpress' );
		$schedule->set_reoccurrence( 'manually' );

endswitch;

$server = '<code title="' . __( 'Check the help tab to learn how to change where your backups are stored.', 'backupwordpress' ) . '">' . esc_attr( str_replace( Path::get_home_path(), '', Path::get_path() ) ) . '</code>';

// Backup to keep
switch ( $schedule->get_max_backups() ) :

	case 1 :

		$backup_to_keep = wp_kses(
			sprintf(
				__( 'store the most recent backup in %s', 'backupwordpress' ),
				$server
			),
			array(
				'code' => array(
					'title' => array(),
				)
			)
		);

	break;

	case 0 :

		$backup_to_keep = esc_html__( 'don\'t store any backups in on this server', 'backupwordpress' );

	break;

	default :

		$backup_to_keep = wp_kses(
			sprintf(
				/* translators: 1: The number of backups to store 2: Path on a server */
				__( 'store the last %1$s backups in %2$s', 'backupwordpress' ),
				esc_html( $schedule->get_max_backups() ),
				$server
			),
			array(
				'code' => array(
					'title' => array(),
				)
			)
		);

endswitch;

$email_msg = '';
$services = array();

foreach ( Services::get_services( $schedule ) as $file => $service ) {

	if ( is_wp_error( $service ) ) {
		$email_msg = $service->get_error_message();
	} elseif ( 'Email' === $service->name ) {
		$email_msg = wp_kses_post( $service->display() );
	} elseif ( $service->is_service_active() && $service->display() ) {
		$services[] = esc_html( $service->display() );
	}
}

if ( ! empty( $services ) && count( $services ) > 1 ) {
	$services[ count( $services ) -2 ] .= ' & ' . $services[ count( $services ) -1 ];
	array_pop( $services );
} ?>

<div class="hmbkp-schedule-sentence<?php if ( $status->get_status() ) { ?> hmbkp-running<?php } ?>">

	<?php $sentence = wp_kses(
		sprintf(
			/* translators: 1: Backup Type (eg. complete, files, database, ...) 2: Total size of backup 3: Schedule (eg. hourly, daily, weekly, ...) 4: Number of backups to store */
			__( 'Backup my %1$s %2$s %3$s, %4$s.', 'backupwordpress' ),
			'<span>' . esc_html( $type ) . '</span>',
			$filesize,
			$reoccurrence,
			$backup_to_keep
		),
		array(
			'span' => array(
				'title' => array(),
			),
			'code' => array(
				'title' => array(),
			),
		)
	);

	if ( $email_msg ) {
		$sentence .= ' ' . $email_msg;
	}

	if ( ! empty( $services ) ) {
		$sentence .= ' ' . esc_html( sprintf(
			/* translators: List of available services for storing backups */
			__( 'Send a copy of each backup to %s.', 'backupwordpress' ),
			implode( ', ', $services )
		) );
	}

	echo $sentence; ?>

	<?php if ( Schedules::get_instance()->get_schedule( $schedule->get_id() ) ) :
		schedule_status( $schedule );
	endif; ?>

	<?php require( HMBKP_PLUGIN_PATH . 'admin/schedule-settings.php' ); ?>

</div>

<?php

/**
 * Returns a formatted string containing the calculated total site size or a message
 * to indicate it is being calculated.
 *
 * @param HM\BackUpWordPress\Scheduled_Backup $schedule
 *
 * @return string
 */
function get_site_size_text( Scheduled_Backup $schedule ) {

	if ( isset( $_GET['hmbkp_add_schedule'] ) ) {
		return '';
	}

	$site_size = new Site_Size( $schedule->get_type(), $schedule->get_excludes() );

	if ( ( 'database' === $schedule->get_type() ) || $site_size->is_site_size_cached() ) {

		return wp_kses(
			sprintf(
				'(<code title="%1$s">%2$s</code>)',
				__( 'Backups will be compressed and should be smaller than this.', 'backupwordpress' ),
				esc_html( $site_size->get_formatted_site_size() )
			),
			array(
				'code' => array(
					'title'  => array(),
				)
			)
		);

	} else {

		return wp_kses(
			sprintf(
				'(<code class="calculating" title="%1$s">%2$s</code>)',
				esc_attr__( 'this shouldn\'t take long&hellip;', 'backupwordpress' ),
				__( 'calculating the size of your site&hellip;', 'backupwordpress' )
			),
			array(
				'code' => array(
					'class' => array(),
					'title'  => array(),
				)
			)
		);
	}
}
