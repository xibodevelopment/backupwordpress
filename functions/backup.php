<?php

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