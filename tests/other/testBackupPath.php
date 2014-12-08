<?php

/**
 * Unit tests for the HMBKP_Path class
 *
 * @see HMBKP_Path
 * @extends HM_Backup_UnitTestCase
 */
class testBackupPathTestCase extends HM_Backup_UnitTestCase {

	public function setUp() {

		$this->path = new HMBKP_Path;
		$this->custom_path = WP_CONTENT_DIR . '/custom';

		// Cleanup before we kickoff in-case theirs cruft around from previous failures
		$this->tearDown();

	}

	public function tearDown() {

		global $is_apache, $hmbkp_is_apache;

		$is_apache = $hmbkp_is_apache;

		foreach( $this->path->get_existing_paths() as $path ) {
			hmbkp_rmdirtree( $path );
		}

		hmbkp_rmdirtree( $this->custom_path );

	}

	/**
	 * By default the path should be the default path
	 */
	public function testdefaultPath() {

		$this->assertEquals( $this->path->get_default_path(), $this->path->get_path() );

		$this->assertFileExists( $this->path->get_default_path() );

	}

	/**
	 * If the default path is unwritable then it should fallback to the fallback path
	 */
	public function testFallbackPath() {

		$this->path->calculate_path();

		// TODO for some reason this isn't working
		chmod( $this->path->get_default_path(), 0555 );

		if ( is_writable( $this->path->get_default_path() ) ) {
			$this->markTestSkipped( 'The default path was still writable' );
		}

		$this->path->calculate_path();

		$this->assertEquals( $this->path->get_path(), $this->path->get_fallback_path() );

		$this->assertFileExists( $this->path->get_fallback_path() );

	}

	/**
	 * If there are 1 or more existing paths then the first one of those should be used
	 */
	public function testExistingPath() {

		$paths = $this->generate_additional_paths();

		$this->assertEquals( $this->path->get_existing_path(), $paths[0] );

		$this->path->set_path( '' );

		$this->path->calculate_path();

		$this->assertEquals( $this->path->get_path(), $this->path->get_existing_path() );

		$this->assertFileExists( $this->path->get_existing_path() );

	}

	/**
	 * If there are several existing paths this should find all of them
	 */
	public function testExistingPaths() {

		$paths = $this->generate_additional_paths();

		$this->assertEquals( $paths, $this->path->get_existing_paths() );

	}

	/**
	 * Setting a writable custom path should override everything
	 */
	public function testCustomPath() {

		$this->path->set_path( $this->custom_path );

		$this->assertEquals( $this->path->get_custom_path(), $this->custom_path );

		$this->assertEquals( $this->path->get_path(), $this->custom_path );

		$this->assertFileExists( $this->path->get_custom_path() );

	}

	/**
	 * Unwritable or otherwide broken custom paths should be ignored
	 */
	public function testUnwritableCustomPath() {

		$this->path->set_path( '/' . rand() );

		if ( is_writable( $this->custom_path ) ) {
			$this->markTestSkipped( 'The custom path was still writable' );
		}

		$this->assertEquals( $this->path->get_path(), $this->path->get_default_path() );

		$this->assertFileExists( $this->path->get_default_path() );

	}

	/**
	 * Test that backups are correctly merged from multiple existing backup paths
	 */
	public function testMergeExistingPath() {

		$backup = new HMBKP_Scheduled_Backup( (string) time() );

		$path = $this->path->get_path();

		$backup->set_path( $path );
		$backup->set_type( 'database' );

		$backup->backup();

		$this->assertFileExists( $backup->get_archive_filepath() );

		$this->path->set_path( $this->custom_path );

		$this->path->merge_existing_paths();

		$this->assertFileExists( str_replace( $path, $this->custom_path, $backup->get_archive_filepath() ) );

	}

	/**
	 * Test that the backup path is correctly protected
	 */
	public function testIsPathProtected() {

		// Fake that we're on Apache so we can also test .htaccess
		global $is_apache, $hmbkp_is_apache;

		$hmbkp_is_apache = $is_apache;
		$is_apache = true;

		// Test the default backup path
		$this->assertFileExists( $this->path->get_path() . '/index.html' );
		$this->assertFileExists( $this->path->get_path() . '/.htaccess' );

		// Test a custom backup path
		$this->path->set_path( $this->custom_path );

		$this->path->calculate_path();

		$this->assertFileExists( $this->path->get_path() . '/index.html' );
		$this->assertFileExists( $this->path->get_path() . '/.htaccess' );

	}

	/**
	 * Create multiple backup paths for testing purposes
	 */
	private function generate_additional_paths() {

		for ( $i = 0; $i < 3; $i++ ) {
			$paths[] = $path = WP_CONTENT_DIR . '/backupwordpress-' . str_pad( $i, 10, $i ) . '-backups';
			$this->path->set_path( $path );
		}

		$uploads = wp_upload_dir();

		for ( $i = 0; $i < 3; $i++ ) {
			$paths[] = $path = $uploads['basedir'] . '/backupwordpress-' . str_pad( $i, 10, $i ) . '-backups';
			$this->path->set_path( $path );
		}

		return $paths;

	}

}