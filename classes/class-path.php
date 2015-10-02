<?php
/**
 * @package BackUpWordPress
 * @subpackage BackUpWordPress/classes
 */

namespace HM\BackUpWordPress;

/**
 * The Backup Path class
 *
 * Handles calculating & protecting the directory that backups will be stored in
 *
 * @todo 	Should be a singleton?
 */
class Path {

	/**
	 * The path to the directory that backup files are stored in
	 *
	 * @var string $this->path
	 */
	protected $path;

	/**
	 * The path to the directory that backup files are stored in
	 *
	 * @var string $this->path
	 */
	protected $custom_path;

	private static $instance;

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
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
	 * Get the path to the directory where backups will be stored
	 */
	public function get_path() {

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

	public function reset_path() {
		$this->set_path( false );
	}

	/**
	 * Get the path to the default backup location in wp-content
	 */
	public function get_default_path() {
		return trailingslashit( WP_CONTENT_DIR ) . 'backupwordpress-' . substr( HMBKP_SECURE_KEY, 0, 10 ) . '-backups';
	}

	/**
	 * Get the path to the fallback backup location in uploads
	 */
	public function get_fallback_path() {

		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . 'backupwordpress-' . substr( HMBKP_SECURE_KEY, 0, 10 ) . '-backups';

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
			if ( wp_mkdir_p( $path ) ) { // Also handles fixing perms / directory already exists
				break;
			}
		}

		if ( isset( $path ) ) {
			$this->path = $path;
		}

	}

	/**
	 * @param string $reset
	 */
	public function protect_path( $reset = 'no' ) {

		global $is_apache;

		// Protect against directory browsing by including an index.html file
		$index = $this->path . '/index.html';

		if ( ( 'reset' === $reset ) && file_exists( $index ) ) {
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
	 * @return void
	 */
	public function move_old_backups( $from ) {

		if ( ! is_readable( $from ) ) {
			return;
		}

		if ( ! wp_is_writable( $this->get_path() ) ) {
			return;
		}

		// Move any existing backups
		if ( $handle = opendir( $from ) ) {

			// Loop through the backup directory
			while ( false !== ( $file = readdir( $handle ) ) ) {

				// Find all zips
				if ( 'zip' === pathinfo( $file, PATHINFO_EXTENSION ) ) {

					// Try to move them
					if ( ! @rename( trailingslashit( $from ) . $file, trailingslashit( $this->get_path() ) . $file ) ) {


						// If we can't move them then try to copy them
						copy( trailingslashit( $from ) . $file, trailingslashit( $this->get_path() ) . $file );

					}

				}
			}

			closedir( $handle );

		}

		// Delete the old directory if it's inside WP_CONTENT_DIR
		if ( false !== strpos( $from, WP_CONTENT_DIR ) && $from !== $this->get_path() ) {
			hmbkp_rmdirtree( $from );
		}

	}

	/**
	 * Clean any temporary / incomplete backups from the backups directory
	 */
	public function cleanup() {

		// Don't cleanup a custom path, who knows what other stuff is there
		if ( $this->get_path() === $this->get_custom_path() ) {
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
