jQuery( document ).ready( function( $ ) {

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

	$( '.hmbkp_advanced-options-toggle' ).click( function() {
		$( '#hmbkp_advanced-options' ).toggle();
	} );

} );

function hmbkpRedirectOnBackupComplete() {

	img = jQuery( '<div>' ).append( jQuery( '.hmbkp_running a.button[disabled]:first img' ).clone() ).remove().html();

	jQuery.get( ajaxurl, { 'action' : 'hmbkp_is_in_progress' },

		function( data ) {

			if ( data == 0 ) {

				location.reload( true );

			} else {

				setTimeout( 'hmbkpRedirectOnBackupComplete();', 5000 );

				jQuery( '.hmbkp_running a.button[disabled]:first' ).html( img + data );

			}
		}
	);

}