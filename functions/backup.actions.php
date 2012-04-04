<?php

function hmbkp_backup_complete( $backup ) {

	if ( $backup->errors() ) {

    	hmbkp_cleanup();

    	$file = hmbkp_path() . '/.backup_errors';

		if ( file_exists( $file ) )
			unlink( $file );

    	if ( ! $handle = @fopen( $file, 'w' ) )
    		return;

		fwrite( $handle, json_encode( $backup->errors() ) );

    	fclose( $handle );

    } elseif ( $backup->warnings() ) {

		$file = hmbkp_path() . '/.backup_warnings';

		if ( file_exists( $file ) )
  			unlink( $file );

		if ( ! $handle = @fopen( $file, 'w' ) )
  	  		return;

  		fwrite( $handle, json_encode( $backup->warnings() ) );

		fclose( $handle );

	}

}
add_action( 'hmbkp_backup_complete', 'hmbkp_backup_complete' );