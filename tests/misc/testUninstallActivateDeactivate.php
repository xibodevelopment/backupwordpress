<?php

/**
 * Unit tests for the HMBKP_Path class
 *
 * @see HMBKP_Path
 * @extends HM_Backup_UnitTestCase
 */
class testUninstallActivateDeactivateTestCase extends HM_Backup_UnitTestCase {


	protected $plugin = null;

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

		// Just make sure the transients have been deleted, which means plugin wa deactivated.
		$transients = array( 'hmbkp_plugin_data', 'hmbkp_directory_filesizes', 'hmbkp_directory_filesize_running' );

		foreach ( $transients as $transient ) {
			$this->assertFalse( get_transient( $transient ) );
		}
	}

	public function test_activate() {

		$this->plugin = HM\BackUpWordPress\Plugin::get_instance();
		$this->assertInstanceOf( 'HM\BackUpWordPress\Plugin', $this->plugin );

	}
}
