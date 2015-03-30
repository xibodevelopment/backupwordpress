jQuery(document).ready(function ( $ ) {

    // Don't ever cache ajax requests
    $.ajaxSetup( {'cache': false} );

    if ( $( 'select#hmbkp_schedule_recurrence_type' ).size() ) {

        hmbkpToggleScheduleFields( $( 'select#hmbkp_schedule_recurrence_type' ).val() );

        $( document ).on( 'change', 'select#hmbkp_schedule_recurrence_type', function () {
            hmbkpToggleScheduleFields( $( this ).val() );
        } );

    }

    // Show delete confirm message for delete schedule
    $( document ).on( 'click', '.hmbkp-schedule-actions .delete-action', function ( e ) {

        if ( ! confirm( hmbkp.delete_schedule ) ) {
            e.preventDefault();
        }

    } );

    // Show delete confirm message for delete backup
    $( document ).on( 'click', '.hmbkp_manage_backups_row .delete-action', function ( e ) {

        if ( ! confirm(hmbkp.delete_backup ) ) {
            e.preventDefault();
        }

    } );

    // Show delete confirm message for remove exclude rule
    $( document ).on( 'click', '.hmbkp-edit-schedule-excludes-form .delete-action', function ( e ) {

        if ( ! confirm(hmbkp.remove_exclude_rule ) ) {
            e.preventDefault();
        }

    } );

    // Test the cron response using ajax
    $.post( ajaxurl, {'nonce': hmbkp.nonce, 'action': 'hmbkp_cron_test'},
        function ( data ) {
            if ( data !== '1' ) {
                $( '.wrap > h2' ).after( data );
            }
        }
    );

    // Run a backup
    $( document ).on( 'click', '.hmbkp-run', function ( e ) {

        $( this ).closest( '.hmbkp-schedule-sentence' ).addClass('hmbkp-running' );

        $( '.hmbkp-error' ).removeClass( 'hmbkp-error' );

        var scheduleId = $( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' );

        $.post(
            ajaxurl,
            {
                'hmbkp_run_schedule_nonce': hmbkp.hmbkp_run_schedule_nonce,
                'action': 'hmbkp_run_schedule',
                'hmbkp_schedule_id': scheduleId
            }
        );

        e.preventDefault();

    } );

    // Send the schedule id with the heartbeat
    $( document ).on( 'heartbeat-send', function ( e, data ) {

        data.hmbkp_schedule_id = $( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' );

        if ( $( '.hmbkp-schedule-sentence.hmbkp-running' ).size() ) {

            data.hmbkp_is_in_progress = true;
        } else {
            data.hmbkp_client_request = 'site_size';
        }

    } );

    // Update schedule status on heartbeat tick
    $( document ).on( 'heartbeat-tick', function ( e, data ) {

        // If the schedule has finished then reload the page
        if ( data.hmbkp_schedule_status === 0 && ! $( '.hmbkp-error' ).size() ) {
            location.reload(true);
        }

        // If the schedule is still running then update the schedule status
        if ( ( data.hmbkp_schedule_status !== 0 ) && ( data.hmbkp_schedule_status !== undefined ) ) {
            $( '.hmbkp-status' ).replaceWith( data.hmbkp_schedule_status );
        }

        if ( ( data.hmbkp_site_size !== undefined ) && ( $( 'code.calculating' ).size() ) ) {
            $( 'code.calculating' ).text( data.hmbkp_site_size );

            var excludes = $( '.hmbkp-exclude-settings' );
            if ( excludes.size() ) {
                excludes.replaceWith( data.hmbkp_dir_sizes );
            }
        }

    } );

    // Closing ThickBox Modal Window 
    $( document ).on( 'click', '.hmbkp-thickbox-close', function ( e ) {

        e.preventDefault();
        window.parent.tb_remove();

    } );

} );

function hmbkpToggleScheduleFields( recurrence ) {

    recurrence = ( typeof recurrence !== 'undefined' ) ? recurrence : 'manually';

    var settingFields = jQuery( '.recurring-setting' );
    var scheduleSettingFields = jQuery( '#schedule-start' );
    var twiceDailyNote = jQuery( 'p.twice-js' );

    switch ( recurrence ) {

        case 'manually':
            settingFields.hide();
            break;

        case 'hmbkp_hourly' :
            settingFields.hide();
            break;

        case 'hmbkp_daily' :
            settingFields.hide();
            scheduleSettingFields.show();
            twiceDailyNote.hide();
            break;

        case 'hmbkp_twicedaily' :
            settingFields.hide();
            scheduleSettingFields.show();
            twiceDailyNote.show();
            break;

        case 'hmbkp_weekly' : // fall through
        case 'hmbkp_fortnightly' :
            settingFields.hide();
            jQuery('#start-day').show();
            scheduleSettingFields.show();
            twiceDailyNote.hide();
            break;

        case 'hmbkp_monthly' :
            settingFields.hide();
            scheduleSettingFields.show();
            jQuery('#start-date').show();
            twiceDailyNote.hide();
            break;

    }

}

function hmbkpCatchResponseAndOfferToEmail( data ) {

    // Backup Succeeded
    if ( ! data || data === 0 ) {
        location.reload( true );
    }

    // The backup failed, show the error and offer to have it emailed back
    else {

        jQuery( '.hmbkp-schedule-sentence.hmbkp-running' ).removeClass( 'hmbkp-running' ).addClass( 'hmbkp-error' );

        jQuery.post(
            ajaxurl,
            {'nonce': hmbkp.nonce, 'action': 'hmbkp_backup_error', 'hmbkp_error': data},
            function ( data ) {

                if ( ! data || data === 0 ) {
                    return;
                } else {
                    location.reload( true );
                }
            }
        );

    }

    jQuery( document ).one( 'click', '.hmbkp_send_error_via_email', function ( e ) {

        e.preventDefault();

        jQuery( this ).addClass( 'hmbkp-ajax-loading' ).attr( 'disabled', 'disabled' );

        jQuery.post(
            ajaxurl,
            {'nonce': hmbkp.nonce, 'action': 'hmbkp_email_error', 'hmbkp_error': data},
            function () {
                //jQuery.colorbox.close();
            }
        );

    } );

}
