jQuery( document ).ready( function( $ ) {

	// Setup the tabs
	$( '.hmbkp-tabs' ).tabs();

	// Set the first tab to be active
	if ( ! $( '.subsubsub a.current' ).size() )
		$( '.subsubsub li:first a').addClass( 'current' );

	// Replace fancybox href with ajax url
	$( '.fancybox' ).each( function() {
		$( this ).attr( 'href', $( this ).attr( 'href' ).replace( userSettings['url'] + 'wp-admin/tools.php', ajaxurl ) );
	} );

	// Initialize fancybox
	$( '.fancybox' ).fancybox( {

		'modal'		: true,
		'type'		: 'ajax',
		'maxWidth'		: 320,
		'afterShow'	: function() {

			$( '.hmbkp-tabs' ).tabs();

			$( '<p class="submit"><button type="button" class="button-primary">Update</button></p>' ).appendTo( '.hmbkp-form fieldset + fieldset' );

		}

	} );

	// Show delete confirm message for delete links
	$( document ).on( 'click', '.delete-action', function( e ) {

		if ( ! showNotice.warn() )
			e.preventDefault();

	} );

	// Toggle excludes visibility based on backup type
	$( document ).on( 'change', '[name="hmbkp_schedule_type"]', function() {

		if ( $( this ).val() == 'database' )
			$( 'label.hmbkp-excludes' ).slideUp( 'fast' );

		else if ( $( 'label.hmbkp-excludes' ).is( ':hidden' ) )
			$( 'label.hmbkp-excludes' ).removeClass( 'hidden' ).hide().slideDown( 'fast' );

	} );

	// Preview exclude rule
	$( document ).on( 'click', '.hmbkp_preview_exclude_rule', function() {

		if ( ! $( '.hmbkp_add_exclude_rule input' ).val() ) {
			$( '.hmbkp_add_exclude_rule ul' ).remove();
			$( '.hmbkp_add_exclude_rule p' ).remove();
			return;
		}

		$.post(
			ajaxurl,
			{ 'action'	: 'hmbkp_file_list', 'hmbkp_schedule_excludes' : $( '.hmbkp_add_exclude_rule input' ).val(), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val(), 'hmbkp_file_method' : 'get_excluded_files' },
			function( data ) {

				$( '.hmbkp_add_exclude_rule ul' ).remove();
				$( '.hmbkp_add_exclude_rule p' ).remove();

				$( '.hmbkp_add_exclude_rule' ).append( data );

				$( '.hmbkp-edit-schedule-excludes-form' ).addClass( 'hmbkp-exclude-preview-open' );

			}
		);

	} );

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

		$.post(
			ajaxurl,
			{ 'action' : 'hmbkp_add_exclude_rule', 'hmbkp_exclude_rule' : $( '.hmbkp_add_exclude_rule input' ).val(), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val() },
			function( data ) {
				var backButton = $( '.hmbkp-edit-schedule-excludes-form p.submit' ).clone( true );
				$( '.hmbkp-edit-schedule-excludes-form' ).replaceWith( data );
				$( '.hmbkp-edit-schedule-excludes-form' ).show().append( backButton );
				$( '.hmbkp-tabs' ).tabs();
			}
		);

	} );

	$( document ).on( 'click', '.hmbkp-edit-schedule-excludes-form td a', function( e ) {

		e.preventDefault();

		$.post(
			ajaxurl,
			{ 'action' : 'hmbkp_delete_exclude_rule', 'hmbkp_exclude_rule' : $( this ).closest( 'td' ).attr( 'data-hmbkp-exclude-rule' ), 'hmbkp_schedule_id' : $( '[name="hmbkp_schedule_id"]' ).val() },
			function( data ) {
				var backButton = $( '.hmbkp-edit-schedule-excludes-form p.submit' ).clone( true );
				$( '.hmbkp-edit-schedule-excludes-form' ).replaceWith( data );
				$( '.hmbkp-edit-schedule-excludes-form' ).show().append( backButton );
				$( '.hmbkp-tabs' ).tabs();
			}
		);

	} );

	// Edit schedule form submit
	$( document ).on( 'submit', 'form.hmbkp-form', function( e ) {

		$( '.hmbkp_error span' ).remove();
		$( '.hmbkp_error' ).removeClass( 'hmbkp-error' );

		e.preventDefault();

		$.post(
			ajaxurl + '?' + $( this ).serialize(),
			{ 'action'	: 'hmnkp_edit_schedule_submit' },
			function( data ) {

				// Assume success if no data passed back
				if ( ! data ) {

					$.fancybox.close();

					// Reload the page so we see changes
					location.reload();

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

	/* 	LEGACY */

	if ( $( '.hmbkp_running' ).size() ) {
		hmbkpRedirectOnBackupComplete();
	}

	if ( $( '.hmbkp_estimated-size .calculate' ).size() ) {
		$.get( ajaxurl, { 'action' : 'hmbkp_calculate' },
		    function( data ) {
		    	$( '.hmbkp_estimated-size .calculate' ).fadeOut( function() {
		    		$( this ).empty().append( data );
		    	} ).fadeIn();
		    }
		);
	}

	$.get( ajaxurl, { 'action' : 'hmbkp_cron_test' },
	    function( data ) {
	    	if ( data != 1 ) {
		    	$( '.wrap > h2' ).after( data );
		    }
	    }
	);

	$( '#hmbkp_backup:not(.hmbkp_running)' ).live( 'click', function( e ) {

		$.ajaxSetup( { 'cache' : false } );

		ajaxRequest = $.get( ajaxurl, { 'action' : 'hmbkp_backup' } );

		$( this ).text( 'Starting Backup' ).addClass( 'hmbkp_running' );

	  	setTimeout( function() {

			ajaxRequest.abort();

	  		hmbkpRedirectOnBackupComplete();

	  	}, 500 );

		e.preventDefault();

	} );

} );

function hmbkpRedirectOnBackupComplete() {

	jQuery.get( ajaxurl, { 'action' : 'hmbkp_is_in_progress' },

		function( data ) {

			if ( data == 0 ) {

				location.reload( true );

			} else {

				setTimeout( 'hmbkpRedirectOnBackupComplete();', 1000 );

				jQuery( '#hmbkp_backup' ).replaceWith( data );

			}
		}
	);

}