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

		// Attempt to create the zip file.
		if ( $zip->open( $this->get_backup_filepath(), \ZIPARCHIVE::CREATE ) ) {

			foreach ( $this->get_files() as $file ) {

				// Create an empty directory for each directory in the filesystem
				if ( $file->isDir() ) {
					$zip->addEmptyDir( $file->getRelativePathname() );
				}

				// Archive the file with a relative path
				elseif ( $file->isFile() ) {
					$zip->addFile( $file->getPathname(), $file->getRelativePathname() );
				}
			}

			// Track any internal warnings
			if ( $zip->status ) {
				$this->warning( __CLASS__, $zip->status );
			}

			if ( $zip->statusSys ) {
				$this->warning( __CLASS__, $zip->statusSys );
			}

			$zip->close();

		}

		return $this->verify_backup();

	}

}
