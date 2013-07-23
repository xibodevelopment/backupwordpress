<?php

/**
 * A singleton to handle the registering, unregistering
 * and storage of individual requirements
 */
class HMBKP_Requirements {

	/**
	 * The array of requirements
	 *
	 * Should be of the format array( __FILE__ => __CLASS__ );
	 *
	 * @access private
	 * @var  array
	 * @static
	 */
    private static $requirements = array();


	/**
	 * Get the array of registered services
	 *
	 * @access public
	 */
    public static function get_requirements( $group = false ) {

    	$requirements = $group ? self::$requirements[$group] : self::$requirements;

    	ksort( $requirements );

    	return array_map( array( self, 'instantiate' ), $requirements );

    }

    public static function get_requirement_groups() {

	    return array_keys( self::$requirements );

    }

	/**
	 * Register a new service
	 *
	 * @access public
	 */
    public static function register( $class, $group = 'misc' ) {

		if ( ! class_exists( $class ) )
			return new WP_Error( 'invalid argument', 'Argument 1 for ' . __METHOD__ . ' must be a valid class' );

		self::$requirements[$group][] = $class;

    }

	/**
	 * Instantiate the individual service classes
	 *
	 * @access private
	 * @param string $class
	 * @return array An array of instantiated classes
	 */
	private static function instantiate( $class ) {

		if ( ! class_exists( $class ) )
			return new WP_Error( 'invalid argument', 'Argument 1 for ' . __METHOD__ . ' must be a valid class' );

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

		if ( is_string( $this->test() ) && $this->test() )
			return $this->test();

		if ( is_bool( $this->test() ) && $this->test() )
			return 'Yes';

		return 'No';

	}

	public function passed() {

		return (bool) $this->test();

	}

}

class HMBKP_Requirement_Zip_Archive extends HMBKP_Requirement {

	var $name = 'ZipArchive';

	protected function test() {

		if ( class_exists( 'ZipArchive' ) )
			return true;

		return false;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Zip_Archive', 'PHP' );

class HMBKP_Requirement_Directory_Iterator_Follow_Symlinks extends HMBKP_Requirement {

	var $name = 'DirectoryIterator FOLLOW_SYMLINKS';

	protected function test() {

		if ( defined( 'RecursiveDirectoryIterator::FOLLOW_SYMLINKS' ) )
			return true;

		return false;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Directory_Iterator_Follow_Symlinks', 'PHP' );

class HMBKP_Requirement_Zip_Command extends HMBKP_Requirement {

	var $name = 'zip';

	protected function test() {

		$hm_backup = new HM_Backup;

		return $hm_backup->get_zip_command_path();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Zip_Command' );

class HMBKP_Requirement_Mysqldump_Command extends HMBKP_Requirement {

	var $name = 'mysqldump';

	protected function test() {

		$hm_backup = new HM_Backup;

		return $hm_backup->get_mysqldump_command_path();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Mysqldump_Command' );

class HMBKP_Requirement_PHP_User extends HMBKP_Requirement {

	var $name = 'PHP User';

	protected function test() {

		return shell_exec( 'whoami' );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_User', 'PHP' );

class HMBKP_Requirement_PHP_Group extends HMBKP_Requirement {

	var $name = 'PHP Group[s]';

	protected function test() {

		return shell_exec( 'groups' );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Group', 'PHP' );

class HMBKP_Requirement_PHP_Version extends HMBKP_Requirement {

	var $name = 'PHP Version';

	protected function test() {

		return PHP_VERSION;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Version', 'PHP' );

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
HMBKP_Requirements::register( 'HMBKP_Requirement_Safe_Mode', 'PHP' );

class HMBKP_Requirement_Shell_Exec extends HMBKP_Requirement {

	var $name = 'Shell Exec';

	protected function test() {

		return HM_Backup::is_shell_exec_available();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Shell_Exec', 'PHP' );

class HMBKP_Requirement_Memory_Limit extends HMBKP_Requirement {

	var $name = 'Memory Limit';

	protected function test() {

		return @ini_get( 'memory_limit' );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Memory_Limit', 'PHP' );

class HMBKP_Requirement_Backup_Path extends HMBKP_Requirement {

	var $name = 'Backup Path';

	protected function test() {

		return hmbkp_path();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Backup_Path', 'Site' );

class HMBKP_Requirement_Backup_Path_Permissions extends HMBKP_Requirement {

	var $name = 'Backup Path Permissions';

	protected function test() {

		return substr( sprintf( '%o', fileperms( hmbkp_path() ) ), -4 );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Backup_Path_Permissions', 'Site' );

class HMBKP_Requirement_WP_CONTENT_DIR extends HMBKP_Requirement {

	var $name = 'WP_CONTENT_DIR';

	protected function test() {

		return WP_CONTENT_DIR;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_WP_CONTENT_DIR', 'Site' );

class HMBKP_Requirement_WP_CONTENT_DIR_Permissions extends HMBKP_Requirement {

	var $name = 'WP_CONTENT_DIR Permissions';

	protected function test() {

		return substr( sprintf( '%o', fileperms( WP_CONTENT_DIR ) ), -4 );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_WP_CONTENT_DIR_Permissions', 'Site' );

class HMBKP_Requirement_ABSPATH extends HMBKP_Requirement {

	var $name = 'ABSPATH';

	protected function test() {

		return ABSPATH;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_ABSPATH', 'Site' );

class HMBKP_Requirement_Backup_Root_Path extends HMBKP_Requirement {

	var $name = 'Backup Root Path';

	protected function test() {

		$hm_backup = new HM_Backup();

		return $hm_backup->get_root();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Backup_Root_Path', 'Site' );

class HMBKP_Requirement_Calculated_Size extends HMBKP_Requirement {

	var $name = 'Calculated size of site';

	protected function test() {

		$schedule = new HMBKP_Scheduled_Backup( 'test' );

		return $schedule->get_filesize();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Calculated_Size', 'Site' );

class HMBKP_Requirement_WP_Cron_Test_Response extends HMBKP_Requirement {

	var $name = 'WP Cron Response';

	protected function test() {

		return print_r( get_option( 'hmbkp_wp_cron_test_response' ), true );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_WP_Cron_Test_Response' );

class HMBKP_Requirement_PHP_API extends HMBKP_Requirement {

	var $name = 'PHP Interface';

	protected function test() {

		return php_sapi_name();

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_API', 'Server' );

class HMBKP_Requirement_SERVER extends HMBKP_Requirement {

	var $name = 'PHP SERVER Global';

	protected function test() {

		return print_r( $_SERVER, true );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_SERVER' );

class HMBKP_Requirement_Server_Software extends HMBKP_Requirement {

	var $name = 'Server Sofware';

	protected function test() {

		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) )
			return $_SERVER['SERVER_SOFTWARE'];

		return false;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Server_Software', 'Server' );

class HMBKP_Requirement_Server_OS extends HMBKP_Requirement {

	var $name = 'Server OS';

	protected function test() {

		return PHP_OS;

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_Server_OS', 'Server' );

class HMBKP_Requirement_PHP_Disable_Functions extends HMBKP_Requirement {

	var $name = 'PHP Disabled Functions';

	protected function test() {

		return @ini_get( 'disable_functions' );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Disable_Functions', 'PHP' );

class HMBKP_Requirement_PHP_Open_Basedir extends HMBKP_Requirement {

	var $name = 'PHP <code>open_basedir</code> Restriction';

	protected function test() {

		return @ini_get( 'open_basedir' );

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Open_Basedir', 'PHP' );

/* CONSTANTS */

class HMBKP_Requirement_PHP_Define_HMBKP_PATH extends HMBKP_Requirement {

	var $name = 'HMBKP_PATH';

	protected function test() {

		return defined( 'HMBKP_PATH' ) ? HMBKP_PATH : '';

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_PATH', 'constants' );

class HMBKP_Requirement_PHP_Define_HMBKP_ROOT extends HMBKP_Requirement {

	var $name = 'HMBKP_ROOT';

	protected function test() {

		return defined( 'HMBKP_ROOT' ) ? HMBKP_ROOT : '';

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_ROOT', 'constants' );

class HMBKP_Requirement_PHP_Define_HMBKP_MYSQLDUMP_PATH extends HMBKP_Requirement {

	var $name = 'HMBKP_MYSQLDUMP_PATH';

	protected function test() {

		return defined( 'HMBKP_MYSQLDUMP_PATH' ) ? HMBKP_MYSQLDUMP_PATH : '';

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_MYSQLDUMP_PATH', 'constants' );

class HMBKP_Requirement_PHP_Define_HMBKP_ZIP_PATH extends HMBKP_Requirement {

	var $name = 'HMBKP_ZIP_PATH';

	protected function test() {

		return defined( 'HMBKP_ZIP_PATH' ) ? HMBKP_ZIP_PATH : '';

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_ZIP_PATH', 'constants' );

class HMBKP_Requirement_PHP_Define_HMBKP_CAPABILITY extends HMBKP_Requirement {

	var $name = 'HMBKP_CAPABILITY';

	protected function test() {

		return defined( 'HMBKP_CAPABILITY' ) ? HMBKP_CAPABILITY : '';

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_CAPABILITY', 'constants' );

class HMBKP_Requirement_PHP_Define_HMBKP_EMAIL extends HMBKP_Requirement {

	var $name = 'HMBKP_EMAIL';

	protected function test() {

		return defined( 'HMBKP_EMAIL' ) ? HMBKP_EMAIL : '';

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_EMAIL', 'constants' );

class HMBKP_Requirement_PHP_Define_HMBKP_EXCLUDE extends HMBKP_Requirement {

	var $name = 'HMBKP_EXCLUDE';

	protected function test() {

		return defined( 'HMBKP_EXCLUDE' ) ? HMBKP_EXCLUDE : '';

	}

}
HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_EXCLUDE', 'constants' );