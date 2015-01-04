<?php

/**
 * Tests that the conform_dir method
 * properly normalized various combinations of slashes
 *
 * @extends WP_UnitTestCase
 */
class testConformDirTestCase extends HM_Backup_UnitTestCase {

	/**
 	 * The correct dir
	 *
	 * @var string
	 * @access protected
	 */
	protected $dir;

	/**
	 * Contains the current backup instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $backup;

	public function setUp() {

		$this->backup = new HM_Backup;
		$this->dir = '/one/two/three';

	}

	public function testBackSlash() {

		$this->assertEquals( HM_Backup::conform_dir( $this->dir ), $this->dir );

	}

	public function testForwardSlash() {

		$this->assertEquals( HM_Backup::conform_dir( '\one\two\three' ), $this->dir );

	}

	public function testTrailingSlash() {

		$this->assertEquals( HM_Backup::conform_dir( '/one/two/three/' ), $this->dir );

	}

	public function testDoubleBackSlash() {

		$this->assertEquals( HM_Backup::conform_dir( '//one//two//three' ), $this->dir );

	}

	public function testDoubleForwardSlash() {

		$this->assertEquals( HM_Backup::conform_dir( '\\one\\two\\three' ), $this->dir );

	}

	public function testMixedSlashes() {

		$this->assertEquals( HM_Backup::conform_dir( '\/one\//\two\/\\three' ), $this->dir );

	}

}