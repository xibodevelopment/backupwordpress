<?php

/**
 * Tests for excludes logic of the back up
 * files process
 *
 * @extends WP_UnitTestCase
 */
class testExcludesTestCase extends HM_Backup_UnitTestCase {

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	/**
	 * Setup the backup object and create the tmp directory
	 *
	 */
	public function setUp() {

		HM\BackUpWordPress\Path::get_instance()->set_path( dirname( __FILE__ ) . '/tmp' );

		$this->backup = new HM\BackUpWordPress\Backup();
		$this->backup->set_root( dirname( __FILE__ ) . '/test-data/' );

		wp_mkdir_p( hmbkp_path() );

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 */
	public function tearDown() {

		hmbkp_rmdirtree( hmbkp_path() );

		unset( $this->backup );

		HM\BackUpWordPress\Path::get_instance()->reset_path();

	}

	public function testBackUpDirIsExcludedWhenBackUpDirIsNotInRoot() {

		$this->assertNotContains( $this->backup->get_root(), hmbkp_path() );
		$this->assertEmpty( $this->backup->get_excludes() );

	}

	public function testBackUpDirIsExcludedWhenBackUpDirIsInRoot() {

		HM\BackUpWordPress\Path::get_instance()->set_path( dirname( __FILE__ ) . '/test-data/tmp' );

		$this->assertContains( $this->backup->get_root(), hmbkp_path() );
		$this->assertNotEmpty( $this->backup->get_excludes() );
		$this->assertContains( trailingslashit( hmbkp_path() ), $this->backup->get_excludes() );

	}

	public function testExcludeAbsoluteDirPathWithZip() {

		$this->backup->set_excludes( '/exclude/' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
			$this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsoluteDirPathWithPclZip() {

		$this->backup->set_excludes( '/exclude/' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsoluteRootDirPathWithZip() {

		$this->backup->set_excludes( dirname( __FILE__ ) . '/test-data/exclude/' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsoluteRootDirPathWithPclZip() {

		$this->backup->set_excludes( dirname( __FILE__ ) . '/test-data/exclude/' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeDirPathFragmentWithZip() {

		$this->backup->set_excludes( 'exclude/' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeDirPathFragmentWithPclZip() {

		$this->backup->set_excludes( 'exclude/' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsoluteDirPathWithZip() {

		$this->backup->set_excludes( 'exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsoluteDirPathWithPclZip() {

		$this->backup->set_excludes( 'exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 1 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsoluteFilePathWithZip() {

		$this->backup->set_excludes( '/exclude/exclude.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

        $this->backup->zip();
        $this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsoluteFilePathWithPclZip() {

		$this->backup->set_excludes( '/exclude/exclude.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsoluteFilePathWithZip() {

		$this->backup->set_excludes( 'exclude/exclude.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

//	public function testExcludeAmbiguousAbsoluteFilePathWithPclZip() {
//
//		$this->backup->set_excludes( 'exclude/exclude.exclude' );
//		$this->backup->set_type( 'file' );
//
//		$this->backup->pcl_zip();
//		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );
//
//		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
//		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );
//
//		$this->assertEmpty( $this->backup->get_warnings() );
//
//	}

	public function testExcludeAbsolutePathWithWildcardFileWithZip() {

		$this->backup->set_excludes( '/exclude/*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( '/exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsolutePathWithWildcardFileWithPclZip() {

		$this->backup->set_excludes( '/exclude/*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( '/exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileWithZip() {

		$this->backup->set_excludes( 'exclude/*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileWithPclZip() {

		$this->backup->set_excludes( 'exclude/*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeWildcardFileNameWithZip() {

		$this->backup->set_excludes( '*.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeWildcardFileNameWithPclZip() {

		$this->backup->set_excludes( '*.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsolutePathWithWildcardFileNameWithZip() {

		$this->backup->set_excludes( '/exclude/*.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsolutePathWithWildcardFileNameWithPclZip() {

		$this->backup->set_excludes( '/exclude/*.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithZip() {

		$this->backup->set_excludes( 'exclude/*.exclude' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileNameWithPclZip() {

		$this->backup->set_excludes( 'exclude/*.exclude' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeWildcardFileExtensionWithZip() {

		$this->backup->set_excludes( 'exclude.*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeWildcardFileExtensionWithPclZip() {

		$this->backup->set_excludes( 'exclude.*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsolutePathWithWildcardFileExtensionWithZip() {

		$this->backup->set_excludes( '/exclude/exclude.*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAbsolutePathWithWildcardFileExtensionWithPclZip() {

		$this->backup->set_excludes( '/exclude/exclude.*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( '/exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithZip() {

		$this->backup->set_excludes( 'exclude/exclude.*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testExcludeAmbiguousAbsolutePathWithWildcardFileExtensionWithPclZip() {

		$this->backup->set_excludes( 'exclude/exclude.*' );
		$this->backup->set_type( 'file' );

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveNotContains( $this->backup->get_archive_filepath(), array( 'exclude/exclude.exclude' ) );
		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 2 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

	public function testWildCardWithZip() {

		$this->backup->set_excludes( '*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->zip();
		$this->assertEquals( 'zip', $this->backup->get_archive_method() );

		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 0 );

		// Expect an error "Nothing to do"
		$this->assertNotEmpty( $this->backup->get_warnings() );

	}

	public function testWildCardWithPclZip() {

		$this->backup->set_excludes( '*' );
		$this->backup->set_type( 'file' );

		if ( ! $this->backup->get_zip_command_path() ) {
            $this->markTestSkipped( "Empty zip command path" );
		}

		$this->backup->pcl_zip();
		$this->assertEquals( 'pclzip', $this->backup->get_archive_method() );

		$this->assertArchiveFileCount( $this->backup->get_archive_filepath(), 0 );

		$this->assertEmpty( $this->backup->get_warnings() );

	}

}