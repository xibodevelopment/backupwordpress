<?php

namespace HM\BackUpWordPress;

/**
 * A set of Backup Utility functions
 */
class Backup_Utilities {

	/**
	 * Checks whether Safe Mode is currently on
	 *
	 * @param  string  $ini_get_callback By default we use `ini_get` to check for
	 *                                   the Safe Mode setting but this can be
	 *                                   overridden for testing purposes.
	 *
	 * @return boolean                   Whether Safe Mode is on or off.
	 */
	public static function is_safe_mode_on( $ini_get_callback = 'ini_get' ) {

		$safe_mode = @call_user_func( $ini_get_callback, 'safe_mode' );

		if ( $safe_mode && strtolower( $safe_mode ) !== 'off' ) {
			return true;
		}

		return false;

	}

	/**
	 * Check whether it's possible to use `exec`.
	 *
	 * @return boolean [description]
	 */
	public static function is_exec_available() {

		// You can't use exec if Safe Mode is on.
		if ( self::is_safe_mode_on() ) {
			return false;
		}

		// Check if exec is specifically disabled
		if ( self::is_function_disabled( 'exec' ) ) {
			return false;
		}

		// Some servers seem to disable escapeshellcmd / escapeshellarg separately to exec, in
		// that instance we don't want to use exec as it's insecure
		if ( self::is_function_disabled( 'escapeshellcmd' ) || self::is_function_disabled( 'escapeshellarg' ) ) {
			return false;
		}

		// Can we issue a simple echo command?
		exec( 'echo backupwordpress', $output, $return );

		if ( $return !== 0 ) {
			return false;
		}

		return true;

	}

	/**
	 * Check whether a PHP function has been disabled.
	 *
	 * @param  string  $function         The function you want to test for.
	 * @param  string  $ini_get_callback By default we check with ini_get, but
	 *                                   it's possible to overridde this for
	 *                                   testing purposes.
	 *
	 * @return boolean                   Whether the function is disabled or not.
	 */
	public static function is_function_disabled( $function, $ini_get_callback = 'ini_get' ) {

		// Suhosin stores it's disabled functions in `suhosin.executor.func.blacklist`
		$suhosin_blacklist = array_map( 'trim', explode( ',', @call_user_func( $ini_get_callback, 'suhosin.executor.func.blacklist' ) ) );

		// PHP supports disabling functions by adding them to `disable_functions` in php.ini.
		$disabled_functions = array_map( 'trim', explode( ',', @call_user_func( $ini_get_callback, 'disable_functions' ) ) );

		if ( in_array( $function, array_merge( $suhosin_blacklist, $disabled_functions ) ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Attempt to work out path to a cli executable.
	 *
	 * @param  array $paths An array of paths to check against.
	 *
	 * @return string|false        The path to the executable.
	 */
	public static function get_executable_path( $paths ) {

		if ( ! self::is_exec_available() ) {
			return false;
		}

		$paths = array_map( 'wp_normalize_path', $paths );

		foreach ( $paths as $path ) {

			$output = $result = 0;

			/**
			 * Attempt to call `--version` on each path, the one which works
			 * must be the correct path.
			 *
			 * We pipe STDERR to /dev/null so we don't leak errors.
			 */
			exec( escapeshellarg( $path ) . ' --version ' . ignore_stderr(), $output, $result );

			// If the command executed successfully then this must be the correct path
			if ( $result === 0 ) {
				return $path;
			}

		}

		return false;

	}

}
