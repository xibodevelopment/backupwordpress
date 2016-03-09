<?php

$url = esc_url( admin_url( 'admin-post.php' ) );

$_POST['action'] = 'hmbkp_request_credentials';
$extra_fields = array( 'action' );

if ( ! isset( $_GET['creation_error'] ) ) {
	request_filesystem_credentials( $url, '', isset( $_GET['connection_error'] ), false, $extra_fields );
}
