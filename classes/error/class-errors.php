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
				'PCL_ZIP_ERR_MISSING_FILE',
				'mysqldump: Got errno 122 on write',
				'Errcode: 28',
				'mysqldump: Got errno 122',
			),
			'insufficient_memory' => array(
				'Fatal error: Allowed memory size of',
				'Fatal error: Out of memory',
			),
			'gateway_timeouts' => array(
				'Operation timed out',
				'504 Gateway Time-out',
				'Connection timed out',
				'504 Gateway Timeout',
				'502 Bad Gateway',
				'Fetching of original content failed with the following error: Proxy Publisher Failure - TIMEOUT.',
				'Read Timeout',
				'Network Error (tcp_error)',
				'524: A timeout occurred',
			),
			'php_timeouts' => array(
				'PHP Fatal error:  Maximum execution time of 60 seconds exceeded'
			),
			'internal_server_errors' => array(
				'500 Internal Server Error',
				'Request Timeout',
			),
			'server_timeouts' => array(
				'Error 503 Service Unavailable',
				'Service Unavailable',
				'Guru Meditation:',
				'XID: 1187361899',
			),
			'404_errors' => array(
				'Nothing found for',
				'Page not found',
			),
			'safe_mode_issues' => array(
				'SAFE MODE Restriction in effect'
			)
		),
	);

	protected $nice_error_messages = array();

	/**
	 * Constructor should not be publicly accessible.
	 */
	private function __construct() {

		$this->nice_error_messages = array(
			'insufficient_disk_space' => __( 'There was insufficient free disk space to complete the backup operation' , 'hmbkp' ),
			'insufficient_memory' => __( 'There was insufficient allocated memory to complete the backup operation', 'hmbkp' ),
			'gateway_timeouts' => __( 'The remote server took too long to respond', 'hmbkp' ),
			'php_timeouts' => __( 'The operation took too long to complete and exceeded the limit set by the server configuration', 'hmbkp' ),
			'internal_server_errors' => __( 'The operation timed out', 'hmbkp' ),
			'server_timeouts' => __( 'The operation timed out', 'hmbkp' ),
			'404_errors' => __( 'There is a server misconfiguration', 'hmbkp' ),
			'safe_mode_issues' => __( 'Safe mode is on', 'hmbkp' ),

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
			foreach ( $types as $key => $error_type ) {
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
