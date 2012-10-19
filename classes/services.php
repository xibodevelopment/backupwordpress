<?php

/**
 * An abstract service class, individual services should
 * extend this class
 */
abstract class HMBKP_Service {

	/**
	 * The instance HMBKP_Backup_Schedule that this service is
	 * is currently working with
	 */
	protected $schedule;

	/**
	 * The form to output as part of the schedule settings
	 *
	 * If you don't want a whole form return ''; here and use @field instead
	 */
	abstract protected function form();

	/**
	 * The field to output as part of the schedule settings
	 *
	 * If you don't want a field return ''; here and use @field instead
	 */
	abstract protected function field();

	/**
	 * Validate and sanitize data before it's saved.
	 *
	 * @param  array &$new_data An array or data from $_GET, passed by reference so it can be modified,
	 * @param  array $old_data  The old data thats going to be overwritten
	 * @return array $error     Array of validation errors e.g. return array( 'email' => 'not valid' );
	 */
	abstract protected function update( &$new_data, $old_data );

	/**
	 * The string to be output as part of the schedule sentence
	 *
	 * @return string
	 */
	abstract protected function display();

	/**
	 * Receives actions from the backup
	 *
	 * This is where the service should do it's thing
	 *
	 * @see  HM_Backup::do_action for a list of the actions
	 */
	abstract protected function action( $action );

	/**
	 * Utility for getting a formated html input name attribute
	 *
	 * @param  string $name The name of the field
	 * @return string       The formated name
	 */
	protected function get_field_name( $name ) {
		return esc_attr( get_class( $this ) . '[' . $name . ']' );
	}

	/**
	 * Get the value of a field
	 * @param  string $name The name of the field
	 * @return string       The field value
	 */
	protected function get_field_value( $name, $esc = 'esc_attr' ) {

		if ( $this->schedule->get_service_options( get_class( $this ), $name ) )
			return $esc( $this->schedule->get_service_options( get_class( $this ), $name ) );

		return '';

	}

	/**
	 * Save the settings for this service
	 *
	 * @return null|array returns null on success, array of errors on failure
	 */
	public function save() {

		$classname = get_class( $this );

		$old_data = $this->schedule->get_service_options( $classname );

		$new_data = isset( $_GET[$classname] ) ? $_GET[$classname] : $old_data;

		$errors = $this->update( $new_data, $old_data );

		if ( $errors && $errors = array_flip( $errors ) ) {

			foreach( $errors as $error => &$field )
				$field = get_class( $this ) . '[' . $field . ']';

			return array_flip( $errors );

		}

		$this->schedule->set_service_options( $classname, $new_data );

		return array();

	}

	/**
	 * Set the current schedule object
	 *
	 * @param HMBKP_Scheduled_Backup $schedule An instantiated schedule object
	 */
	public function set_schedule( HMBKP_Scheduled_Backup $schedule ) {
		$this->schedule = $schedule;
	}

}

/**
 * A singleton to handle the registering, unregistering
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
	 * Get the array of registered services
	 *
	 * @access public
	 */
    public function get_services( HMBKP_Scheduled_Backup $schedule = null ) {

    	if ( is_null( $schedule ) )
    		return self::instance()->services;

   		self::instance()->schedule = $schedule;

    	return array_map( array( self::instance(), 'instantiate' ), self::instance()->services );

    }

	/**
	 * Register a new service
	 *
	 * @access public
	 */
    public function register( $filepath, $classname ) {

    	if ( ! file_exists( $filepath ) )
    		throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a valid filepath' );

		self::instance()->services[$filepath] = $classname;

    }

	/**
	 * Unregister an existing service
	 *
	 * @access public
	 */
    public function unregister( $filepath ) {

    	if ( ! isset( self::instance()->services[$filepath] ) )
    		throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a registered service' );

    	unset( self::instance()->services[$filepath] );

    }

	/**
	 * Instantiate the individual service classes
	 *
	 * @access private
	 * @param string $class
	 * @return array An array of instantiated classes
	 */
	private function instantiate( $class ) {

		if ( ! class_exists( $class ) )
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a valid class' );

		$$class = new $class;

		if ( self::instance()->schedule )
			$$class->set_schedule( self::instance()->schedule );

		return $$class;

	}

}