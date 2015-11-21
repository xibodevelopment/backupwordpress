<?php

namespace HM\BackUpWordPress;

/**
 * The Backups Director takes an array of backup_engines and
 * iterates through them until it finds one which works.
 */
class Backup_Director {

	/**
	 * The successful backup_engine
	 * @var Backup_Engine|false
	 */
	private $backup_engine = false;

	/**
	 * The array of Backup_Engine's passed in
	 * @var array
	 */
	private $backup_engines = array();

	/**
	 * The filename for the resulting backup
	 * @var string
	 */
	private $backup_filename = '';

	/**
	 * The list of exclude_rules
	 * @var string
	 */
	private $excludes = '';

	/**
	 * A Backup_Director must be passed an array of Backup_Engine's when it
	 * is instantiated
	 * @param array $backup_engines [description]
	 */
	public function __construct( Array $backup_engines ) {
		$this->backup_engines = $backup_engines;
	}

	/**
	 * Set the filename of the backup
	 * @param string $filename
	 */
	public function set_backup_filename( $filename ) {
		$this->backup_filename = $filename;
	}

	/**
	 * Set the exclude rules
	 *
	 * @see Excludes::set_excludes
	 * @param array|string $excludes
	 */
	public function set_excludes( $excludes ) {
		$this->excludes = $excludes;
	}

	/**
	 * Perform the backup by iterating through each Backup_Engine in turn until
	 * we find one which works. If a backup filename or any excludes have been
	 * set then those are passed to each Backup_Engine.
	 */
	public function backup() {

		foreach ( $this->backup_engines as $backup_engine ) {

			$backup_engine = new $backup_engine;

			if ( $this->backup_filename ) {
				$backup_engine->set_backup_filename( $this->backup_filename );
			}

			if ( $this->excludes && is_a( $backup_engine, 'File_Backup_Engine' ) ) {
				$backup_engine->set_excludes( $this->excludes );
			}

			if ( $backup_engine->backup() ) {
				$this->backup_engine = $backup_engine;
				break;
			}

		}

	}

	/**
	 * Return backup filepath, if this is called before a backup has run
	 * then it returns an empty string.
	 * @return string The backup filepath.
	 */
	public function get_backup_filepath() {

		if ( $this->backup_engine ) {
			return $this->backup_engine->get_backup_filepath();
		}

		return '';

	}

	/**
	 * Returns false if a backup hasn't yet run and the name of the
	 * Backup_Engine class if a backup completed.
	 * @return false|string The Backup_Engine classname
	 */
	public function selected_backup_engine() {

		if ( ! $this->backup_engine ) {
			return false;
		}

		return get_class( $this->backup_engine );

	}

}
