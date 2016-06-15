<?php

$_POST['action'] = 'hmbkp_request_credentials';
$extra_fields    = array( 'action' );

if ( ! isset( $_GET['creation_error'] ) ) {
	request_filesystem_credentials( admin_url( 'admin-post.php' ), '', isset( $_GET['connection_error'] ), false, $extra_fields );
}
