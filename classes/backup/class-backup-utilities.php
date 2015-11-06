<?php

namespace HM\BackUpWordPress;

class Backup_Utilities {

	public static function is_safe_mode_on( $ini_get_callback = 'ini_get' ) {

		$safe_mode = @call_user_func( $ini_get_callback, 'safe_mode' );

		if ( $safe_mode && strtolower( $safe_mode ) !== 'off' ) {
			return true;
		}

		return false;

	}

	public static function is_exec_available() {

		if ( self::is_safe_mode_on() ) {
			return false;
		}

		if ( self::is_function_disabled( 'exec' ) ) {
			return false;
		}

		if ( self::is_function_disabled( 'escapeshellcmd' ) ) {
			return false;
		}

		if ( self::is_function_disabled( 'escapeshellarg' ) ) {
			return false;
		}

		// Can we issue a simple echo command?
		exec( 'echo backupwordpress', $output, $return );

		if ( $return !== 0 ) {
			return false;
		}

		return true;

	}

	public static function is_function_disabled( $function, $ini_get_callback = 'ini_get' ) {

		$suhosin_blacklist = array_map( 'trim', explode( ',', @call_user_func( $ini_get_callback, 'suhosin.executor.func.blacklist' ) ) );
		$ini_disabled_functions = array_map( 'trim', explode( ',', @call_user_func( $ini_get_callback, 'disable_functions' ) ) );

		$disabled_functions = array_merge( $suhosin_blacklist, $ini_disabled_functions );

		if ( in_array( $function, $disabled_functions ) ) {
			return true;
		}

		return false;

	}

	public static function get_executable_path( $paths ) {

		if ( ! self::is_exec_available() ) {
			return false;
		}

		$paths = array_map( 'wp_normalize_path', $paths );

		foreach ( $paths as $path ) {

			// Pipe STDERR to /dev/null so we don't leak errors
			exec( escapeshellarg( $path ) . ' --version 2>/dev/null', $output, $result );

			// If the command executed successfully then this must be the correct path
			if ( $result === 0 ) {
				return $path;
			}

		}

		return false;

	}

	public static function get_home_path( $site_path = ABSPATH ) {

		if ( defined( 'HMBKP_ROOT' ) && HMBKP_ROOT ) {
			return wp_normalize_path( HMBKP_ROOT );
		}

		$home_path = $site_path;

		// Handle wordpress installed in a subdirectory
		if ( file_exists( dirname( $site_path ) . '/wp-config.php' ) && ! file_exists( $site_path . '/wp-config.php' ) && file_exists( dirname( $site_path ) . '/index.php' ) ) {
			$home_path = dirname( $site_path );
		}

		// Handle wp-config.php being above site_path
		if ( file_exists( dirname( $site_path ) . '/wp-config.php' ) && ! file_exists( $site_path . '/wp-config.php' ) && ! file_exists( dirname( $site_path ) . '/index.php' ) ) {
			$home_path = $site_path;
		}

		return wp_normalize_path( untrailingslashit( $home_path ) );

	}

}