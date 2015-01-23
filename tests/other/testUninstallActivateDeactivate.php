<?php

/**
 * Unit tests for the HMBKP_Path class
 *
 * @see HMBKP_Path
 * @extends HM_Backup_UnitTestCase
 */
class testUninstallActivateDeactivateTestCase extends HM_Backup_UnitTestCase {

	protected $plugin;
	protected $schedule_intervals;
	protected $schedule_ids = array();

	public function setUp() {

		$this->plugin = BackUpWordPress_Plugin::get_instance();

		foreach ( HMBKP_Schedules::get_instance()->get_schedules() as $schedule ) {
			$this->schedule_ids[] = $schedule->get_id();
		}
	}

	public function tearDown() {

	}

	public function testDeactivation() {

		BackUpWordPress_Setup::deactivate();

		foreach ( $this->schedule_ids as $id ) {
			$this->asserFalse( wp_next_scheduled( 'hmbkp_schedule_hook', array( 'id' => $id ) ) );
		}

	}

	public function testUninstall() {

		BackUpWordPress_Setup::uninstall();

		$transients = array( 'hmbkp_plugin_data', 'hmbkp_directory_filesizes', 'hmbkp_directory_filesize_running' );

		$options = array( 'hmbkp_enable_support', 'hmbkp_plugin_version', 'hmbkp_path', 'hmbkp_default_path', 'hmbkp_upsell' );

		foreach ( $transients as $transient ) {
			$this->assertFalse( get_transient( $transient ) );
		}

		foreach ( $options as $option ) {
			$this->assertFalse( get_option( $option ) );
		}

		$this->assertFalse( is_dir( hmbkp_path() ) );

		$this->assertEmpty( HMBKP_Schedules::get_instance()->get_schedules() );
	}
}