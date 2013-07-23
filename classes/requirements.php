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
	 * @var array
	 */
	private static $requirements = array();


	/**
	 * Get the array of registered services
	 * @param bool $group
	 * @return array
	 */
	public static function get_requirements( $group = false ) {

		$requirements = $group ? self::$requirements[$group] : self::$requirements;

		ksort( $requirements );

		return array_map( array( 'self', 'instantiate' ), $requirements );

	}

	/**
	 * Get the requirement groups
	 * @return array
	 */
	public static function get_requirement_groups() {

		return array_keys( self::$requirements );

	}

	/**
	 * Register a new service
	 * @param        $class
	 * @param string $group
	 * @return WP_Error
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
 * An abstract service class, individual requirements should
 * extend this class
 */
abstract class HMBKP_Requirement {

	/**
	 * @return mixed
	 */
	abstract protected function test();

	/**
	 * @return mixed
	 */
	public function name() {

		return $this->name;

	}

	/**
	 * @return mixed|string
	 */
	public function result() {

		if ( is_string( $this->test() ) && $this->test() )
			return $this->test();

		if ( is_bool( $this->test() ) && $this->test() )
			return 'Yes';

		return 'No';

	}

	/**
	 * @return bool
	 */
	public function passed() {

		return (bool) $this->test();

	}

}

/**
 * Class HMBKP_Requirement_Zip_Archive
 */
class HMBKP_Requirement_Zip_Archive extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'ZipArchive';

	/**
	 * @return bool
	 */
	protected function test() {

		if ( class_exists( 'ZipArchive' ) )
			return true;

		return false;

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Zip_Archive', 'PHP' );

/**
 * Class HMBKP_Requirement_Directory_Iterator_Follow_Symlinks
 */
class HMBKP_Requirement_Directory_Iterator_Follow_Symlinks extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'DirectoryIterator FOLLOW_SYMLINKS';

	/**
	 * @return bool
	 */
	protected function test() {

		if ( defined( 'RecursiveDirectoryIterator::FOLLOW_SYMLINKS' ) )
			return true;

		return false;

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Directory_Iterator_Follow_Symlinks', 'PHP' );

/**
 * Class HMBKP_Requirement_Zip_Command
 */
class HMBKP_Requirement_Zip_Command extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'zip';

	/**
	 * @return string
	 */
	protected function test() {

		$hm_backup = new HM_Backup;

		return $hm_backup->get_zip_command_path();

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Zip_Command' );

/**
 * Class HMBKP_Requirement_Mysqldump_Command
 */
class HMBKP_Requirement_Mysqldump_Command extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'mysqldump';

	/**
	 * @return string
	 */
	protected function test() {

		$hm_backup = new HM_Backup;

		return $hm_backup->get_mysqldump_command_path();

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Mysqldump_Command' );

/**
 * Class HMBKP_Requirement_PHP_User
 */
class HMBKP_Requirement_PHP_User extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'PHP User';

	/**
	 * @return string
	 */
	protected function test() {

		return shell_exec( 'whoami' );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_User', 'PHP' );

/**
 * Class HMBKP_Requirement_PHP_Group
 */
class HMBKP_Requirement_PHP_Group extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'PHP Group[s]';

	/**
	 * @return string
	 */
	protected function test() {

		return shell_exec( 'groups' );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Group', 'PHP' );

/**
 * Class HMBKP_Requirement_PHP_Version
 */
class HMBKP_Requirement_PHP_Version extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'PHP Version';

	/**
	 * @return string
	 */
	protected function test() {

		return PHP_VERSION;

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Version', 'PHP' );

/**
 * Class HMBKP_Requirement_Cron_Array
 */
class HMBKP_Requirement_Cron_Array extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Cron Array';

	/**
	 * @return bool|mixed
	 */
	protected function test() {

		$cron = get_option( 'cron' );

		if ( ! $cron )
			return false;

		return var_export( $cron, true );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Cron_Array' );

/**
 * Class HMBKP_Requirement_Safe_Mode
 */
class HMBKP_Requirement_Safe_Mode extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Safe Mode';

	/**
	 * @return bool
	 */
	protected function test() {

		return HM_Backup::is_safe_mode_active();

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Safe_Mode', 'PHP' );

/**
 * Class HMBKP_Requirement_Shell_Exec
 */
class HMBKP_Requirement_Shell_Exec extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Shell Exec';

	/**
	 * @return bool
	 */
	protected function test() {

		return HM_Backup::is_shell_exec_available();

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Shell_Exec', 'PHP' );

/**
 * Class HMBKP_Requirement_Memory_Limit
 */
class HMBKP_Requirement_Memory_Limit extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Memory Limit';

	/**
	 * @return string
	 */
	protected function test() {

		return @ini_get( 'memory_limit' );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Memory_Limit', 'PHP' );

/**
 * Class HMBKP_Requirement_Backup_Path
 */
class HMBKP_Requirement_Backup_Path extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Backup Path';

	/**
	 * @return string
	 */
	protected function test() {

		return hmbkp_path();

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Backup_Path', 'Site' );

/**
 * Class HMBKP_Requirement_Backup_Path_Permissions
 */
class HMBKP_Requirement_Backup_Path_Permissions extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Backup Path Permissions';

	/**
	 * @return string
	 */
	protected function test() {

		return substr( sprintf( '%o', fileperms( hmbkp_path() ) ), - 4 );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Backup_Path_Permissions', 'Site' );

/**
 * Class HMBKP_Requirement_WP_CONTENT_DIR
 */
class HMBKP_Requirement_WP_CONTENT_DIR extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'WP_CONTENT_DIR';

	/**
	 * @return string
	 */
	protected function test() {

		return WP_CONTENT_DIR;

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_WP_CONTENT_DIR', 'Site' );

/**
 * Class HMBKP_Requirement_WP_CONTENT_DIR_Permissions
 */
class HMBKP_Requirement_WP_CONTENT_DIR_Permissions extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'WP_CONTENT_DIR Permissions';

	/**
	 * @return string
	 */
	protected function test() {

		return substr( sprintf( '%o', fileperms( WP_CONTENT_DIR ) ), - 4 );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_WP_CONTENT_DIR_Permissions', 'Site' );

/**
 * Class HMBKP_Requirement_ABSPATH
 */
class HMBKP_Requirement_ABSPATH extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'ABSPATH';

	/**
	 * @return string
	 */
	protected function test() {

		return ABSPATH;

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_ABSPATH', 'Site' );

/**
 * Class HMBKP_Requirement_Backup_Root_Path
 */
class HMBKP_Requirement_Backup_Root_Path extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Backup Root Path';

	/**
	 * @return string
	 */
	protected function test() {

		$hm_backup = new HM_Backup();

		return $hm_backup->get_root();

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Backup_Root_Path', 'Site' );

/**
 * Class HMBKP_Requirement_Calculated_Size
 */
class HMBKP_Requirement_Calculated_Size extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Calculated size of site';

	/**
	 * @return string
	 */
	protected function test() {

		$schedule = new HMBKP_Scheduled_Backup( 'test' );

		return $schedule->get_filesize();

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Calculated_Size', 'Site' );

/**
 * Class HMBKP_Requirement_WP_Cron_Test_Response
 */
class HMBKP_Requirement_WP_Cron_Test_Response extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'WP Cron Response';

	/**
	 * @return mixed
	 */
	protected function test() {

		return  var_export( get_option( 'hmbkp_wp_cron_test_response' ), true );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_WP_Cron_Test_Response' );

/**
 * Class HMBKP_Requirement_PHP_API
 */
class HMBKP_Requirement_PHP_API extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'PHP Interface';

	/**
	 * @return string
	 */
	protected function test() {

		return php_sapi_name();

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_API', 'Server' );

/**
 * Class HMBKP_Requirement_SERVER
 */
class HMBKP_Requirement_SERVER extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'PHP SERVER Global';

	/**
	 * @return mixed
	 */
	protected function test() {

		return var_export( $_SERVER, true );
	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_SERVER' );

/**
 * Class HMBKP_Requirement_Server_Software
 */
class HMBKP_Requirement_Server_Software extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Server Sofware';

	/**
	 * @return bool
	 */
	protected function test() {

		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) )
			return $_SERVER['SERVER_SOFTWARE'];

		return false;

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Server_Software', 'Server' );

/**
 * Class HMBKP_Requirement_Server_OS
 */
class HMBKP_Requirement_Server_OS extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'Server OS';

	/**
	 * @return string
	 */
	protected function test() {

		return PHP_OS;

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_Server_OS', 'Server' );

/**
 * Class HMBKP_Requirement_PHP_Disable_Functions
 */
class HMBKP_Requirement_PHP_Disable_Functions extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'PHP Disabled Functions';

	/**
	 * @return string
	 */
	protected function test() {

		return @ini_get( 'disable_functions' );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Disable_Functions', 'PHP' );

/**
 * Class HMBKP_Requirement_PHP_Open_Basedir
 */
class HMBKP_Requirement_PHP_Open_Basedir extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'PHP <code>open_basedir</code> Restriction';

	/**
	 * @return string
	 */
	protected function test() {

		return @ini_get( 'open_basedir' );

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Open_Basedir', 'PHP' );

/* CONSTANTS */

/**
 * Class HMBKP_Requirement_PHP_Define_HMBKP_PATH
 */
class HMBKP_Requirement_PHP_Define_HMBKP_PATH extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_PATH';

	/**
	 * @return string
	 */
	protected function test() {

		return defined( 'HMBKP_PATH' ) ? HMBKP_PATH : '';

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_PATH', 'constants' );

/**
 * Class HMBKP_Requirement_PHP_Define_HMBKP_ROOT
 */
class HMBKP_Requirement_PHP_Define_HMBKP_ROOT extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_ROOT';

	/**
	 * @return string
	 */
	protected function test() {

		return defined( 'HMBKP_ROOT' ) ? HMBKP_ROOT : '';

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_ROOT', 'constants' );

/**
 * Class HMBKP_Requirement_PHP_Define_HMBKP_MYSQLDUMP_PATH
 */
class HMBKP_Requirement_PHP_Define_HMBKP_MYSQLDUMP_PATH extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_MYSQLDUMP_PATH';

	/**
	 * @return string
	 */
	protected function test() {

		return defined( 'HMBKP_MYSQLDUMP_PATH' ) ? HMBKP_MYSQLDUMP_PATH : '';

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_MYSQLDUMP_PATH', 'constants' );

/**
 * Class HMBKP_Requirement_PHP_Define_HMBKP_ZIP_PATH
 */
class HMBKP_Requirement_PHP_Define_HMBKP_ZIP_PATH extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_ZIP_PATH';

	/**
	 * @return string
	 */
	protected function test() {

		return defined( 'HMBKP_ZIP_PATH' ) ? HMBKP_ZIP_PATH : '';

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_ZIP_PATH', 'constants' );

/**
 * Class HMBKP_Requirement_PHP_Define_HMBKP_CAPABILITY
 */
class HMBKP_Requirement_PHP_Define_HMBKP_CAPABILITY extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_CAPABILITY';

	/**
	 * @return string
	 */
	protected function test() {

		return defined( 'HMBKP_CAPABILITY' ) ? HMBKP_CAPABILITY : '';

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_CAPABILITY', 'constants' );

/**
 * Class HMBKP_Requirement_PHP_Define_HMBKP_EMAIL
 */
class HMBKP_Requirement_PHP_Define_HMBKP_EMAIL extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_EMAIL';

	/**
	 * @return string
	 */
	protected function test() {

		return defined( 'HMBKP_EMAIL' ) ? HMBKP_EMAIL : '';

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_EMAIL', 'constants' );

/**
 * Class HMBKP_Requirement_PHP_Define_HMBKP_EXCLUDE
 */
class HMBKP_Requirement_PHP_Define_HMBKP_EXCLUDE extends HMBKP_Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_EXCLUDE';

	/**
	 * @return string
	 */
	protected function test() {

		return defined( 'HMBKP_EXCLUDE' ) ? HMBKP_EXCLUDE : '';

	}

}

HMBKP_Requirements::register( 'HMBKP_Requirement_PHP_Define_HMBKP_EXCLUDE', 'constants' );

class HMBKP_Requirement_Active_Plugins extends HMBKP_Requirement {

	var $name = 'Active Plugins';

	protected function test(){

		return var_export( get_option('active_plugins'), true );
	}
}

HMBKP_Requirements::register( 'HMBKP_Requirement_Active_Plugins' );