<?php

/**
 * Tests for the complete backup process both with
 * the shell commands and with the PHP fallbacks
 *
 * @extends WP_UnitTestCase
 */
class testFullBackUpTestCase extends WP_UnitTestCase {

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
	 * @access public
	 * @return null
	 */
	function setUp() {

		$this->backup = new HM_Backup();
		$this->backup->excludes = 'wp-content/backups/';

		$this->markTestSkipped();

	}

	/**
	 * Cleanup the backup file and tmp directory
	 * after every test
	 *
	 * @access public
	 * @return null
	 */
	function tearDown() {

		if ( file_exists( $this->backup->archive_filepath() ) )
			unlink( $this->backup->archive_filepath() );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithZip() {

		if ( ! $this->backup->zip_command_path )
            $this->markTestSkipped( 'Empty zip command path' );

		if ( ! defined( 'RecursiveDirectoryIterator::FOLLOW_SYMLINK' ) )
			$this->markTestSkipped();

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->backup->root() ), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD, RecursiveDirectoryIterator::FOLLOW_SYMLINK );

		$excludes = $this->backup->exclude_string( 'regex' );

		foreach ( $files as $file ) {

			if ( ! $file->isReadable() )
			    continue;

			// Excludes
			if ( $excludes && preg_match( '(' . $excludes . ')', str_replace( $this->backup->root(), '', $this->backup->conform_dir( $file->getPathname() ) ) ) )
			    continue;

			$paths[] = str_replace( trailingslashit( $this->backup->root() ), '', $file->getPathname() );

		}

		$paths[] = $this->backup->database_dump_filename;

		$this->assertArchiveContains( $this->backup->archive_filepath(), $paths );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), count( $paths ) );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithZipArchive() {

		$this->backup->zip_command_path = false;

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->backup->root() ), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );

		$excludes = $this->backup->exclude_string( 'regex' );

		foreach ( $files as $file ) {

			if ( ! $file->isReadable() )
			    continue;

			// Excludes
			if ( $excludes && preg_match( '(' . $excludes . ')', str_replace( $this->backup->root(), '', $this->backup->conform_dir( $file->getPathname() ) ) ) )
			    continue;

			$paths[] = str_replace( trailingslashit( $this->backup->root() ), '', $file->getPathname() );

		}

		$paths[] = $this->backup->database_dump_filename;

		$this->assertArchiveContains( $this->backup->archive_filepath(), $paths );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), count( $paths ) );

	}

	/**
	 * Test a full backup with the shell commands
	 *
	 * @access public
	 * @return null
	 */
	function testFullBackupWithPclZip() {

		$this->backup->zip_command_path = false;
		$this->backup->skip_zip_archive = true;

		$this->backup->backup();

		$this->assertFileExists( $this->backup->archive_filepath() );

		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->backup->root() ), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );

		$excludes = $this->backup->exclude_string( 'regex' );

		foreach ( $files as $file ) {

			if ( ! $file->isReadable() )
			    continue;

			// Excludes
			if ( $excludes && preg_match( '(' . $excludes . ')', str_replace( $this->backup->root(), '', $this->backup->conform_dir( $file->getPathname() ) ) ) )
			    continue;

			$paths[] = str_replace( trailingslashit( $this->backup->root() ), '', $file->getPathname() );

		}

		$paths[] = $this->backup->database_dump_filename;

		$this->assertArchiveContains( $this->backup->archive_filepath(), $paths );
		$this->assertArchiveFileCount( $this->backup->archive_filepath(), count( $paths ) );

	}

}