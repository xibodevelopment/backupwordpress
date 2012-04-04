<?php

/**
  * Email backup.
  *
  * @todo should be hooked in as a service
  *	@param $file
  * @return bool
  */
function hmbkp_email_backup() {

	//$file = HM_Backup::get_instance()->archive_filepath();

	if ( ! hmbkp_get_email_address() || ! file_exists( $file ) )
		return;

	update_option( 'hmbkp_email_error', 'hmbkp_email_failed' );

	// Raise the memory and time limit
	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
	@set_time_limit( 0 );

	// @todo admin_url?
	$download = get_bloginfo( 'wpurl' ) . '/wp-admin/tools.php?page=' . HMBKP_PLUGIN_SLUG . '&hmbkp_download=' . base64_encode( $file );

	$domain = parse_url( get_bloginfo( 'url' ), PHP_URL_HOST ) . parse_url( get_bloginfo( 'url' ), PHP_URL_PATH );

	$subject = sprintf( __( 'Backup of %s', 'hmbkp' ), $domain );
	$message = sprintf( __( "BackUpWordPress has completed a backup of your site %s.\n\nThe backup file should be attached to this email.\n\nYou can also download the backup file by clicking the link below:\n\n%s\n\nKind Regards\n\n The Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), get_bloginfo( 'url' ), $download );
	$headers = 'From: BackUpWordPress <' . get_bloginfo( 'admin_email' ) . '>' . "\r\n";

	// Try to send the email
	$sent = wp_mail( array_filter( hmbkp_get_email_address(), 'is_email' ), $subject, $message, $headers, $file );

	// If it failed- Try to send a download link - The file was probably too large.
	if ( ! $sent ) :

		$subject = sprintf( __( 'Backup of %s', 'hmbkp' ), $domain );
		$message = sprintf( __( "BackUpWordPress has completed a backup of your site %s.\n\nUnfortunately the backup file was too large to attach to this email.\n\nYou can download the backup file by clicking the link below:\n\n%s\n\nKind Regards\n\n The Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), get_bloginfo( 'url' ), $download );

		$sent = wp_mail( array_filter( hmbkp_get_email_address(), 'is_email' ), $subject, $message, $headers );

	endif;

	// Set option for email not sent error
	if ( $sent )
		delete_option( 'hmbkp_email_error' );

	return true;

}

/**
 * Check if a backup is running
 *
 * @return bool
 */
function hmbkp_in_progress() {
	return file_exists( hmbkp_path() . '/.backup_running' ) ? reset( explode( '::', file_get_contents( hmbkp_path() .'/.backup_running' ) ) ) : '';
}

function hmbkp_backup_errors() {

	if ( ! file_exists( hmbkp_path() . '/.backup_errors' ) )
		return '';

	return file_get_contents( hmbkp_path() . '/.backup_errors' );

}

function hmbkp_backup_warnings() {

	if ( ! file_exists( hmbkp_path() . '/.backup_warnings' ) )
		return '';

	return file_get_contents( hmbkp_path() . '/.backup_warnings' );

}