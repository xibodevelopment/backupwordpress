<?php

// Mark the backup as started
add_action( 'hmbkp_backup_started', 'hmbkp_set_status', 10, 0 );

/**
 * Set the backup status to dumping database
 *
 * @return null
 */
function hmbkp_set_status_dumping_database() {
    hmbkp_set_status( __( 'Dumping database', 'hmbkp' ) );
}
add_action( 'hmbkp_mysqldump_started', 'hmbkp_set_status_dumping_database' );

/**
 * Set the backup status to archiving
 *
 * @return null
 */
function hmbkp_set_status_archiving() {
    hmbkp_set_status( __( 'Creating zip archive', 'hmbkp' ) );
}
add_action( 'hmbkp_archive_started', 'hmbkp_set_status_archiving' );

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