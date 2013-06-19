<?php

/**
 * A singleton to handle the registering, unregistering
 * and storage of errors
 */
class HMBKP_Errors {

	/**
	 * Store the current instance
	 *
	 * @access private
	 * @var object HMBKP_Errors
	 * @static
	 */
    private static $instance;

	/**
	 * The array of errors
	 *
	 * Should be of the format array( __FILE__ => __CLASS__ );
	 *
	 * @access private
	 * @var  array
	 * @static
	 */
    private $errors = array();

    /**
     * The current schedule object
     *
     * @access private
     * @var object HMBKP_Scheduled_Backup
     */
    private $error;

	/**
	 * Get the current instance
	 *
	 * @access public
	 * @static
	 */
    public static function instance() {

        if ( ! isset( self::$instance ) )
            self::$instance = new HMBKP_Errors;

        return self::$instance;

    }

	/**
	 * Get the array of registered errors
	 *
	 * @access public
	 */
    public static function get_errors( HMBKP_Error $error = null ) {

    	if ( is_null( $error ) )
    		return self::instance()->errors;

   		self::instance()->error = $error;

    	return array_map( array( self::instance(), 'instantiate' ), self::instance()->errors );

    }

	/**
	 * Register a new errors
	 *
	 * @access public
	 */
    public static function register( $handle, $classname ) {

		self::instance()->errors[$handle] = $classname;

    }

	/**
	 * Unregister an existing errors
	 *
	 * @access public
	 */
    public static function unregister( $handle ) {

    	if ( ! isset( self::instance()->errors[$handle] ) )
    		throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a registered error' );

    	unset( self::instance()->errors[$handle] );

    }

	/**
	 * Instantiate the individual error classes
	 *
	 * @access private
	 * @param string $class
	 * @return array An array of instantiated classes
	 */
	private static function instantiate( $class ) {

		if ( ! class_exists( $class ) )
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a valid class' );

		$$class = new $class;

		return $$class;

	}

}

abstract class HMBKP_Error {
	
	public function __construct( $error ) {
		
		$this->error = $error;
	
		if ( $this->match() )
			return $this->message();

		return $this->get_error();
	
	}

	protected function get_error() {
		return $this->error;
	}

	protected function match() {}

	protected function message() {}

}

class HMBKP_Disk_Quote_Error() {

	public function match() {

		if ( stripos( $this->get_error(), 'disk quota' ) )
			return true;

		return false;

	}

	public function message() {
		return __( 'Your backup could not be completed because you have run out of disk space.', 'hmbkp' );
	}

}