<?php

namespace HM\BackUpWordPress;

use Symfony\Component\Finder\Finder;

/**
 * Site Size class
 *
 * Use to calculate the total or partial size of the sites database and files.
 */
class Site_Size {

	private $size = 0;

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
	public function __construct( $type = 'complete', $excludes = array() ) {
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

			global $wpdb;
			$tables = $wpdb->get_results( 'SHOW TABLE STATUS FROM `' . DB_NAME . '`', ARRAY_A );

			foreach ( $tables as $table ) {
				$size += (float) $table['Data_length'];
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
	public static function is_site_size_cached() {
		return false !== get_transient( 'hmbkp_directory_filesizes' );
	}

	/**
	 * Return the contents of `$directory` as a single depth list ordered by total filesize.
	 *
	 * Will schedule background threads to recursively calculate the filesize of subdirectories.
	 * The total filesize of each directory and subdirectory is cached in a transient for 1 week.
	 *
	 * @param string $directory The directory to list
	 *
	 * @return array            returns an array of files ordered by filesize
	 */
	public function list_directory_by_total_filesize( $directory ) {

		$files = $files_with_no_size = $empty_files = $files_with_size = $unreadable_files = array();

		if ( ! is_dir( $directory ) ) {
			return $files;
		}

		$finder = new Finder();
		$finder->followLinks();
		$finder->ignoreDotFiles( false );
		$finder->ignoreUnreadableDirs();
		$finder->depth( '== 0' );

		$files = $finder->in( $directory );

		foreach ( $files as $entry ) {

			// Get the total filesize for each file and directory
			$filesize = $this->filesize( $entry );

			if ( $filesize ) {

				// If there is already a file with exactly the same filesize then let's keep increasing the filesize of this one until we don't have a clash
				while ( array_key_exists( $filesize, $files_with_size ) ) {
					$filesize ++;
				}

				$files_with_size[ $filesize ] = $entry;

			} elseif ( 0 === $filesize ) {

				$empty_files[] = $entry;

			} else {

				$files_with_no_size[] = $entry;

			}

		}

		// Sort files by filesize, largest first
		krsort( $files_with_size );

		// Add 0 byte files / directories to the bottom
		$files = $files_with_size + array_merge( $empty_files, $unreadable_files );

		// Add directories that are still calculating to the top
		if ( $files_with_no_size ) {

			// We have to loop as merging or concatenating the array would re-flow the keys which we don't want because the filesize is stored in the key
			foreach ( $files_with_no_size as $entry ) {
				array_unshift( $files, $entry );
			}
		}

		return $files;

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
		$directory_sizes = get_transient( 'hmbkp_directory_filesizes' );

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

		set_transient( 'hmbkp_directory_filesizes', $directory_sizes, DAY_IN_SECONDS );

		// Remove the lock
		delete_transient( 'hmbkp_directory_filesizes_running' );

		return $directory_sizes;

	}

	/**
	 * Get the total filesize for a given file or directory
	 *
	 * If $file is a file then just return the result of `filesize()`.
	 * If $file is a directory then schedule a recursive filesize scan.
	 *
	 * @param \SplFileInfo   $file The file or directory you want to know the size of
	 *
	 * @return int           The total of the file or directory
	 */
	public function filesize( \SplFileInfo $file ) {

		// Skip missing or unreadable files
		if ( ! file_exists( $file->getPathname() ) || ! $file->getRealpath() || ! $file->isReadable() ) {
			return 0;
		}

		// If it's a file then just pass back the filesize
		if ( $file->isFile() && $file->isReadable() ) {
			return $file->getSize();
		}

		// If it's a directory then pull it from the cached filesize array
		if ( $file->isDir() ) {

			// If we haven't calculated the site size yet then kick it off in a thread
			$directory_sizes = get_transient( 'hmbkp_directory_filesizes' );

			if ( ! is_array( $directory_sizes ) ) {

				if ( ! $this->is_site_size_being_calculated() ) {

					// Mark the filesize as being calculated
					set_transient( 'hmbkp_directory_filesizes_running', true, HOUR_IN_SECONDS );

					// Schedule a Backdrop task to trigger a recalculation
					$task = new \HM\Backdrop\Task( array( $this, 'recursive_filesize_scanner' ) );
					$task->schedule();

				}

				// If the filesize is being calculated then return null
				return null;

			}

			$directory_sizes = array_flip( preg_grep( '(' . $file->getRealPath() . ')', array_flip( $directory_sizes ) ) );

			if ( $this->excludes ) {

				$excludes = new Excludes;
				$excludes->set_excludes( $this->excludes );
				$excludes = implode( '|', $excludes->get_excludes_for_regex() );

				$directory_sizes = array_flip( preg_grep( '(' . $excludes . ')', array_flip( $directory_sizes ), PREG_GREP_INVERT ) );

			}

			// Directory size is now just a sum of all files across all sub directories
			return absint( array_sum( $directory_sizes ) );

		}

	}

}
