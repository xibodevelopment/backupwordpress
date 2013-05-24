<?php
/**
 * Bootstrap the testing environment
 * Uses wordpress tests (http://github.com/nb/wordpress-tests/) which uses PHPUnit
 * @package wordpress-plugin-tests
 *
 * Usage: change the below array to any plugin(s) you want activated during the tests
 *        value should be the path to the plugin relative to /wp-content/
 *
 * Note: Do note change the name of this file. PHPUnit will automatically fire this file when run.
 *
 */

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'backupwordpress/backupwordpress.php' ),
);

require dirname( __FILE__ ) . '/lib/bootstrap.php';

class HM_Backup_UnitTestCase extends WP_UnitTestCase{

	/**
	 * Assert that a zip archive contains the array
	 * of filenames
	 *
	 * @access public
	 * @param string path to zip file
	 * @param array of filenames to check for
	 * @return null
	 */
	function assertArchiveContains( $zip_file, $filepaths, $root = ABSPATH ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$files = array();

		foreach( $filepaths as $filepath )
			$filenames[] = str_ireplace( trailingslashit( $root ), '', $filepath );

		foreach( $extracted as $fileInfo )
			$files[] = untrailingslashit( $fileInfo['filename'] );

		foreach( $filenames as $filename )
			$this->assertContains( untrailingslashit( $filename ), $files );

	}

	/**
	 * Assert that a zip archive doesn't contain any of the files
	 * in the array of filenames
	 *
	 * @access public
	 * @param string path to zip file
	 * @param array of filenames to check for
	 * @return null
	 */
	function assertArchiveNotContains( $zip_file, $filenames ) {

		$extracted = $this->pclzip_extract_as_string( $zip_file );

		$files = array();

		foreach( $extracted as $fileInfo )
			$files[] = $fileInfo['filename'];

		foreach( $filenames as $filename )
			$this->assertNotContains( $filename, $files );

	}

	/**
	 * Assert that a zip archive contains the
	 * correct number of files
	 *
	 * @access public
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

		if ( isset( $previous_encoding ) )
			mb_internal_encoding( $previous_encoding );

		return $extracted ?: array();

	}

}