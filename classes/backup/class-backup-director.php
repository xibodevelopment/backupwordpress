<?php

namespace HM\BackUpWordPress;

class Backup_Director {

	private $backup_engine = false;
	private $backup_engines = array();

	public function __construct( Backup_Engine $backup_engine ) {
		$this->backup_engines = func_get_args();
	}

	public function selected_backup_engine() {

		if ( ! $this->backup_engine ) {
			return false;
		}

		return get_class( $this->backup_engine );

	}

	public function backup() {

		foreach ( $this->backup_engines as $backup_engine ) {

			if ( $backup_engine->backup() ) {

				$this->backup_engine = $backup_engine;

				break;

			}

		}

	}

}
