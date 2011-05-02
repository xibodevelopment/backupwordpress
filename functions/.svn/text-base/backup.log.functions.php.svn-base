<?php

/**
 * hmbkp_write_log function.
 *
 * @param array $log
 * @param string $return. (default: "")
 */
function hmbkp_write_log( &$log ) {

	global $wp_version;

	$time = hmbkp_timestamp();

    if ( !is_array( $log ) )
    	return;

    $logdir = trailingslashit( hmbkp_path() ) . 'logs';

    if ( !is_dir( $logdir ) )
    	mkdir( $logdir, 0755 );

    $logname = $logdir . '/' . $log['filename'] . '.log';
    $logfile = '';

    if ( !file_exists( $logname ) ) :

    	// Write some environmental information for debugging purposes
    	$logfile .= "WordPress Version: " . $wp_version . "\n";

    endif;

    if ( is_array( $log['logfile'] ) ) :

    	foreach ( $log['logfile'] as $l ) :

    		if ( is_array( $l ) )
    			$l = serialize( $l );

    		$logfile .= "## " . hmbkp_timestamp() . ' : ' . $l . "\n";

    	endforeach;

    	// Write the log
    	if ( !$handle = fopen( $logname, 'a' ) )
    	    error_log( 'Logfile could not be opened for writing: ' . $logname );

    	if ( !fwrite( $handle, $logfile ) )
    	    error_log( 'Logfile not writable: ' . $logname );

    	fclose( $handle );

	    $log['logfile'] = array();

    	return true;

    endif;

}