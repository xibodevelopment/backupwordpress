<?php

/**
 * Fallback for creating zip archive if zip command is
 * unnavailable.
 *
 * Uses the PCLZIP library that ships with WordPress
 *
 * @todo support zipArchive
 * @param string $path
 */
function hmbkp_archive_files_fallback( $path ) {

	require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

	$archive = new PclZip( $path );

	// Zip up everything
	if ( ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY ) || !defined( 'HMBKP_DATABASE_ONLY' ) )
		$archive->create( ABSPATH, PCLZIP_OPT_REMOVE_PATH, ABSPATH, PCLZIP_CB_PRE_ADD, 'hmbkp_pclzip_exclude' );

	// Only zip up the database
	if ( defined( 'HMBKP_DATABASE_ONLY' ) && HMBKP_DATABASE_ONLY )
		$archive->create( hmbkp_path() . '/database_' . DB_NAME . '.sql', PCLZIP_OPT_REMOVE_PATH, hmbkp_path() );

}

/**
 * Add file callback, excludes files in the backups directory
 * and sets the database dump to be stored in the root
 * of the zip
 * 
 * @param string $event
 * @param array &$file
 * @return bool
 */
function hmbkp_pclzip_exclude( $event, &$file ) {

	$excludes = hmbkp_exclude_string( 'pclzip' );

	// Include the database file
	if ( strpos( $file['filename'], 'database_' . DB_NAME . '.sql' ) !== false )
		$file['stored_filename'] = 'database_' . DB_NAME . '.sql';

	// Match everything else past the exclude list
	elseif ( preg_match( '(' . $excludes . ')', $file['stored_filename'] ) )
		return false;

	return true;

}