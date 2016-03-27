<?php

namespace HM\BackUpWordPress;

class Notice_Tests extends \HM_Backup_UnitTestCase {

	public function setUp() {
		$this->notices = Notices::get_instance();
	}

	public function tearDown() {
		$this->notices->clear_all_notices();
		$this->reset_notices();
	}

	public function test_no_notices_no_context() {
		$this->assertEquals( array(), $this->notices->get_notices() );
	}

	public function test_no_notices_context() {
		$this->assertEquals( array(), $this->notices->get_notices( 'foo' ) );
	}

	public function test_set_single_notice_with_context() {
		$this->notices->set_notices( 'foo', array( 'bar' ), false );
		$this->assertEquals( array( 'bar' ), $this->notices->get_notices( 'foo' ) );
	}

	public function test_get_notices_with_wrong_context() {
		$this->notices->set_notices( 'foo', array( 'bar' ), false );
		$this->assertEquals( array(), $this->notices->get_notices( 'bar' ) );
	}

	public function test_empty_context() {
		$this->notices->set_notices( '', array( 'bar' ), false );
		$this->assertEquals( array(), $this->notices->get_notices() );
	}

	public function test_empty_message() {
		$this->notices->set_notices( 'foo', array( '' ), false );
		$this->assertEquals( array(), $this->notices->get_notices() );
	}

	public function test_set_multiple_notice_with_context() {
		$this->notices->set_notices( 'foo', array( 'bar' ), false );
		$this->notices->set_notices( 'fizz', array( 'buzz' ), false );
		$this->assertEquals( array( 'bar' ), $this->notices->get_notices( 'foo' ) );
		$this->assertEquals( array( 'buzz' ), $this->notices->get_notices( 'fizz' ) );
		$this->assertEquals( array( 'foo' => array( 'bar' ), 'fizz' => array( 'buzz' ) ), $this->notices->get_notices() );
	}

	public function test_set_multiple_notices_in_same_context() {
		$this->notices->set_notices( 'foo', array( 'bar' ), false );
		$this->notices->set_notices( 'foo', array( 'baz' ), false );
		$this->assertEquals( array( 'bar', 'baz' ), $this->notices->get_notices( 'foo' ) );
	}

	public function test_set_multiple_persistant_notices_in_same_context() {
		$this->notices->set_notices( 'foo', array( 'bar' ), true );
		$this->notices->set_notices( 'foo', array( 'baz' ), true );
		$this->reset_notices();
		$this->assertEquals( array( 'bar', 'baz' ), $this->notices->get_notices( 'foo' ) );
	}

	public function test_set_duplicate_notice() {
		$this->notices->set_notices( 'foo', array( 'bar' ), false );
		$this->notices->set_notices( 'foo', array( 'bar' ), false );
		$this->assertEquals( array( 'bar' ), $this->notices->get_notices( 'foo' ) );
	}

	public function test_set_duplicate_notices_different_contexts() {
		$this->notices->set_notices( 'foo', array( 'bar' ), false );
		$this->notices->set_notices( 'baz', array( 'bar' ), false );
		$this->assertEquals( array( 'foo' => array( 'bar' ), 'baz' => array( 'bar' ) ), $this->notices->get_notices() );
	}

	public function test_clear_notices() {
		$this->notices->set_notices( 'foo', array( 'bar' ), false );
		$this->notices->clear_all_notices();
		$this->assertEquals( array(), $this->notices->get_notices( 'bar' ) );
		$this->assertEquals( array(), $this->notices->get_notices() );
	}

	public function test_persistant_notice() {
		$this->notices->set_notices( 'foo', array( 'bar' ), true );
		$this->reset_notices();
		$this->assertEquals( array( 'bar' ), $this->notices->get_notices( 'foo' ) );

	}

	public function test_set_persistant_single_notice_with_context() {
		$this->notices->set_notices( 'foo', array( 'bar' ), true );
		$this->reset_notices();
		$this->assertEquals( array( 'bar' ), $this->notices->get_notices( 'foo' ) );
	}

	public function test_get_persistant_notices_with_wrong_context() {
		$this->notices->set_notices( 'foo', array( 'bar' ), true );
		$this->reset_notices();
		$this->assertEquals( array(), $this->notices->get_notices( 'bar' ) );
	}

	public function test_set_persistant_multiple_notice_with_context() {
		$this->notices->set_notices( 'foo', array( 'bar' ), true );
		$this->notices->set_notices( 'fizz', array( 'buzz' ), true );
		$this->reset_notices();
		$this->assertEquals( array( 'bar' ), $this->notices->get_notices( 'foo' ) );
		$this->assertEquals( array( 'buzz' ), $this->notices->get_notices( 'fizz' ) );
		$this->assertEquals( array( 'foo' => array( 'bar' ), 'fizz' => array( 'buzz' ) ), $this->notices->get_notices() );
	}

	private function reset_notices() {
		$reflection = new \ReflectionClass( Notices::get_instance() );
		$instance = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
		$instance->setAccessible( false );
		$this->notices = Notices::get_instance();
	}
}
