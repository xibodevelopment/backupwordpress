<?php

/**
 * Unit tests for the Path class
 *
 * @see Path
 * @extends HM_Backup_UnitTestCase
 */
class testBackupPathTestCase extends HM_Backup_UnitTestCase {

	public function setUp() {

		// We need to mess with the $is_apache global so let's back it up now
		global $is_apache;
		$this->is_apache = $is_apache;

		$this->path = HM\BackUpWordPress\Path::get_instance();
		$this->custom_path = WP_CONTENT_DIR . '/custom';

		// Cleanup before we kickoff in-case theirs cruft around from previous failures
		$this->tearDown();

	}

	public function tearDown() {

		// Restore the is_apache global in-case it was messed with in the test
		global $is_apache;

		if ( isset( $this->is_apache ) ) {
			$is_apache = $this->is_apache;
		}

		// Remove all backup paths that exist
		foreach( $this->path->get_existing_paths() as $path ) {
			hmbkp_rmdirtree( $path );
		}

		// Remove our custom path
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

		$this->assertEquals( $this->path->get_default_path(), $this->path->get_path() );

		if ( wp_is_writable( $this->path->get_default_path() ) ) {
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

		$paths = $this->generate_additional_paths();

		// Do a single database backup in each path
		foreach ( $paths as $path ) {

			$this->path->set_path( $path );

			$backup = new HM\BackUpWordPress\Backup();

			$backup->set_type( 'database' );

			// We want to avoid name clashes
			$backup->set_archive_filename( microtime() . '.zip' );

			$backup->backup();

			$this->assertFileExists( $backup->get_archive_filepath() );

			$backups[] = $backup->get_archive_filename();

		}

		$this->path->merge_existing_paths();

		foreach ( $backups as $backup ) {
			$this->assertFileExists( $this->path->get_path() . '/' . $backup );
		}


	}

	/**
	 * Test that the backup path is correctly protected
	 */
	public function testIsPathProtected() {

		// Fake that we're on Apache so we can also test .htaccess
		global $is_apache;
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