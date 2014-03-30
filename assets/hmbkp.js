jQuery( document ).ready( function( $ ) {

	// Don't ever cache ajax requests
	$.ajaxSetup( { 'cache' : false } );

	// Remove the loading class when ajax requests complete
	$( document ).ajaxComplete( function() {
		$( '.hmbkp-ajax-loading' ).removeClass( 'hmbkp-ajax-loading' ).removeAttr( 'disabled' );
	} );

	$( document ).on( 'click', '.hmbkp-colorbox-close', function() {
	    $.colorbox.close(); location.reload();
	} );

	// Setup the tabs
	$( '.hmbkp-tabs' ).tabs();

	// Set the first tab to be active
	if ( ! $( '.subsubsub a.current' ).size() )
		$( '.subsubsub li:first a').addClass( 'current' );

	// Initialize colorbox
	$( '.colorbox' ).colorbox( {
		'initialWidth'	: '320px',
		'initialHeight'	: '100px',
		'transition'	: 'elastic',
		'scrolling'		: false,
		'innerWidth'	: '320px',
		'maxHeight'		: '85%', // 85% Takes into account the WP Admin bar.
		'escKey'		: false,
		'overlayClose'	: false,
		'onLoad'		: function() {
			$( '#cboxClose' ).remove();
		},
		'onComplete'	: function() {

			$( '.hmbkp-tabs' ).tabs();

			if ( $( ".hmbkp-form p.submit:contains('" + hmbkp.update + "')" ).size() ) {
				$( '<button type="button" class="button-secondary hmbkp-colorbox-close">' + hmbkp.cancel + '</button>' ).appendTo( '.hmbkp-form p.submit' );
			}


			$( '.recurring-setting' ).hide();

			hmbkpToggleScheduleFields( $('select#hmbkp_schedule_recurrence_type').val() );

			$( document ).on( 'change', 'select#hmbkp_schedule_recurrence_type', function() {
				hmbkpToggleScheduleFields( $( this ).val() );
			} );

			$.colorbox.resize();

		}

	} );

	// Resize the colorbox when switching tabs
	$( document).on( 'click', '.ui-tabs-anchor', function( e ) {
		$.colorbox.resize();
	} );

	// Show delete confirm message for delete schedule
	$( document ).on( 'click', '.hmbkp-schedule-actions .delete-action', function( e ) {

		if ( ! confirm( hmbkp.delete_schedule ) )
			e.preventDefault();

	} );

	// Show delete confirm message for delete backup
	$( document ).on( 'click', '.hmbkp_manage_backups_row .delete-action', function( e ) {

		if ( ! confirm( hmbkp.delete_backup ) )
			e.preventDefault();

	} );

	// Show delete confirm message for remove exclude rule
	$( document ).on( 'click', '.hmbkp-edit-schedule-excludes-form .delete-action', function( e ) {

		if ( ! confirm( hmbkp.remove_exclude_rule ) )
			e.preventDefault();

	} );

	// Preview exclude rule
	$( document ).on( 'click', '.hmbkp_preview_exclude_rule', function() {

		if ( ! $( '.hmbkp_add_exclude_rule input' ).val() ) {
			$( '.hmbkp_add_exclude_rule ul' ).remove();
			$( '.hmbkp_add_exclude_rule p' ).remove();
			return;
		}

		$( this ).addClass( 'hmbkp-ajax-loading' ).attr( 'disabled', 'disabled' );

		$.post(
			ajaxurl,
			{ 'nonce' : hmbkp.nonce, 'action' : 'hmbkp_file_list', 'hmbkp_schedule_excludes' : $( '.hmbkp_add_exclude_rule input' ).val(), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val() },
			function( data ) {

				$( '.hmbkp_add_exclude_rule ul' ).remove();
				$( '.hmbkp_add_exclude_rule p' ).remove();

				if ( data.indexOf( 'hmbkp_file_list' ) != -1 )
					$( '.hmbkp_add_exclude_rule' ).append( data );

				else
					$( '.hmbkp_add_exclude_rule' ).append( '<p>There was an error previewing the exclude rule.</p>' );

				$( '.hmbkp-edit-schedule-excludes-form' ).addClass( 'hmbkp-exclude-preview-open' );

				$.colorbox.resize();

			}
		)

	} );

	// Fire the preview button when the enter key is pressed in the preview input
	$( document ).on( 'keypress', '.hmbkp_add_exclude_rule input', function( e ) {

		if ( ! $( '.hmbkp_add_exclude_rule input' ).val() )
			return true;

		var code = ( e.keyCode ? e.keyCode : e.which );

		if ( code != 13 )
			return true;

		$( '.hmbkp_preview_exclude_rule' ).click();

		e.preventDefault();

	} );

	// Cancel add exclude rule
	$( document ).on( 'click', '.hmbkp_cancel_save_exclude_rule, .hmbkp-edit-schedule-excludes-form .submit button', function() {

		 $( '.hmbkp_add_exclude_rule ul' ).remove();
		 $( '.hmbkp_add_exclude_rule p' ).remove();

		 $( '.hmbkp-edit-schedule-excludes-form' ).removeClass( 'hmbkp-exclude-preview-open' );

		 $.colorbox.resize();

	} );

	// Add exclude rule
	$( document ).on( 'click', '.hmbkp_save_exclude_rule', function() {

		$( this ).addClass( 'hmbkp-ajax-loading' ).attr( 'disabled', 'disabled' );

		$.post(
			ajaxurl,
			{ 'nonce' : hmbkp.nonce, 'action' : 'hmbkp_add_exclude_rule', 'hmbkp_exclude_rule' : $( '.hmbkp_add_exclude_rule input' ).val(), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val() },
			function( data ) {
				$( '.hmbkp-edit-schedule-excludes-form' ).replaceWith( data );
				$( '.hmbkp-edit-schedule-excludes-form' ).show();
				$( '.hmbkp-tabs' ).tabs();
				$.colorbox.resize();
			}
		);

	} );

	// Remove exclude rule
	$( document ).on( 'click', '.hmbkp-edit-schedule-excludes-form td a', function( e ) {

		$( this ).addClass( 'hmbkp-ajax-loading' ).text( '' ).attr( 'disabled', 'disabled' );

		$.colorbox.resize();

		e.preventDefault();

		$.get(
			ajaxurl,
			{ 'action' : 'hmbkp_delete_exclude_rule', 'hmbkp_exclude_rule' : $( this ).closest( 'td' ).attr( 'data-hmbkp-exclude-rule' ), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val() },
			function( data ) {
				$( '.hmbkp-edit-schedule-excludes-form' ).replaceWith( data );
				$( '.hmbkp-edit-schedule-excludes-form' ).show();
				$( '.hmbkp-tabs' ).tabs();
				$.colorbox.resize();
			}
		);

	} );

	// Edit schedule form submit
	$( document ).on( 'submit', 'form.hmbkp-form', function( e ) {

		var $isDestinationSettingsForm = $( this ).find( 'button[type="submit"]' ).hasClass( "dest-settings-save" );

		var isNewSchedule = $( this ).closest( 'form' ).attr( 'data-schedule-action' ) == 'add' ? true : false;
		var scheduleId    = $( this ).closest( 'form' ).find( '[name="hmbkp_schedule_id"]' ).val();

		// Only continue if we have a schedule id
		if ( typeof( scheduleId ) == 'undefined' )
			return;

		// Warn that backups will be deleted if max backups has been set to less than the number of backups currently stored
		if ( ! isNewSchedule && Number( $( 'input[name="hmbkp_schedule_max_backups"]' ).val() ) < Number( $( '.hmbkp_manage_backups_row' ).size() ) && ! confirm( hmbkp.remove_old_backups ) )
			return false;

		$( this ).find( 'button[type="submit"]' ).addClass( 'hmbkp-ajax-loading' ).attr( 'disabled', 'disabled' );

		$( '.hmbkp-error span' ).remove();
		$( '.hmbkp-error' ).removeClass( 'hmbkp-error' );

		e.preventDefault();

		$.get(
			ajaxurl + '?' + $( this ).serialize(),
			{ 'action'	: 'hmbkp_edit_schedule_submit' },
			function( data ) {

				if ( ( data.success === true ) && ( $isDestinationSettingsForm === false ) ) {

					$.colorbox.close();

					// Reload the page so we see changes
					if ( isNewSchedule )
						location.replace( '//' + location.host + location.pathname + '?page=' + hmbkp.page_slug + '&hmbkp_schedule_id=' + scheduleId );

					else
						location.reload();

				} else if( data.success === true ) {
					// nothing for now
				} else {

					// Get the errors json string
					var errors = data.data;

					// Loop through the errors
					$.each( errors, function( key, value ) {

						var selector = key.replace(/(:|\.|\[|\])/g,'\\$1');

						// Focus the first field that errored
						if ( typeof( hmbkp_focused ) == 'undefined' ) {

							$( '#' + selector ).focus();

							hmbkp_focused = true;

						}

						// Add an error class to all fields with errors
						$( 'label[for=' + selector + ']' ).addClass( 'hmbkp-error' );

						$( '#' + selector ).next( 'span' ).remove();

						// Add the error message
						$( '#' + selector ).after( '<span class="hmbkp-error">' + value + '</span>' );


					} );

				}

			}
		);

	} );

	// Test the cron response using ajax
	$.post( ajaxurl, { 'nonce' : hmbkp.nonce, 'action' : 'hmbkp_cron_test' },
		 function( data ) {
			 if ( data != 1 ) {
				 	$( '.wrap > h2' ).after( data );
			 }
		 }
	);

	// Calculate the estimated backup size
	if ( $( '.hmbkp-schedule-sentence .calculating' ).size() ) {
		$.post( ajaxurl, { 'nonce' : hmbkp.nonce, 'action' : 'hmbkp_calculate', 'hmbkp_schedule_id' : $( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' ) },
			function( data ) {

				if ( data.indexOf( 'title' ) != -1 )
					$( '.hmbkp-schedule-sentence' ).replaceWith( data );

				// Fail silently for now
				else
					$( '.calculating' ).remove();

			}
		).error( function() {

			// Fail silently for now
			$( '.calculating' ).remove();

		} );
	}

	if ( $( '.hmbkp-schedule-sentence.hmbkp-running' ).size() )
		hmbkpRedirectOnBackupComplete( $( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' ), true );

	// Run a backup
	$( document ).on( 'click', '.hmbkp-run', function( e ) {

		$( this ).closest( '.hmbkp-schedule-sentence' ).addClass( 'hmbkp-running' );

		$( '.hmbkp-error' ).removeClass( 'hmbkp-error' );

		scheduleId = $( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' );

		ajaxRequest = $.post(
			ajaxurl,
			{ 'nonce' : hmbkp.nonce, 'action' : 'hmbkp_run_schedule', 'hmbkp_schedule_id' : scheduleId }
		).done( function( data ) {

			hmbkpCatchResponseAndOfferToEmail( data );

		// Redirect back on error
		} ).fail( function( jqXHR, textStatus ) {

					hmbkpCatchResponseAndOfferToEmail( jqXHR.responseText );

		} );

		setTimeout( function() {
			hmbkpRedirectOnBackupComplete( scheduleId, false )
		}, 1000 );

		e.preventDefault();

	} );

} );

function hmbkpToggleScheduleFields( recurrence  ){

	recurrence = typeof recurrence !== 'undefined' ? recurrence : 'manually';

	var settingFields         = jQuery( '.recurring-setting');
	var scheduleSettingFields = jQuery( '#schedule-start');
	var twiceDailyNote        = jQuery( 'p.twice-js' );

	switch( recurrence ) {

		case 'manually':
			settingFields.hide();
			break;

		case 'hmbkp_hourly' : // fall through
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
			jQuery( '#start-day' ).show();
			scheduleSettingFields.show();
			twiceDailyNote.hide();
			break;

		case 'hmbkp_monthly' :
			settingFields.hide();
			scheduleSettingFields.show();
			jQuery( '#start-date' ).show();
			twiceDailyNote.hide();
			break;

	}

	jQuery.colorbox.resize();

}

function hmbkpCatchResponseAndOfferToEmail( data ) {

	// Backup Succeeded
	if ( ! data || data == 0 )
		location.reload( true );

	// The backup failed, show the error and offer to have it emailed back
	else {

		jQuery( '.hmbkp-schedule-sentence.hmbkp-running' ).removeClass( 'hmbkp-running' ).addClass( 'hmbkp-error' );

		jQuery.post(
			ajaxurl,
			{ 'nonce' : hmbkp.nonce, 'action' : 'hmbkp_backup_error', 'hmbkp_error' : data },
			function( data ) {

				if ( ! data || data == 0 )
					return;

				jQuery.colorbox( {
					'innerWidth'	: "320px",
					'maxHeight'		: "100%",
			        'html'			: data,
			        'overlayClose'	: false,
				    'escKey'		: false,
					'onLoad'		: function() {
						jQuery( '#cboxClose' ).remove();
						jQuery.colorbox.resize();
					}
		        } );

			}
		);

	}

	jQuery( document ).one( 'click', '.hmbkp_send_error_via_email', function( e ) {

		e.preventDefault();

		jQuery( this ).addClass( 'hmbkp-ajax-loading' ).attr( 'disabled', 'disabled' );

		jQuery.post(
		    ajaxurl,
		    { 'nonce' : hmbkp.nonce, 'action' : 'hmbkp_email_error', 'hmbkp_error' : data },
			function( data ) {
				jQuery.colorbox.close();
			}

		)

	} );

}

function hmbkpRedirectOnBackupComplete( schedule_id, redirect ) {

	jQuery.post(
		ajaxurl,
		{ 'nonce':hmbkp.nonce, 'action' : 'hmbkp_is_in_progress', 'hmbkp_schedule_id' : jQuery( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' ) },
		function( data ) {

			if ( data == 0 && redirect === true && ! jQuery( '.hmbkp-error' ).size() ) {
				location.reload( true );

			} else {

				if ( data != 0 ) {

					redirect = true;

					jQuery( '.hmbkp-status' ).remove();
					jQuery( '.hmbkp-schedule-actions' ).replaceWith( data );

				}

				setTimeout( function() {
					hmbkpRedirectOnBackupComplete( schedule_id, redirect );
				}, 5000 );

			}
		}
	);

}
