<?php

namespace HM\BackUpWordPress;

class Backup_Director {

	private $backup_engine = false;
	private $backup_engines = array();
	private $backup_filename = '';
	private $excludes = '';

	public function __construct( Array $backup_engines ) {

		// Start the engines
		$this->backup_engines = $backup_engines;

	}

	public function selected_backup_engine() {

		if ( ! $this->backup_engine ) {
			return false;
		}

		return get_class( $this->backup_engine );

	}

	public function backup() {

		foreach ( $this->backup_engines as $backup_engine ) {

			$backup_engine = new $backup_engine;

			if ( $this->backup_filename ) {
				$backup_engine->set_backup_filename( $this->backup_filename );
			}

			if ( $this->excludes ) {
				$backup_engine->set_excludes( $this->excludes );
			}

			if ( $backup_engine->backup() ) {
				$this->backup_engine = $backup_engine;
				break;
			}

		}

	}

	public function set_backup_filename( $filename ) {
		$this->backup_filename = $filename;
	}

	public function get_backup_filepath() {

		if ( $this->backup_engine ) {
			return $this->backup_engine->get_backup_filepath();
		}

		return '';

	}

	public function set_excludes( $excludes ) {
		$this->excludes = $excludes;
	}

}
