<?php

namespace HM\BackUpWordPress;

class Zip_Archive_File_Backup_Engine extends File_Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function backup() {

		if ( ! class_exists( 'ZipArchive' ) ) {
			return;
		}

		$zip = new \ZipArchive();

		if ( $zip->open( $this->get_backup_filepath(), \ZIPARCHIVE::CREATE ) ) {

			$files_added = 0;

			foreach ( $this->get_files() as $file ) {

				if ( $file->isDir() ) {
					$zip->addEmptyDir( $file->getRelativePathname() );

				}

				elseif ( $file->isFile() ) {
					$zip->addFile( $file->getPathname(), $file->getRelativePathname() );

				}

				if ( ++$files_added % 500 === 0 ) {
					if ( ! $zip->close() || ! $zip->open( $this->get_backup_filepath(), \ZIPARCHIVE::CREATE ) ) {
						return;
					}
				}
			}

		}

		//if ( $zip->status ) {
		//	$this->warning( __CLASS__, $zip->status );
		//}
//
		//if ( $zip->statusSys ) {
		//	$this->warning( __CLASS__, $zip->statusSys );
		//}

		$zip->close();

		return $this->verify_backup();

	}

}