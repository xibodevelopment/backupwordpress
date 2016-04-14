<?php

namespace HM\BackUpWordPress;

/**
 * An abstract requirement class, individual requirements should
 * extend this class
 */
abstract class Requirement {

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @return mixed
	 */
	protected static function test() {}

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

		$test = $this->test();

		if ( is_string( $test ) && $test ) {
			return $test;
		}

		if ( is_bool( $test ) || empty( $test ) ) {

			if ( $test ) {
				return 'Yes';
			}

			return 'No';

		}

		return var_export( $test, true );

	}

	public function raw_result() {
		return $this->test();
	}
}

/**
 * Class Requirement_Zip_Archive
 */
class Requirement_Zip_Archive extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'ZipArchive';

	/**
	 * @return bool
	 */
	public static function test() {

		if ( class_exists( 'ZipArchive' ) ) {
			return true;
		}

		return false;

	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Zip_Archive', 'PHP' );

/**
 * Class Requirement_Zip_Command
 *
 * Tests whether the zip command is available and if it is what path it's available at
 */
class Requirement_Zip_Command_Path extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'zip command';

	/**
	 * @return string
	 */
	public static function test() {

		$backup = new Zip_File_Backup_Engine;

		return $backup->get_zip_executable_path();

	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Zip_Command_Path', 'Server' );

/**
 * Class Requirement_Mysqldump_Command
 *
 * Tests whether the zip command is available and if it is what path it's available at
 */
class Requirement_Mysqldump_Command_Path extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'mysqldump command';

	/**
	 * @return string
	 */
	public static function test() {

		$backup = new Mysqldump_Database_Backup_Engine;

		return $backup->get_mysqldump_executable_path();

	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Mysqldump_Command_Path', 'Server' );

/**
 * Class Requirement_PHP_Version
 */
class Requirement_PHP_Version extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Version';

	/**
	 * @return string
	 */
	public static function test() {
		return PHP_VERSION;
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_PHP_Version', 'PHP' );

/**
 * Class Requirement_Cron_Array
 */
class Requirement_Cron_Array extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Cron Array';

	/**
	 * @return bool|mixed
	 */
	public static function test() {

		$cron = get_option( 'cron' );

		if ( ! $cron ) {
			return false;
		}

		return $cron;

	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Cron_Array', 'Site' );

/**
 * Class Requirement_Cron_Array
 */
class Requirement_Language extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Language';

	/**
	 * @return bool|mixed
	 */
	public static function test() {

		// Since 4.0
		$language = get_option( 'WPLANG' );

		if ( $language ) {
			return $language;
		}

		if ( defined( 'WPLANG' ) && WPLANG ) {
			return WPLANG;
		}

		return 'en_US';

	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Language', 'Site' );

/**
 * Class Requirement_Safe_Mode
 */
class Requirement_Safe_Mode extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Safe Mode';

	/**
	 * @return bool
	 */
	public static function test() {
		return Backup_Utilities::is_safe_mode_on();
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Safe_Mode', 'PHP' );

/**
 * Class Requirement_Memory_Limit
 */
class Requirement_PHP_Memory_Limit extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Memory Limit';

	/**
	 * @return string
	 */
	public static function test() {
		return @ini_get( 'memory_limit' );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_PHP_Memory_Limit', 'PHP' );

/**
 * Class Requirement_Backup_Path
 */
class Requirement_Backup_Path extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Backup Path';

	/**
	 * @return string
	 */
	public static function test() {
		return Path::get_path();
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Backup_Path', 'Site' );

/**
 * Class Requirement_Backup_Path_Permissions
 */
class Requirement_Backup_Path_Permissions extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Backup Path Permissions';

	/**
	 * @return string
	 */
	public static function test() {
		return substr( sprintf( '%o', fileperms( Path::get_path() ) ), - 4 );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Backup_Path_Permissions', 'Site' );

/**
 * Class Requirement_WP_CONTENT_DIR
 */
class Requirement_WP_CONTENT_DIR extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'WP_CONTENT_DIR';

	/**
	 * @return string
	 */
	public static function test() {
		return WP_CONTENT_DIR;
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_WP_CONTENT_DIR', 'Site' );

/**
 * Class Requirement_WP_CONTENT_DIR_Permissions
 */
class Requirement_WP_CONTENT_DIR_Permissions extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'WP_CONTENT_DIR Permissions';

	/**
	 * @return string
	 */
	public static function test() {
		return substr( sprintf( '%o', fileperms( WP_CONTENT_DIR ) ), - 4 );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_WP_CONTENT_DIR_Permissions', 'Site' );

/**
 * Class Requirement_ABSPATH
 */
class Requirement_ABSPATH extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'ABSPATH';

	/**
	 * @return string
	 */
	public static function test() {
		return ABSPATH;
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_ABSPATH', 'Site' );

/**
 * Class Requirement_Backup_Root_Path
 */
class Requirement_Backup_Root_Path extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Site Root Path';

	/**
	 * @return string
	 */
	public static function test() {
		return Path::get_root();
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Backup_Root_Path', 'Site' );

/**
 * Class Requirement_Calculated_Size
 */
class Requirement_Calculated_Size extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Calculated size of site';

	/**
	 * @return array
	 */
	public static function test() {

		$backup_sizes = array();

		$schedules = Schedules::get_instance();

		foreach ( $schedules->get_schedules() as $schedule ) {

			$site_size = new Site_Size( $schedule->get_type(), $schedule->get_excludes() );

			if ( $site_size->is_site_size_cached() ) {
				$backup_sizes[ $schedule->get_type() ] = $site_size->get_formatted_site_size();
			}
		}

		return $backup_sizes;

	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Calculated_Size', 'Site' );

/**
 * Class Requirement_WP_Cron_Test_Response
 */
class Requirement_WP_Cron_Test extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'WP Cron Test Failed';

	/**
	 * @return mixed
	 */
	public static function test() {
		return (bool) get_option( 'hmbkp_wp_cron_test_failed' );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_WP_Cron_Test', 'Site' );

/**
 * Class Requirement_PHP_API
 */
class Requirement_PHP_API extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Interface';

	/**
	 * @return string
	 */
	public static function test() {
		return php_sapi_name();
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_PHP_API', 'PHP' );

/**
 * Class Requirement_Server_Software
 */
class Requirement_Server_Software extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Server';

	/**
	 * @return bool
	 */
	public static function test() {

		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			return $_SERVER['SERVER_SOFTWARE'];
		}

		return false;

	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Server_Software', 'Server' );

/**
 * Class Requirement_Server_OS
 */
class Requirement_Server_OS extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'OS';

	/**
	 * @return string
	 */
	public static function test() {
		return PHP_OS;
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Server_OS', 'Server' );

/**
 * Class Requirement_PHP_Disable_Functions
 */
class Requirement_PHP_Disable_Functions extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'Disabled Functions';

	/**
	 * @return string
	 */
	public static function test() {
		return @ini_get( 'disable_functions' );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_PHP_Disable_Functions', 'PHP' );

/**
 * Class Requirement_PHP_Open_Basedir
 */
class Requirement_PHP_Open_Basedir extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'open_basedir';

	/**
	 * @return string
	 */
	public static function test() {
		return @ini_get( 'open_basedir' );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_PHP_Open_Basedir', 'PHP' );

/* CONSTANTS */

/**
 * Class Requirement_Define_HMBKP_PATH
 */
class Requirement_Define_HMBKP_PATH extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_PATH';

	/**
	 * @return string
	 */
	public static function test() {
		return defined( 'HMBKP_PATH' ) ? HMBKP_PATH : '';
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Define_HMBKP_PATH', 'constants' );

/**
 * Class Requirement_Define_HMBKP_ROOT
 */
class Requirement_Define_HMBKP_ROOT extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_ROOT';

	/**
	 * @return string
	 */
	public static function test() {
		return defined( 'HMBKP_ROOT' ) ? HMBKP_ROOT : '';
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Define_HMBKP_ROOT', 'constants' );

/**
 * Class Requirement_Define_HMBKP_MYSQLDUMP_PATH
 */
class Requirement_Define_HMBKP_MYSQLDUMP_PATH extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_MYSQLDUMP_PATH';

	/**
	 * @return string
	 */
	public static function test() {
		return defined( 'HMBKP_MYSQLDUMP_PATH' ) ? HMBKP_MYSQLDUMP_PATH : '';
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Define_HMBKP_MYSQLDUMP_PATH', 'constants' );

/**
 * Class Requirement_Define_HMBKP_ZIP_PATH
 */
class Requirement_Define_HMBKP_ZIP_PATH extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_ZIP_PATH';

	/**
	 * @return string
	 */
	public static function test() {
		return defined( 'HMBKP_ZIP_PATH' ) ? HMBKP_ZIP_PATH : '';
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Define_HMBKP_ZIP_PATH', 'constants' );

/**
 * Class Requirement_Define_HMBKP_CAPABILITY
 */
class Requirement_Define_HMBKP_CAPABILITY extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_CAPABILITY';

	/**
	 * @return string
	 */
	public static function test() {
		return defined( 'HMBKP_CAPABILITY' ) ? HMBKP_CAPABILITY : '';
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Define_HMBKP_CAPABILITY', 'constants' );

/**
 * Class Requirement_Define_HMBKP_EMAIL
 */
class Requirement_Define_HMBKP_EMAIL extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_EMAIL';

	/**
	 * @return string
	 */
	public static function test() {
		return defined( 'HMBKP_EMAIL' ) ? HMBKP_EMAIL : '';
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Define_HMBKP_EMAIL', 'constants' );

/**
 * Class Requirement_Define_HMBKP_ATTACHMENT_MAX_FILESIZE
 */
class Requirement_Define_HMBKP_ATTACHMENT_MAX_FILESIZE extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_ATTACHMENT_MAX_FILESIZE';

	/**
	 * @return string
	 */
	public static function test() {
		return defined( 'HMBKP_ATTACHMENT_MAX_FILESIZE' ) ? HMBKP_ATTACHMENT_MAX_FILESIZE : '';
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Define_HMBKP_ATTACHMENT_MAX_FILESIZE', 'constants' );

/**
 * Class Requirement_Define_HMBKP_EXCLUDE
 */
class Requirement_Define_HMBKP_EXCLUDE extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'HMBKP_EXCLUDE';

	/**
	 * @return string
	 */
	public static function test() {
		return defined( 'HMBKP_EXCLUDE' ) ? HMBKP_EXCLUDE : '';
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Define_HMBKP_EXCLUDE', 'constants' );

class Requirement_Active_Plugins extends Requirement {

	var $name = 'Active Plugins';

	public static function test() {
		return get_option( 'active_plugins' );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Active_Plugins', 'Site' );

class Requirement_Home_Url extends Requirement {

	var $name = 'Home URL';

	public static function test() {
		return home_url();
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Home_Url', 'Site' );

class Requirement_Site_Url extends Requirement {

	var $name = 'Site URL';

	public static function test() {
		return site_url();
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Site_Url', 'Site' );

class Requirement_Plugin_Version extends Requirement {
	var $name = 'Plugin Version';

	public static function test() {
		return Plugin::PLUGIN_VERSION;
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Plugin_Version', 'constants' );

class Requirement_Max_Exec extends Requirement {

	var $name = 'Max execution time';

	public static function test() {
		return @ini_get( 'max_execution_time' );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Max_Exec', 'PHP' );

class Requirement_PDO extends Requirement {

	var $name = 'PDO';

	public static function test() {

		if ( class_exists( 'PDO' ) && \PDO::getAvailableDrivers() ) {
			return implode( ', ', \PDO::getAvailableDrivers() );
		}

		return false;

	}
}

Requirements::register( 'HM\BackUpWordPress\Requirement_PDO', 'PHP' );

/**
 * Class Requirement_Proc_Open
 */
class Requirement_Proc_Open extends Requirement {

	/**
	 * @var string
	 */
	var $name = 'proc_open';

	/**
	 * @return bool
	 */
	public static function test() {
		return function_exists( 'proc_open' ) && function_exists( 'proc_close' );
	}
}
Requirements::register( 'HM\BackUpWordPress\Requirement_Proc_Open', 'PHP' );
