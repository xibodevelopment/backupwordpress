<?php


/**
 * An abstract service class, individual services should
 * extend this class
 */
abstract class HMBKP_Service {}

/**
 * A singleton to handle the registering, de-registering
 * and storage of services
 */
class HMBKP_Services {

	/**
	 * Store the current instance
	 *
	 * @access private
	 * @var object HMBKP_Services
	 * @static
	 */
	private static $instance;

	/**
	 * The array of services
	 *
	 * Should be of the format array( __FILE__ => __CLASS__ );
	 *
	 * @access private
	 * @var  array
	 * @static
	 */
	private $services = array();

	/**
	 * The current schedule object
	 *
	 * @access private
	 * @var object HMBKP_Scheduled_Backup
	 */
	private $schedule;

	/**
	 * Get the current instance
	 *
	 * @access public
	 * @static
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) )
			self::$instance = new HMBKP_Services;

		return self::$instance;

	}

	/**
	 * Register a new service
	 *
	 * @param $filepath
	 * @param $classname
	 * @return bool|WP_Error
	 */
	public static function register( $filepath, $classname ) {

		_deprecated_function( __METHOD__, '3.1.2' );
	}

	/**
	 * Instantiate the individual service classes
	 *
	 * @param string $classname
	 *
	 * @return array An array of instantiated classes
	 */
	private static function instantiate( $classname ) {

		if ( ! class_exists( $classname ) )
			return new \WP_Error( 'hmbkp_invalid_type_error', sprintf( __( 'Argument 1 for %s must be a valid class', 'backupwordpress' ) ), __METHOD__ );

		/**
		 * @var HMBKP_Service
		 */
		$class = new $classname( self::instance()->schedule );

		return $class;

	}

}
