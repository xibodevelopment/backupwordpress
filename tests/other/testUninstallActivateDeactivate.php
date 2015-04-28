<?php

/**
 * Unit tests for the HMBKP_Path class
 *
 * @see HMBKP_Path
 * @extends HM_Backup_UnitTestCase
 */
class testUninstallActivateDeactivateTestCase extends HM_Backup_UnitTestCase {


	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_uninstall() {

		//BackUpWordPress_Setup::uninstall();

		$transients = array( 'hmbkp_plugin_data', 'hmbkp_directory_filesizes', 'hmbkp_directory_filesize_running' );

		$options = array( 'hmbkp_enable_support', 'hmbkp_plugin_version', 'hmbkp_path', 'hmbkp_default_path', 'hmbkp_upsell' );

		foreach ( $transients as $transient ) {
			$this->assertFalse( get_transient( $transient ) );
		}

		foreach ( $options as $option ) {
			$this->assertFalse( get_option( $option ) );
		}

	}

	public function test_deactivate() {

		$res = activate_plugin( 'backupwordpress/backupwordpress.php' );
		$this->assertFalse( is_wp_error( $res ) );
		deactivate_plugins( 'backupwordpress/backupwordpress.php' );
		$this->assertFalse( is_plugin_active( 'backupwordpress/backupwordpress.php' ) );
		$this->assertGreaterThanOrEqual( 1, did_action( 'deactivate_backupwordpress/backupwordpress.php' ) );
	}

	public function test_activate() {

		$res = activate_plugin( 'backupwordpress/backupwordpress.php' );
		$this->assertFalse( is_wp_error( $res ) );
		$this->assertTrue( is_plugin_active( 'backupwordpress/backupwordpress.php' ) );

		// Check our activation hook was registered
		$this->assertGreaterThanOrEqual( 1, did_action( 'activate_backupwordpress/backupwordpress.php' ) );

	}
}