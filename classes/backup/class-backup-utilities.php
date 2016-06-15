<?php

namespace HM\BackUpWordPress;

use Symfony\Component\Process\Process as Process;

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
	 * Attempt to work out path to a cli executable.
	 *
	 * @param  array $paths An array of paths to check against.
	 *
	 * @return string|false        The path to the executable.
	 */
	public static function get_executable_path( $paths ) {

		if ( ! function_exists( 'proc_open' ) || ! function_exists( 'proc_close' ) ) {
			return false;
		}

		$paths = array_map( 'wp_normalize_path', $paths );

		foreach ( $paths as $path ) {

			/**
			 * Attempt to call `--version` on each path, the one which works
			 * must be the correct path.
			 */
			 $process = new Process( $path . ' --version' );

			try {
				$process->run();
			} catch ( \Exception $e ) {
				return false;
			}

			if ( $process->isSuccessful() ) {
				return $path;
			}
		}

		return false;

	}
}
