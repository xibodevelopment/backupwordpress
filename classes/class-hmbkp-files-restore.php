<?php

class HMBKP_Files_Restore {

	protected $root_path;

	public function __construct( $root_path, $dump_file_archive ) {

		$this->root_path = $root_path;

	}

	public function backup_install() {

		$backup_path = $this->root_path . '/bwp_' . time();

		if ( ! is_dir( $backup_path ) ) {
			mkdir( $backup_path );
		}

		foreach (
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($this->root_path, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD ) as $item
		) {

			if( $item->isDir() && ( strpos( $item->getPath(), $backup_path ) !== false ) )
				continue;
			if( is_link( $item ) ) {
				symlink( readlink( $item ), $backup_path . DIRECTORY_SEPARATOR . $iterator->getSubPathName() );
			} elseif ( $item->isDir() ) {
				mkdir( $backup_path . DIRECTORY_SEPARATOR . $iterator->getSubPathName() );
			} else {
				copy( $item->getRealPath(), $backup_path . DIRECTORY_SEPARATOR . $iterator->getSubPathName() . $item->getFilename );
			}
		}

	}

	public function copy_dir($src, $dst)
	{

		if (is_link($src)) {
			symlink(readlink($src), $dst);
		} elseif (is_dir($src)) {
			mkdir($dst);
			foreach (scandir($src) as $file) {
				if ($file != '.' && $file != '..') {
					if( $dst !== $src )
						continue;
					copy_dir("$src/$file", "$dst/$file");
				}
			}
		} elseif (is_file($src)) {
			copy($src, $dst);
		} else {
			echo "WARNING: Cannot copy $src (unknown file type)\n";
		}
	}

	public function restore_files() {

		$backup_path = $this->root_path . '/bwp_' . time();

		//$this->backup_install();
		$this->copy_dir( $this->root_path, $backup_path );

	}

}
