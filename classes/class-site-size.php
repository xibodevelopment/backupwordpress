<?php

namespace HM\BackUpWordPress;

use HM\Backdrop\Task;
use Symfony\Component\Finder\Finder;

/**
 * Site Size class
 *
 * Use to calculate the total or partial size of the site's database and files.
 */
class Site_Size {

	private $size = 0;
	private $type = '';
	private $excludes = array();

	/**
	 * Constructor
	 *
	 * Set up some initial conditions including whether we want to calculate the
	 * size of the database, files or both and whether to exclude any files from
	 * the file size calculation.
	 *
	 * @param string $type     Whether to calculate the size of the database, files
	 *                         or both. Should be one of 'file', 'database' or 'complete'
	 * @param array  $excludes An array of exclude rules
	 */
	public function __construct( $type = 'complete', Excludes $excludes = null ) {
		$this->type = $type;
		$this->excludes = $excludes;
	}

	/**
	 * Calculate the size total size of the database + files.
	 *
	 * Doesn't account for any compression that would be gained by zipping.
	 *
	 * @return string
	 */
	public function get_site_size() {

		if ( $this->size ) {
			return $this->size;
		}

		$size = 0;

		// Include database size except for file only schedule.
		if ( 'file' !== $this->type ) {

			$size = (int) get_transient( 'hmbkp_database_size' );

			if ( ! $size ) {

				global $wpdb;

				$tables = $wpdb->get_results( 'SHOW TABLE STATUS FROM `' . DB_NAME . '`', ARRAY_A );

				foreach ( $tables as $table ) {
					$size += (float) $table['Data_length'];
				}

				set_transient( 'hmbkp_database_size', $size, WEEK_IN_SECONDS );
			}
		}

		// Include total size of dirs/files except for database only schedule.
		if ( 'database' !== $this->type ) {

			$root = new \SplFileInfo( Path::get_root() );
			$size += $this->filesize( $root );

		}

		$this->size = $size;

		return $size;

	}

	/**
	 * Get the site size formatted
	 *
	 * @see size_format
	 *
	 * @return string
	 */
	public function get_formatted_site_size() {
		return size_format( $this->get_site_size() );
	}

	/**
	 * Whether the total filesize is being calculated
	 *
	 * @return bool
	 */
	public static function is_site_size_being_calculated() {
		return false !== get_transient( 'hmbkp_directory_filesizes_running' );
	}

	/**
	 * Whether the total filesize is cached
	 *
	 * @return bool
	 */
	public function is_site_size_cached() {
		return (bool) $this->get_cached_filesizes();
	}

	/**
	 * Recursively scans a directory to calculate the total filesize
	 *
	 * Locks should be set by the caller with `set_transient( 'hmbkp_directory_filesizes_running', true, HOUR_IN_SECONDS );`
	 *
	 * @return array $directory_sizes    An array of directory paths => filesize sum of all files in directory
	 */
	public function recursive_filesize_scanner() {

		/**
		 * Raise the `memory_limit` and `max_execution time`
		 *
		 * Respects the WP_MAX_MEMORY_LIMIT Constant and the `admin_memory_limit`
		 * filter.
		 */
		@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
		@set_time_limit( 0 );

		// Use the cached array directory sizes if available
		$directory_sizes = $this->get_cached_filesizes();

		// If we do have it in cache then let's use it and also clear the lock
		if ( is_array( $directory_sizes ) ) {
			delete_transient( 'hmbkp_directory_filesizes_running' );
			return $directory_sizes;
		}

		// If we don't have it cached then we'll need to re-calculate
		$finder = new Finder();
		$finder->followLinks();
		$finder->ignoreDotFiles( false );
		$finder->ignoreUnreadableDirs( true );

		$files = $finder->in( Path::get_root() );

		foreach ( $files as $file ) {

			if ( $file->isReadable() ) {
				$directory_sizes[ wp_normalize_path( $file->getRealpath() ) ] = $file->getSize();
			} else {
				$directory_sizes[ wp_normalize_path( $file->getRealpath() ) ] = 0;
			}
		}

		file_put_contents( PATH::get_path() . '/.files', gzcompress( json_encode( $directory_sizes ) ) );

		// Remove the lock
		delete_transient( 'hmbkp_directory_filesizes_running' );

		return $directory_sizes;

	}

	/**
	 * Get the total filesize for a given file or directory. Aware of exclusions.
	 *
	 * If $file is a file then return the result of `filesize()` or 0 if it's excluded.
	 * If $file is a directory then recursively calculate the size without
	 * the size of excluded files/directories.
	 *
	 * @param \SplFileInfo   $file The file or directory you want to know the size of.
	 *
	 * @return int           The total filesize of the file or directory without
	 *                       the size of excluded files/directories.
	 */
	public function filesize( \SplFileInfo $file ) {

		// Skip missing or unreadable files.
		if ( ! file_exists( $file->getPathname() ) || ! $file->getRealpath() || ! $file->isReadable() ) {
			return 0;
		}

		// If it's a file then return its filesize or 0 if it's excluded.
		if ( $file->isFile() ) {

			if ( $this->excludes && $this->excludes->is_file_excluded( $file ) ) {
				return 0;
			} else {
				return $file->getSize();
			}
		}

		// If it's a directory then pull it from the cached filesize array.
		if ( $file->isDir() ) {
			return $this->directory_filesize( $file );
		}
	}

	public function directory_filesize( \SplFileInfo $file ) {

		// For performance reasons we cache the root.
		if ( $file->getRealPath() === PATH::get_root() && $this->excludes ) {

			$directory_sizes = get_transient( 'hmbkp_root_size' );

			if ( $directory_sizes ) {
				return (int) $directory_sizes;
			}
		}

		// If we haven't calculated the site size yet then kick it off in a thread.
		$directory_sizes = $this->get_cached_filesizes();

		if ( ! is_array( $directory_sizes ) ) {
			$this->rebuild_directory_filesizes();

			// Intentionally return null so the caller can tell that the size is being calculated.
			return null;
		}

		/*
		 * Ensure we only include files in the current path, the filepaths are stored in keys
		 * so we need to flip for use with preg_grep.
		 */
		$directory_sizes = array_flip( preg_grep( '(' . wp_normalize_path( $file->getRealPath() ) . ')', array_flip( $directory_sizes ) ) );

		if ( $this->excludes ) {

			$excludes = implode( '|', $this->excludes->get_excludes_for_regex() );

			if ( $excludes ) {
				// Use PREG_GREP_INVERT to remove any filepaths which match an exclude rule
				$directory_sizes = array_flip( preg_grep( '(' . $excludes . ')', array_flip( $directory_sizes ), PREG_GREP_INVERT ) );
			}
		}

		$directory_sizes = absint( array_sum( $directory_sizes ) );

		// For performance reasons we cache the root.
		if ( $file->getRealPath() === PATH::get_root() && $this->excludes ) {
			set_transient( 'hmbkp_root_size', $directory_sizes, DAY_IN_SECONDS );
		}

		// Directory size is now just a sum of all files across all sub directories.
		return (int) $directory_sizes;

	}

	public function rebuild_directory_filesizes() {

		if ( $this->is_site_size_being_calculated() ) {
			return false;
		}

		// Mark the filesize as being calculated
		set_transient( 'hmbkp_directory_filesizes_running', true, HOUR_IN_SECONDS );

		// Schedule a Backdrop task to trigger a recalculation
		$task = new Task( array( $this, 'recursive_filesize_scanner' ) );
		$task->schedule();

	}

	public function get_cached_filesizes( $max_age = WEEK_IN_SECONDS ) {

		$cache = PATH::get_path() . '/.files';
		$files = false;

		if ( file_exists( $cache ) ) {

			// If the file is old then regenerate it
			if ( ( time() - filemtime( $cache ) ) <= $max_age ) {
				$files = json_decode( gzuncompress( file_get_contents( $cache ) ), 'ARRAY_A' );
			}
		}

		return $files;

	}
}
