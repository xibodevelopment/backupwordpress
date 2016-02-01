<?php

namespace HM\BackUpWordPress;

/**
 * Manages both the backup path and site root
 *
 * Handles calculating & protecting the directory that backups will be stored in
 * as well as the directory that is being backed up
 */
class Path {

	/**
	 * The path to the directory that backup files are stored in
	 *
	 * @var string $this->path
	 */
	private $path;

	/**
	 * The path to the directory that will be backed up
	 *
	 * @var string $this->root
	 */
	private $root;

	/**
	 * The path to the directory that backup files are stored in
	 *
	 * @var string $this->path
	 */
	private $custom_path;

	/**
	 * Contains the instantiated Path instance
	 *
	 * @var Path $this->instance
	 */
	private static $instance;

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 */
	private function __clone() {}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 */
	private function __wakeup() {}

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @staticvar Path $instance The *Singleton* instances of this class.
	 *
	 * @return Path The *Singleton* instance.
	 */
	public static function get_instance() {

		if ( ! ( self::$instance instanceof Path ) ) {
			self::$instance = new Path();
		}

		return self::$instance;
	}

	/**
	 * Convenience method for quickly grabbing the path
	 */
	public static function get_path() {
		return self::get_instance()->get_calculated_path();
	}

	/**
	 * Convenience method for quickly grabbing the root
	 */
	public static function get_root() {
		return self::get_instance()->get_calculated_root();
	}

	/**
	 * Calculate the path to the site "home" directory.
	 *
	 * The home directory is the path equivalent to the home_url. That is,
	 * the path to the true root of the website. In situations where WordPress is
	 * installed in a subdirectory the home path is different to ABSPATH
	 *
	 * @param string $site_path The site_path to use when calculating the home path, defaults to ABSPATH
	 */
	public static function get_home_path( $site_path = ABSPATH ) {

		if ( defined( 'HMBKP_ROOT' ) && HMBKP_ROOT ) {
			return wp_normalize_path( HMBKP_ROOT );
		}

		$home_path = $site_path;

		if ( path_in_php_open_basedir( dirname( $site_path ) ) ) {

			// Handle wordpress installed in a subdirectory
			// 1. index.php and wp-config.php found in parent dir
			// 2. index.php in parent dir, wp-config.php in $site_path ( wp-config.php can be in both locations )
			if ( ( file_exists( dirname( $site_path ) . '/wp-config.php' ) || file_exists( $site_path . '/wp-config.php' ) )  && file_exists( dirname( $site_path ) . '/index.php' ) ) {
				$home_path = dirname( $site_path );
			}

			// Handle wp-config.php being above site_path
			if ( file_exists( dirname( $site_path ) . '/wp-config.php' ) && ! file_exists( $site_path . '/wp-config.php' ) && ! file_exists( dirname( $site_path ) . '/index.php' ) ) {
				$home_path = $site_path;
			}

		}

		return wp_normalize_path( untrailingslashit( $home_path ) );

	}

	/**
	 * get the calculated path to the directory where backups will be stored
	 */
	private function get_calculated_path() {

		// Calculate the path if needed
		if ( empty( $this->path ) || ! wp_is_writable( $this->path ) ) {
			$this->calculate_path();
		}

		// Ensure the backup directory is protected
		$this->protect_path();

		return wp_normalize_path( $this->path );

	}

	/**
	 * Set the path directly, overriding the default
	 *
	 * @param $path
	 */
	public function set_path( $path ) {

		$this->custom_path = $path;

		// Re-calculate the backup path
		$this->calculate_path();

	}

	/**
	 * get the calculated path to the directory that will be backed up
	 */
	private function get_calculated_root() {

		$root = self::get_home_path();

		if ( defined( 'HMBKP_ROOT' ) && HMBKP_ROOT ) {
			$root = HMBKP_ROOT;
		}

		if ( $this->root ) {
			$root = $this->root;
		}

		return wp_normalize_path( $root );

	}

	/**
	 * Set the root path directly, overriding the default
	 *
	 * @param $root
	 */
	public function set_root( $root ) {
		$this->root = $root;
	}

	public function reset_path() {
		$this->set_path( false );
	}

	/**
	 * Get the path to the default backup location in wp-content
	 */
	public function get_default_path() {
		return trailingslashit( wp_normalize_path( WP_CONTENT_DIR ) ) . 'backupwordpress-' . substr( HMBKP_SECURE_KEY, 0, 10 ) . '-backups';
	}

	/**
	 * Get the path to the fallback backup location in uploads
	 */
	public function get_fallback_path() {

		$upload_dir = wp_upload_dir();

		return trailingslashit( wp_normalize_path( $upload_dir['basedir'] ) ) . 'backupwordpress-' . substr( HMBKP_SECURE_KEY, 0, 10 ) . '-backups';

	}

	/**
	 * Get the path to the custom backup location if it's been set
	 */
	public function get_custom_path() {

		if ( $this->custom_path ) {
			return $this->custom_path;
		}

		if ( defined( 'HMBKP_PATH' ) && wp_is_writable( HMBKP_PATH ) ) {
			return HMBKP_PATH;
		}

		return '';

	}

	/**
	 * Builds an array containing existing backups folders.
	 *
	 * @return array
	 */
	public function get_existing_paths() {

		if ( false === $default = glob( WP_CONTENT_DIR . '/backupwordpress-*-backups', GLOB_ONLYDIR ) ) {
			$default = array();
		}

		$upload_dir = wp_upload_dir();

		if ( false === $fallback = glob( $upload_dir['basedir'] . '/backupwordpress-*-backups', GLOB_ONLYDIR ) ) {
			$fallback = array();
		}

		$paths = array_merge( $default, $fallback );

        $paths = array_map( 'wp_normalize_path', $paths );

		return $paths;

	}

	/**
	 * Returns the first existing path if there is one
	 *
	 * @return string Backup path if found empty string if not
	 */
	public function get_existing_path() {

		$paths = $this->get_existing_paths();

		if ( ! empty( $paths[0] ) ) {
			return $paths[0];
		}

		return '';

	}

	/**
	 * Calculate the backup path and create the directory if it doesn't exist.
	 *
	 * Tries all possible locations and uses the first one possible
	 *
	 * @return
	 */
	public function calculate_path() {

		$paths = array();

		// If we have a custom path then try to use it
		if ( $this->get_custom_path() ) {
			$paths[] = $this->get_custom_path();
		}

		// If there is already a backups directory then try to use that
		if ( $this->get_existing_path() ) {
			$paths[] = $this->get_existing_path();
		}

		// If not then default to a new directory in wp-content
		$paths[] = $this->get_default_path();

		// If that didn't work then fallback to a new directory in uploads
		$paths[] = $this->get_fallback_path();

		// Loop through possible paths, use the first one that exists/can be created and is writable
		foreach ( $paths as $path ) {
			if ( wp_mkdir_p( $path ) && wp_is_writable( $path ) ) { // Also handles fixing perms / directory already exists
				break;
			}
		}

		if ( file_exists( $path ) && wp_is_writable( $path ) ) {
			$this->path = $path;
		}

	}

	/**
	 * Protect the directory that backups are stored in
	 *
	 * - Adds an index.html file in an attempt to disable directory browsing
	 * - Adds a .httaccess file to deny direct access if on Apache
	 *
	 * @param string $reset
	 */
	public function protect_path( $reset = 'no' ) {

		global $is_apache;

		// Protect against directory browsing by including an index.html file
		$index = $this->path . '/index.html';

		if ( 'reset' === $reset && file_exists( $index ) ) {
			@unlink( $index );
		}

		if ( ! file_exists( $index ) && wp_is_writable( $this->path ) ) {
			file_put_contents( $index, '' );
		}

		$htaccess = $this->path . '/.htaccess';

		if ( ( 'reset' === $reset ) && file_exists( $htaccess ) ) {
			@unlink( $htaccess );
		}

		// Protect the directory with a .htaccess file on Apache servers
		if ( $is_apache && function_exists( 'insert_with_markers' ) && ! file_exists( $htaccess ) && wp_is_writable( $this->path ) ) {

			$contents[] = '# ' . sprintf( __( 'This %s file ensures that other people cannot download your backup files.', 'backupwordpress' ), '.htaccess' );
			$contents[] = '';
			$contents[] = '<IfModule mod_rewrite.c>';
			$contents[] = 'RewriteEngine On';
			$contents[] = 'RewriteCond %{QUERY_STRING} !key=' . HMBKP_SECURE_KEY;
			$contents[] = 'RewriteRule (.*) - [F]';
			$contents[] = '</IfModule>';
			$contents[] = '';

			file_put_contents( $htaccess, '' );

			insert_with_markers( $htaccess, 'BackUpWordPress', $contents );

		}

	}

	/**
	 * If we have more than one path then move any existing backups to the current path and remove them
	 */
	public function merge_existing_paths() {

		$paths = $this->get_existing_paths();

		if ( ( $paths && $this->get_custom_path() ) || count( $paths ) > 1 ) {
			foreach ( $paths as $old_path ) {
				$this->move_old_backups( $old_path );
			}
		}

	}

	/**
	 * Move backup files from an existing directory and the new
	 * location
	 *
	 * @param string $path 	The path to move the backups from
	 */
	public function move_old_backups( $from ) {

		if ( ! is_readable( $from ) ) {
			return;
		}

		if ( ! wp_is_writable( Path::get_path() ) ) {
			return;
		}

		// Move any existing backups
		if ( $handle = opendir( $from ) ) {

			// Loop through the backup directory
			while ( false !== ( $file = readdir( $handle ) ) ) {

				// Find all zips
				if ( 'zip' === pathinfo( $file, PATHINFO_EXTENSION ) ) {

					// Try to move them
					if ( ! @rename( trailingslashit( $from ) . $file, trailingslashit( Path::get_path() ) . $file ) ) {


						// If we can't move them then try to copy them
						copy( trailingslashit( $from ) . $file, trailingslashit( Path::get_path() ) . $file );

					}

				}
			}

			closedir( $handle );

		}

		// Delete the old directory if it's inside WP_CONTENT_DIR
		if ( false !== strpos( $from, WP_CONTENT_DIR ) && $from !== Path::get_path() ) {
			rmdirtree( $from );
		}

	}

	/**
	 * Clean any temporary / incomplete backups from the backups directory
	 */
	public function cleanup() {

		// Don't cleanup a custom path, who knows what other stuff is there
		if ( Path::get_path() === $this->get_custom_path() ) {
			return;
		}

		foreach ( new CleanUpIterator( new \DirectoryIterator( $this->path ) ) as $file ) {

			if ( $file->isDot() || ! $file->isReadable() || ! $file->isFile() ) {
				continue;
			}

			@unlink( $file->getPathname() );

		}

	}

}

class CleanUpIterator extends \FilterIterator {

	// Don't match index.html,files with zip extension or status logfiles.
	public function accept() {
		return ! preg_match( '/(index\.html|.*\.zip|.*-running)/', $this->current() );
	}
}
