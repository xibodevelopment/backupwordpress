<?php

/**
 * A singleton to handle the registering, unregistering
 * and storage of individual requirements
 */
class HMBKP_Requirements {

	/**
	 * Store the current instance
	 *
	 * @access private
	 * @var object HMBKP_Requirements
	 * @static
	 */
    private static $instance;

	/**
	 * The array of requirements
	 *
	 * Should be of the format array( __FILE__ => __CLASS__ );
	 *
	 * @access private
	 * @var  array
	 * @static
	 */
    private $requirements = array();

	/**
	 * Get the current instance
	 *
	 * @access public
	 * @static
	 */
    public static function instance() {

        if ( ! isset( self::$instance ) )
            self::$instance = new HMBKP_Requirements;

        return self::$instance;

    }

	/**
	 * Get the array of registered services
	 *
	 * @access public
	 */
    public function get_requirements() {

    	return array_map( array( self::instance(), 'instantiate' ), self::instance()->requirements );

    }

	/**
	 * Register a new service
	 *
	 * @access public
	 */
    public function register( $class ) {

		if ( ! class_exists( $class ) )
			throw new Exception( 'Argument 1 for ' . __METHOD__ . ' must be a valid class' );

		self::instance()->requirements[] = $class;

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

		return $$class;

	}

}

/**
 * An abstract service class, individual services should
 * extend this class
 */
abstract class HMBKP_Requirement {

	abstract protected function test();

	public function name() {

		return $this->name;

	}

	public function result() {

		if ( is_string( $this->test() ) )
			return $this->test();

		if ( is_bool( $this->test() ) && $this->test() )
			return 'Yes';

		return 'No';

	}

	public function passed() {

		return (bool) $this->test();

	}

}

class HMBKP_Requirement_Server extends HMBKP_Requirement {

	protected function test() {}

}

class HMBKP_Requirement_Zip_Archive extends HMBKP_Requirement {

	var $name = 'ZipArchive';

	protected function test() {

		if ( class_exists( 'ZipArchive' ) )
			return true;

		return false;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Zip_Archive' );

class HMBKP_Requirement_Directory_Iterator_Follow_Symlinks extends HMBKP_Requirement {

	var $name = 'DirectoryIterator';

	protected function test() {

		if ( defined( 'RecursiveDirectoryIterator::FOLLOW_SYMLINKS' ) )
			return true;

		return false;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Directory_Iterator_Follow_Symlinks' );

class HMBKP_Requirement_Zip_Command extends HMBKP_Requirement {

	var $name = 'zip';

	protected function test() {

		$hm_backup = new HM_Backup;

		if ( $hm_backup->get_zip_command_path() )
			return shell_exec( 'whoami' );

		return false;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Zip_Command' );

class HMBKP_Requirement_PHP_User extends HMBKP_Requirement {

	var $name = 'PHP User';

	protected function test() {

		return shell_exec( 'whoami' );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_User' );

class HMBKP_Requirement_PHP_Group extends HMBKP_Requirement {

	var $name = 'PHP Group[s]';

	protected function test() {

		return shell_exec( 'groups' );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Group' );

class HMBKP_Requirement_PHP_Version extends HMBKP_Requirement {

	var $name = 'PHP Version';

	protected function test() {

		return PHP_VERSION;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Version' );

class HMBKP_Requirement_Cron_Array extends HMBKP_Requirement {

	var $name = 'Cron Array';

	protected function test() {

		$cron = get_option( 'cron' );

		if ( ! $cron )
			return false;

		return print_r( $cron, true );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Cron_Array' );

class HMBKP_Requirement_Safe_Mode extends HMBKP_Requirement {

	var $name = 'Safe Mode';

	protected function test() {

		return HM_Backup::is_safe_mode_active();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Safe_Mode' );

class HMBKP_Requirement_Shell_Exec extends HMBKP_Requirement {

	var $name = 'Shell Exec';

	protected function test() {

		return HM_Backup::is_shell_exec_available();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Shell_Exec' );

class HMBKP_Requirement_Memory_Limit extends HMBKP_Requirement {

	var $name = 'Memory Limit';

	protected function test() {

		return @ini_get( 'memory_limit' );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Memory_Limit' );

class HMBKP_Requirement_Backup_Path extends HMBKP_Requirement {

	var $name = 'Backup Path';

	protected function test() {

		return hmbkp_path();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Backup_Path' );