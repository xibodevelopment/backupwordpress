<?php

namespace HM\BackUpWordPress;

class testPathInOpenBasedir extends \HM_Backup_UnitTestCase {

    function ini_get_mock() {
      return $this->basedir;
    }

    public function test_empty_basedir() {

      $this->basedir = '';
      $this->assertTrue( path_in_php_open_basedir( __DIR__, array( $this, 'ini_get_mock' ) ) );

    }

    public function test_not_in_basedir() {

      $this->basedir = 'foobarbaz';
      $this->assertFalse( path_in_php_open_basedir( __DIR__, array( $this, 'ini_get_mock' ) ) );

    }

    public function test_is_basedir() {

      $this->basedir = __DIR__;
      $this->assertTrue( path_in_php_open_basedir( __DIR__, array( $this, 'ini_get_mock' ) ) );

    }

    public function test_in_basedir() {

      $this->basedir = dirname( __DIR__ );
      $this->assertTrue( path_in_php_open_basedir( __DIR__, array( $this, 'ini_get_mock' ) ) );

    }

    public function test_deep_in_basedir() {

      $this->basedir = dirname( dirname( dirname( __DIR__ ) ) );
      $this->assertTrue( path_in_php_open_basedir( __DIR__, array( $this, 'ini_get_mock' ) ) );

    }

    public function test_multiple_basedir() {

      $this->basedir = 'foobarbaz:' . __DIR__;
      $this->assertTrue( path_in_php_open_basedir( __DIR__, array( $this, 'ini_get_mock' ) ) );

    }

    public function test_preceding_basedir() {

      $this->basedir = 'foobarbaz' . dirname( __DIR__ );
      $this->assertFalse( path_in_php_open_basedir( __DIR__, array( $this, 'ini_get_mock' ) ) );

    }


}
