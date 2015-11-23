<?php

namespace HM\BackUpWordPress;

/**
 * Unit tests for the Path class
 *
 * @see Path
 * @extends HM_Backup_UnitTestCase
 */
class Test_Backup_Path extends \HM_Backup_UnitTestCase {

	public function setUp() {

		// We need to mess with the $is_apache global so let's back it up now
		global $is_apache;
		$this->is_apache = $is_apache;

		$this->path = Path::get_instance();
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

		if ( file_exists( $this->path->get_default_path() ) ) {
			chmod( $this->path->get_default_path(), 0755 );
		}

		chmod( dirname( $this->path->get_default_path() ), 0755 );

		// Reset the path internally
		$this->path->reset_path();

		// Remove all backup paths that exist
		foreach( $this->path->get_existing_paths() as $path ) {
			rmdirtree( $path );
		}

		// Remove our custom path
		rmdirtree( $this->custom_path );

	}

	/**
	 * By default the path should be the default path
	 */
	public function testdefaultPath() {

		$this->assertEquals( $this->path->get_default_path(), Path::get_path() );

		$this->assertFileExists( $this->path->get_default_path() );

	}

	/**
	 * If the default path is unwritable then it should fallback to the fallback path
	 */
	public function testFallbackPath() {

		$this->assertEquals( $this->path->get_default_path(), Path::get_path() );

		$path = $this->path->get_default_path();

		chmod( $path, 0555 );

		$this->path->calculate_path();

		// wp_mkdir_p fixes permissions which invalidates this test
		if ( wp_is_writable( $path ) ) {
			$this->markTestSkipped( 'The default path was still writable' );
		}

		$this->assertEquals( Path::get_path(), $this->path->get_fallback_path() );

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

		$this->assertEquals( Path::get_path(), $this->path->get_existing_path() );

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

		$this->assertEquals( Path::get_path(), $this->custom_path );

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

		$this->assertEquals( Path::get_path(), $this->path->get_default_path() );

		$this->assertFileExists( $this->path->get_default_path() );

	}

	/**
	 * Test that backups are correctly merged from multiple existing backup paths
	 */
	public function testMergeExistingPath() {

		$paths = $this->generate_additional_paths();

		// Create a dummy database backup in each path
		foreach ( $paths as $path ) {

				$backups[] = $backup = microtime() . '.zip';

				file_put_contents( trailingslashit( $path ) . $backup, 'Just keep swimming, just keep swimming...' );

		}

		$this->path->merge_existing_paths();

		foreach ( $backups as $backup ) {
			$this->assertFileExists( Path::get_path() . '/' . $backup );
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
		$this->assertFileExists( Path::get_path() . '/index.html' );
		$this->assertFileExists( Path::get_path() . '/.htaccess' );

		// Test a custom backup path
		$this->path->set_path( $this->custom_path );

		$this->path->calculate_path();

		$this->assertFileExists( Path::get_path() . '/index.html' );
		$this->assertFileExists( Path::get_path() . '/.htaccess' );

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
