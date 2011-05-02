<?php

/**
 * Fallback for creating zip archive if zip command is
 * unnavailable.
 *
 * Uses the PCLZIP library that ships with WordPress
 *
 * @todo support zipArchive
 * @param string $backup_tmp_dir
 * @param string $backup_filepath
 */
function hmbkp_archive_files_fallback( $backup_tmp_dir, $backup_filepath ) {

	// Try PCLZIP
	require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

	$archive = new PclZip( $backup_filepath );
	$archive->create( $backup_tmp_dir, PCLZIP_OPT_REMOVE_PATH, $backup_tmp_dir );

}