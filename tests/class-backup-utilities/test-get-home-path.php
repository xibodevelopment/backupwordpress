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

	/**
	 * In this scenario WordPress is installed as normal with wp-config.php and index.php in the root directory.
	 */
	function test_standard_install() {

		$this->assertEquals( wp_normalize_path( untrailingslashit( ABSPATH ) ), Path::get_home_path() );

	}

	/**
	 * In this scenario, WordPress is installed in a subdirectory with index.php and wp-config both in root.
	 */
	function test_standard_install_in_subdirectory() {

		$home = get_option( 'home' );
		$siteurl = get_option( 'siteurl' );
		$sfn = $_SERVER['SCRIPT_FILENAME'];
		$this->assertEquals( wp_normalize_path( untrailingslashit( ABSPATH ) ), Path::get_home_path() );

		update_option( 'home', 'http://localhost' );
		update_option( 'siteurl', 'http://localhost/wp' );

		$_SERVER['SCRIPT_FILENAME'] = 'D:\root\vhosts\site\httpdocs\wp\wp-admin\options-permalink.php';
		$this->assertEquals( 'D:/root/vhosts/site/httpdocs/', trailingslashit( Path::get_home_path() ) );

		$_SERVER['SCRIPT_FILENAME'] = '/Users/foo/public_html/trunk/wp/wp-admin/options-permalink.php';
		$this->assertEquals( '/Users/foo/public_html/trunk/',  trailingslashit( Path::get_home_path() ) );

		$_SERVER['SCRIPT_FILENAME'] = 'S:/home/wordpress/trunk/wp/wp-admin/options-permalink.php';
		$this->assertEquals( 'S:/home/wordpress/trunk/',  trailingslashit( Path::get_home_path() ) );

		update_option( 'home', $home );
		update_option( 'siteurl', $siteurl );
		$_SERVER['SCRIPT_FILENAME'] = $sfn;

	}

}
