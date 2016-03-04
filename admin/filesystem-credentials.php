<?php

$url = esc_url( admin_url( 'admin-post.php' ) );

$_POST['action'] = 'hmbkp_request_credentials';
$extra_fields = array( 'action' );

$connection_error = isset( $_GET['connection_error'] ) ? true : false;

if ( isset( $_GET['creation_error'] ) ) {
	echo 'we connected to your server successfully but weren\'t able to automatically create the directory.';
	return;
}

if ( false === ( $creds = request_filesystem_credentials( $url, '', $connection_error, false, $extra_fields ) ) ) {
	return; // stop processing here
}
