<?php

class HM_Backup_UnitTestCase extends WP_UnitTestCase {

	protected $test_root = __DIR__;

	/**
	 * Assert that a zip archive exactly matches the array
	 * of filenames
	 *
	 * @param string path to zip file
	 * @param array of filenames to check for
	 * @return null
	 */
	function assertArchiveEquals( $zip_file, $filepaths, $root = ABSPATH ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$files = array();

		foreach( $filepaths as $filepath ) {
			$filenames[] = str_ireplace( trailingslashit( $root ), '', wp_normalize_path( (string) $filepath ) );
		}

		foreach( $extracted as $fileInfo ) {
			$files[] = untrailingslashit( $fileInfo['filename'] );
		}

		$this->assertEquals( $filenames, $files );

	}

	/**
	 * Assert that a zip archive doesn't match the array of filenames
	 *
	 * @param string path to zip file
	 * @param array of filenames to check for
	 * @return null
	 */
	function assertArchiveNotEquals( $zip_file, $filenames ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$files = array();

		foreach( $extracted as $fileInfo ) {
			$files[] = $fileInfo['filename'];
		}

		$this->assertNotEquals( $filenames, $files );

	}

	/**
	 * Assert that a zip archive contains the array
	 * of filenames
	 *
	 * @param string path to zip file
	 * @param array of filenames to check for
	 * @return null
	 */
	function assertArchiveContains( $zip_file, $filepaths, $root = ABSPATH ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$files = array();

		foreach( $filepaths as $filepath ) {
			$filenames[] = str_ireplace( trailingslashit( $root ), '', wp_normalize_path( (string) $filepath ) );
		}

		foreach( $extracted as $fileInfo ) {
			$files[] = untrailingslashit( wp_normalize_path( $fileInfo['filename'] ) );
		}

		foreach( $filenames as $filename ) {
			$this->assertContains( $filename, $files );
		}

	}

	/**
	 * Assert that a zip archive doesn't contain any of the files
	 * in the array of filenames
	 *
	 * @param string path to zip file
	 * @param array of filenames to check for
	 * @return null
	 */
	function assertArchiveNotContains( $zip_file, $filenames ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$files = array();

		foreach( (array) $extracted as $fileInfo ) {
			$files[] = wp_normalize_path( $fileInfo['filename'] );
		}

		foreach( $filenames as $filename ) {
			$this->assertNotContains( $filename, $files );
		}


	}

	/**
	 * Assert that a zip archive contains the
	 * correct number of files
	 *
	 * @param string path to zip file
	 * @param int the number of files the archive should contain
	 * @return null
	 */
	function assertArchiveFileCount( $zip_file, $file_count ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$this->assertEquals( count( array_filter( (array) $extracted ) ), $file_count );

	}

	private function pclzip_extract_as_string( $zip_file ) {

		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

		if ( ini_get( 'mbstring.func_overload' ) && function_exists( 'mb_internal_encoding' ) ) {
			$previous_encoding = mb_internal_encoding();
			mb_internal_encoding( 'ISO-8859-1' );
		}

		$archive = new PclZip( $zip_file );

		$extracted = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING );

		if ( isset( $previous_encoding ) ) {
			mb_internal_encoding( $previous_encoding );
		}

		return $extracted;

	}

	protected function setup_test_data() {

		$this->test_data = wp_normalize_path( __DIR__ ) . '/test-data';
		$this->test_data_symlink = wp_normalize_path( __DIR__ ) . '/test-data-symlink';

		$this->cleanup_test_data();

		mkdir( $this->test_data );
		mkdir( $this->test_data . '/exclude' );
		file_put_contents( $this->test_data . '/test-data.txt', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla adipiscing tincidunt dictum. Cras sed elit in ligula volutpat egestas et ac ipsum. Maecenas vitae lorem nulla, vel lacinia ligula. Duis varius nibh consequat dui lacinia tempor eu eu ipsum. Cras gravida metus ut elit eleifend mattis. Cras porta dignissim elit, at tincidunt ante pellentesque vitae. Nam dictum dapibus arcu, vitae tincidunt nunc semper eu. Pellentesque ornare interdum arcu, sit amet molestie orci malesuada a. Morbi ac lacus a lorem consectetur auctor. Suspendisse facilisis nisi vitae nisi convallis a blandit odio imperdiet. Ut lobortis luctus lacinia. Maecenas malesuada ultrices dui.' );
		file_put_contents( $this->test_data . '/exclude/exclude.exclude', '' );

		mkdir( $this->test_data_symlink );
		file_put_contents( $this->test_data_symlink . '/test-data-symlink.txt', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla adipiscing tincidunt dictum. Cras sed elit in ligula volutpat egestas et ac ipsum. Maecenas vitae lorem nulla, vel lacinia ligula. Duis varius nibh consequat dui lacinia tempor eu eu ipsum. Cras gravida metus ut elit eleifend mattis. Cras porta dignissim elit, at tincidunt ante pellentesque vitae. Nam dictum dapibus arcu, vitae tincidunt nunc semper eu. Pellentesque ornare interdum arcu, sit amet molestie orci malesuada a. Morbi ac lacus a lorem consectetur auctor. Suspendisse facilisis nisi vitae nisi convallis a blandit odio imperdiet. Ut lobortis luctus lacinia. Maecenas malesuada ultrices dui.' );

	}

	protected function cleanup_test_data() {
		HM\BackUpWordPress\rmdirtree( $this->test_data );
		HM\BackUpWordPress\rmdirtree( $this->test_data_symlink );
	}

	/**
	 * Provide a backwards compatible version of assertNotWPError for old versions of WordPress
	 */
	public function assertNotWPError( $actual, $message = '' ) {

		if ( is_callable( 'parent::assertNotWPError' ) ) {
			return parent::assertNotWPError( $actual, $message );
		}

		if ( is_wp_error( $actual ) && '' === $message ) {
			$message = $actual->get_error_message();
		}
		$this->assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

}
