<?php

namespace HM\BackUpWordPress;

class Home_Path_Tests extends \HM_Backup_UnitTestCase {

	function setUp() {

		$this->setup_test_data();

	}

	function tearDown() {
		rmdirtree( $this->test_data );
		rmdirtree( $this->test_data_symlink );
	}

	function test_standard_install() {

		$abspath = $this->test_data;
		$this->wp_config_in( $abspath );
		$this->index_in( $abspath );
		$path = Path::get_home_path( $abspath );

		$this->assertEquals( $abspath, $path );

	}

	function test_standard_install_wp_config_above_abspath() {

		$abspath = $this->test_data . '/exclude' ;
		$this->wp_config_in( dirname( $abspath ) );
		$this->index_in( $abspath );
		$path = Path::get_home_path( $abspath );

		$this->assertEquals( $abspath, $path );

	}

	/**
	 * In this scenario, index.php and wp-config are both in the parent folder.
	 */
	function test_standard_install_in_subdirectory_1() {

		$abspath = $this->test_data . '/exclude' ;
		$this->wp_config_in( dirname( $abspath ) );
		$this->index_in( dirname( $abspath ) );
		$path = Path::get_home_path( $abspath );

		$this->assertEquals( dirname( $abspath ), $path );

	}

	/**
	 * In this scenario, there is an index.php in the parent folder of the WP install, but wp-config.php is in the WP folder
	 */
	function test_standard_install_in_subdirectory_2() {

		$abspath = $this->test_data . '/exclude' ;
		$this->wp_config_in( $abspath );
		$this->index_in( dirname( $abspath ) );
		$path = Path::get_home_path( $abspath );

		$this->assertEquals( dirname( $abspath ), $path );

	}

	private function wp_config_in( $path ) {
		file_put_contents( trailingslashit( $path ) . 'wp-config.php', '// I am your father' );
	}

	private function index_in( $path ) {
		file_put_contents( trailingslashit( $path ) . 'index.php', '// shhh' );
	}

}
