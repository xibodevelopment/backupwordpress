<?php

namespace HM\BackUpWordPress;

class Backup_Director {

	private $backup_engine = false;
	private $backup_engines = array();

	public function __construct( $backup_engines ) {

		// Start the engines
		$this->backup_engines = array_map( function ( $backup_engine ) {
			return new $backup_engine();
		}, $backup_engines );

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

	public function __call( $method, $arguments ) {
		foreach ( $this->backup_engines as $backup_engine ) {
			if ( is_callable( $this->backup_engine->$method ) ){
				call_user_method_array( $method, $this->backup_engine, $arguments );
			}
		}
	}

}
