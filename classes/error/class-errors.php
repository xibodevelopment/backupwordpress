<?php

class HMBKP_Known_Errors {

	private static $_instance;

	protected $possible_errors = array(
		'insufficient_disk_space' => array(
			'zip I/O error: Disk quota exceeded',
			'zip error: Output file write failure',
			'PCL_ZIP_ERR_MISSING_FILE'
		),
	);

	public static function get_instance() {

		if ( ! ( self::$_instance instanceof HMBKP_Known_Errors ) ) {
			self::$_instance = new HMBKP_Known_Errors();
			return self::$_instance;
		}
	}

	public function get_all_possible_errors() {
		return $this->possible_errors;
	}

	public function get_possible_errors_of_type( $type ) {

		return $this->possible_errors[ $type ];
	}

	/**
	 * Return the matching error message.
	 *
	 * @param $message
	 */
	public function match( $message ) {

		foreach ( $this->possible_errors as $category -> $errors ) {
			if ( $match = $this->array_find( $errors, $message ) ) {
				return $match;
			} else {
				return $message;
			}
		}

	}

	/**
	 * Find a partial match.
	 *
	 * @param array $haystack
	 * @param       $needle
	 *
	 * @return bool|int|string
	 */
	protected function array_find( array $haystack, $needle ) {

		// Iterate the possible error messages and return it if there is a match.
		foreach ( $haystack as $value ) {
			if ( false !== stripos( $value, $needle ) ) {
				return $value;
			}
			return false;
		}

	}
}
