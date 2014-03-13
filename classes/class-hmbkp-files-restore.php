<?php

class HMBKP_Files_Restore {

	protected $root_path;

	protected $restore_from_path;

	public function __construct( $restore_from_path ) {

		$this->root_path = get_home_path();

		$this->restore_from_path = $restore_from_path;
	}

	public function restore_files() {

		$this->copy_old_files();

		$this->copy_new_files();

	}

	public function copy_old_files() {

		$backup_folder =  'bwp_' . time();
		$backup_path = $this->root_path . $backup_folder;

		if ( ! is_dir( $backup_path ) )
			if ( ! mkdir( $backup_path ) )
				return;

		// TODO: alternative method to copy if copy_dir not available
		WP_Filesystem();

		copy_dir( $this->root_path, $backup_path, array( $backup_folder, '.git' ) );

	}

	public function copy_new_files() {

		WP_Filesystem();

		copy_dir( $this->restore_from_path, $this->root_path );

	}

}
