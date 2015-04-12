<?php

namespace HM\BackUpWordPress;

/**
 * A singleton to handle the registering, de-registering
 * and storage of services
 */
class Services {

	/**
	 * Store the current instance
	 *
	 * @access private
	 * @var object Services
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
	 * @var object Scheduled_Backup
	 */
	private $schedule;

	/**
	 * Get the current instance
	 *
	 * @static
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) )
			self::$instance = new Services;

		return self::$instance;

	}

	/**
	 * Get the array of registered services
	 *
	 * @param Scheduled_Backup $schedule
	 * @return Service[]
	 */
	public static function get_services( Scheduled_Backup $schedule = null ) {

		if ( is_null( $schedule ) ) {
			return self::instance()->services;
		}

		self::instance()->schedule = $schedule;

		return array_map( array( self::instance(), 'instantiate' ), self::instance()->services );

	}

	/**
	 * Register a new service
	 *
	 * @param $filepath
	 * @param $classname
	 * @return bool|WP_Error
	 */
	public static function register( $filepath, $classname ) {
		if ( ! file_exists( $filepath ) ) {
			return new \WP_Error( 'hmbkp_invalid_path_error', sprintf( __( 'Argument 1 for %s must be a valid filepath', 'backupwordpress' ), __METHOD__ ) );
		}

		self::instance()->services[ $filepath ] = $classname;

		return true;
	}

	/**
	 * De-register an existing service
	 * @param string $filepath
	 * @return bool|WP_Error
	 */
	public static function unregister( $filepath ) {

		if ( ! isset( self::instance()->services[ $filepath ] ) ) {
			return new \WP_Error( 'hmbkp_unrecognized_service_error', sprintf( __( 'Argument 1 for %s must be a registered service', 'backupwordpress' ), __METHOD__ ) );
		}

		unset( self::instance()->services[ $filepath ] );

		return true;
	}

	/**
	 * Instantiate the individual service classes
	 *
	 * @param string $classname
	 *
	 * @return array An array of instantiated classes
	 */
	private static function instantiate( $classname ) {

		if ( ! class_exists( $classname ) ) {
			return new \WP_Error( 'hmbkp_invalid_type_error', sprintf( __( 'Argument 1 for %s must be a valid class', 'backupwordpress' ), __METHOD__ ) );
		}

		/**
		 * @var Service
		 */
		$class = new $classname( self::instance()->schedule );

		return $class;

	}

}