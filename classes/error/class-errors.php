<?php

/**
 * Class HMBKP_Known_Errors
 */
class HMBKP_Known_Errors {

	/**
	 * The single instance of this class.
	 *
	 * @var HMBKP_Known_Errors
	 */
	private static $_instance;

	/**
	 * Stores all possible error messages.
	 * possible_error[error_context][error_category][message]
	 * @var array
	 */
	protected $possible_errors = array(
		'backup_errors' => array(
			'insufficient_disk_space' => array(
				'zip I/O error: Disk quota exceeded',
				'zip error: Output file write failure',
				'PCL_ZIP_ERR_MISSING_FILE'
			),
		),
	);

	protected $nice_error_messages = array();

	/**
	 * Constructor should not be publicly accessible.
	 */
	private function __construct() {

		$this->nice_error_messages = array(
			'insufficient_disk_space' => __( 'Your backups folder location has insufficient free disk space' , 'hmbkp' ),
		);
	}

	/**
	 * Provides access to the singleton instance.
	 *
	 * @return HMBKP_Known_Errors
	 */
	public static function get_instance() {

		if ( ! ( self::$_instance instanceof HMBKP_Known_Errors ) ) {
			self::$_instance = new HMBKP_Known_Errors();
			return self::$_instance;
		}
	}

	/**
	 * Fetch possible error messages.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function get_possible_errors( $type = 'all' ) {

		if ( 'all' === $type ) {
			return $this->possible_errors;
		}

		return $this->possible_errors[ $type ];
	}

	/**
	 * Return the matching error message.
	 *
	 * @param string $message
	 */
	public function match( $error_message ) {

		foreach ( $this->possible_errors as $category => $types ) {
			foreach( $types as $key => $error_type ) {
				if ( $match = $this->array_find( $error_type, $error_message ) ) {
					return $this->get_nice_error_message( $key );
				} else {
					return sprintf( __( 'Unhandled error: %s', 'hmbkp' ), $error_message );
				}
			}

		}

	}

	protected function get_nice_error_message( $key ) {
		return $this->nice_error_messages[ $key ];
	}

	/**
	 * Find a partial match.
	 *
	 * @param array $haystack
	 * @param string $needle
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
