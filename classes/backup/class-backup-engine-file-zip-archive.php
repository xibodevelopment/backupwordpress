<?php

namespace HM\BackUpWordPress;

/**
 * Perform a file backup using the native PHP ZipArchive extension
 */
class Zip_Archive_File_Backup_Engine extends File_Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function backup() {

		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}

		$zip = new \ZipArchive();

		/**
		 * Ensure the zip is closed on shutdown if it hasn't been closed already
		 *
		 * ZipArchive does automatically cleanup on unexpected shutdown (a fatal error
		 * for example), but we manually cleanup here as we want the opportunity to run
		 * other user shutdown functions afterwards.
		 */
		add_action( 'shutdown', function() use ( &$zip ) {
			try {
				if ( $zip->filename ) {
					$zip->close();
				}
			} catch ( \Exception $e ) {}
		}, 1 );

		// Attempt to create the zip file.
		if ( $zip->open( $this->get_backup_filepath(), \ZIPARCHIVE::CREATE ) ) {

			foreach ( $this->get_files() as $file ) {

				// Create an empty directory for each directory in the filesystem
				if ( $file->isDir() ) {
					$zip->addEmptyDir( $file->getRelativePathname() );
				} elseif ( $file->isFile() ) { // Archive the file with a relative path
					$zip->addFile( $file->getPathname(), $file->getRelativePathname() );
				}
			}

			// Track any internal warnings
			if ( $zip->status ) {
				$this->warning( __CLASS__, $zip->status );
			}

			// @codingStandardsIgnoreStart
			if ( $zip->statusSys ) {
				$this->warning( __CLASS__, $zip->statusSys );
			}
			// @codingStandardsIgnoreEnd

			$zip->close();

		}

		return $this->verify_backup();

	}
}
