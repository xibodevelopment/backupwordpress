<?php

/**
 * Unit tests for the HMBKP_Path class
 *
 * @see HMBKP_Path
 * @extends HM_Backup_UnitTestCase
 */
class testUninstallActivateDeactivateTestCase extends HM_Backup_UnitTestCase {


	public function setUp() {

	}

	public function tearDown() {

	}

	public function testDeactivation() {
		
	}

	public function testUninstall() {

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
}