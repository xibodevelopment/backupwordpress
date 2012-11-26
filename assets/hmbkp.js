jQuery( document ).ready( function( $ ) {

	// Don't ever cache ajax requests
	$.ajaxSetup( { 'cache' : false } );

	// Remove the loading class when ajax requests complete
	$( document ).ajaxComplete( function() {
		$( '.hmbkp-ajax-loading' ).removeClass( 'hmbkp-ajax-loading' );
	} );

	$( document ).on( 'click', '.hmbkp-fancybox-close', function() {
	    $.fancybox.close();
	} );

	// Setup the tabs
	$( '.hmbkp-tabs' ).tabs();

	// Set the first tab to be active
	if ( ! $( '.subsubsub a.current' ).size() )
		$( '.subsubsub li:first a').addClass( 'current' );

	// Initialize fancybox
	$( '.fancybox' ).fancybox( {

		'modal'		: true,
		'type'		: 'ajax',
		'maxWidth'	: 320,
		'afterShow'	: function() {

			$( '.hmbkp-tabs' ).tabs();

			if ( $( ".hmbkp-form p.submit:contains('" + objectL10n.update + "')" ).size() )
				$( '<button type="button" class="button-secondary hmbkp-fancybox-close">' + objectL10n.cancel + '</button></p>' ).appendTo( '.hmbkp-form p.submit' );

		}

	} );

	// Show delete confirm message for delete schedule
	$( document ).on( 'click', '.hmbkp-schedule-actions .delete-action', function( e ) {

		if ( ! confirm( objectL10n.delete_schedule ) )
			e.preventDefault();

	} );

	// Show delete confirm message for delete backup
	$( document ).on( 'click', '.hmbkp_manage_backups_row .delete-action', function( e ) {

		if ( ! confirm( objectL10n.delete_backup ) )
			e.preventDefault();

	} );

	// Show delete confirm message for remove exclude rule
	$( document ).on( 'click', '.hmbkp-edit-schedule-excludes-form .delete-action', function( e ) {

		if ( ! confirm( objectL10n.remove_exclude_rule ) )
			e.preventDefault();

	} );

	// Preview exclude rule
	$( document ).on( 'click', '.hmbkp_preview_exclude_rule', function() {

		if ( ! $( '.hmbkp_add_exclude_rule input' ).val() ) {
			$( '.hmbkp_add_exclude_rule ul' ).remove();
			$( '.hmbkp_add_exclude_rule p' ).remove();
			return;
		}

		$( this ).addClass( 'hmbkp-ajax-loading' );

		$.post(
			ajaxurl,
			{ 'action'	: 'hmbkp_file_list', 'hmbkp_schedule_excludes' : $( '.hmbkp_add_exclude_rule input' ).val(), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val() },
			function( data ) {

				$( '.hmbkp_add_exclude_rule ul' ).remove();
				$( '.hmbkp_add_exclude_rule p' ).remove();

				if ( data.indexOf( 'hmbkp_file_list' ) != -1 )
					$( '.hmbkp_add_exclude_rule' ).append( data );

				else
					$( '.hmbkp_add_exclude_rule' ).append( '<p>There was an error previewing the exclude rule.</p>' );

				$( '.hmbkp-edit-schedule-excludes-form' ).addClass( 'hmbkp-exclude-preview-open' );

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

	} );

	// Toggle additional fieldsets on
	$( document ).on( 'click', '.hmbkp-toggle-fieldset', function() {

		// Get the current fieldset
		var fromFieldset = 'fieldset.' + $( this ).closest( 'fieldset' ).attr( 'class' );
		var toFieldset = 'fieldset.' + $( this ).attr( 'data-hmbkp-fieldset' );

		// Show the one we are moving too
		$( toFieldset ).show().find( 'p.submit button' ).data( 'hmbkp-previous-fieldset', fromFieldset );

		// Animate
		$( fromFieldset ).animate( {
			marginLeft : '-100%'
		}, 'fast', function() {
			$( this ).hide();
		} );

	} );

	// Toggle additional fieldsets off
	$( document ).on( 'click', '.hmbkp-form fieldset + fieldset p.submit button', function() {

		// Get the current fieldset
		var fromFieldset = 'fieldset.' + $( this ).closest( 'fieldset' ).attr( 'class' );
		var toFieldset = $( this ).data( 'hmbkp-previous-fieldset' );

		// Show the one we are moving too
		$( toFieldset ).show();

		$( toFieldset ).animate( {
				marginLeft : '0'
			}, 'fast', function() {
				$( fromFieldset ).hide();
			}
		);

	} );

	// Add exclude rule
	$( document ).on( 'click', '.hmbkp_save_exclude_rule', function() {

		$( this ).addClass( 'hmbkp-ajax-loading' );

		$.post(
			ajaxurl,
			{ 'action' : 'hmbkp_add_exclude_rule', 'hmbkp_exclude_rule' : $( '.hmbkp_add_exclude_rule input' ).val(), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val() },
			function( data ) {
				$( '.hmbkp-edit-schedule-excludes-form' ).replaceWith( data );
				$( '.hmbkp-edit-schedule-excludes-form' ).show();
				$( '.hmbkp-tabs' ).tabs();
			}
		);

	} );

	// Remove exclude rule
	$( document ).on( 'click', '.hmbkp-edit-schedule-excludes-form td a', function( e ) {

		$( this ).addClass( 'hmbkp-ajax-loading' ).text( '' );

		e.preventDefault();

		$.get(
			ajaxurl,
			{ 'action' : 'hmbkp_delete_exclude_rule', 'hmbkp_exclude_rule' : $( this ).closest( 'td' ).attr( 'data-hmbkp-exclude-rule' ), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val() },
			function( data ) {
				$( '.hmbkp-edit-schedule-excludes-form' ).replaceWith( data );
				$( '.hmbkp-edit-schedule-excludes-form' ).show();
				$( '.hmbkp-tabs' ).tabs();
			}
		);

	} );

	// Edit schedule form submit
	$( document ).on( 'submit', 'form.hmbkp-form', function( e ) {

		isNewSchedule = $( this ).closest( 'form' ).attr( 'data-schedule-action' ) == 'add' ? true : false;
		scheduleId = $( this ).closest( 'form' ).find( '[name="hmbkp_schedule_id"]' ).val();

		// Warn that backups will be deleted if max backups has been set to less than the number of backups currently stored
		if ( ! isNewSchedule && Number( $( 'input[name="hmbkp_schedule_max_backups"]' ).val() ) < Number( $( '.hmbkp_manage_backups_row' ).size() ) && ! confirm( objectL10n.remove_old_backups ) )
			return false;

		$( this ).find( 'button[type="submit"]' ).addClass( 'hmbkp-ajax-loading' );

		$( '.hmbkp-error span' ).remove();
		$( '.hmbkp-error' ).removeClass( 'hmbkp-error' );

		e.preventDefault();

		$.get(
			ajaxurl + '?' + $( this ).serialize(),
			{ 'action'	: 'hmnkp_edit_schedule_submit' },
			function( data ) {

				// Assume success if no data passed back
				if ( ! data || data == 0 ) {

					$.fancybox.close();

					// Reload the page so we see changes
					if ( isNewSchedule )
						location.replace( '//' + location.host + location.pathname  + '?page=backupwordpress&hmbkp_schedule_id=' + scheduleId );

					else
						location.reload( true );

				} else {

					// Get the errors json string
					errors = JSON.parse( data );

					// Loop through the errors
					$.each( errors, function( key, value ) {

						// Focus the first field that errored
						if ( typeof( hmbkp_focused ) == 'undefined' ) {

							$( '[name="' + key + '"]' ).focus();

							hmbkp_focused = true;

						}

						// Add an error class to all fields with errors
						$( '[name="' + key + '"]' ).closest( 'label' ).addClass( 'hmbkp-error' );

						// Add the error message
						$( '[name="' + key + '"]' ).after( '<span>' + value + '</span>' );

					} );

				}

			}
		);

	} );

	// Test the cron response using ajax
	$.post( ajaxurl, { 'action' : 'hmbkp_cron_test' },
		 function( data ) {
			 if ( data != 1 ) {
				 	$( '.wrap > h2' ).after( data );
			 }
		 }
	);

	// Calculate the estimated backup size
	if ( $( '.hmbkp-schedule-sentence .calculating' ).size() ) {
		$.post( ajaxurl, { 'action' : 'hmbkp_calculate', 'hmbkp_schedule_id' : $( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' ) },
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

	$( document ).on( 'click', '.hmbkp-run', function( e ) {

		$( this ).closest( '.hmbkp-schedule-sentence' ).addClass( 'hmbkp-running' );

		$( '.hmbkp-error' ).removeClass( 'hmbkp-error' );

		scheduleId = $( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' );

		ajaxRequest = $.post(
			ajaxurl,
			{ 'action' : 'hmbkp_run_schedule', 'hmbkp_schedule_id' : scheduleId },
			function( data ) {

				// Backup Succeeded
				if ( ! data || data == 0 ) {
					location.reload( true );
				}

				// The backup failed, show the error and offer to have it emailed back
				else {

					$( '.hmbkp-schedule-sentence.hmbkp-running' ).removeClass( 'hmbkp-running' ).addClass( 'hmbkp-error' );

					$.post(
						ajaxurl,
						{ 'action' : 'hmbkp_backup_error', 'hmbkp_error' : data },
						function( data ) {

							if ( ! data || data == 0 )
								return;

							$.fancybox( {
				                'maxWidth'	: 500,
				                'content'	: data,
				                'modal'		: true
				            } );

						}
					);

				}

				$( document ).one( 'click', '.hmbkp_send_error_via_email', function( e ) {

					e.preventDefault();

					$( this ).addClass( 'hmbkp-ajax-loading' );

					$.post(
					    ajaxurl,
					    { 'action' : 'hmbkp_email_error', 'hmbkp_error' : data },
						function( data ) {
							$.fancybox.close();
						}

					)

				} );

			}

		// Redirect back on error
		).error( function( data ) {
			location.replace( '//' + location.host + location.pathname  + '?page=backupwordpress&action=hmbkp_cancel&reason=broken&hmbkp_schedule_id=' + scheduleId );
		} );

		setTimeout( function() {
			hmbkpRedirectOnBackupComplete( scheduleId, false )
		}, 1000 );

		e.preventDefault();

	} );

} );

function hmbkpRedirectOnBackupComplete( schedule_id, redirect ) {

	jQuery.post(
		ajaxurl,
		{ 'action' : 'hmbkp_is_in_progress', 'hmbkp_schedule_id' : jQuery( '[data-hmbkp-schedule-id]' ).attr( 'data-hmbkp-schedule-id' ) },
		function( data ) {

			if ( data == 0 && redirect === true ) {
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