<?php

namespace HM\BackUpWordPress;

class Is_Same_Size_Format_Tests extends \HM_Backup_UnitTestCase {

	public function test_both_same_size() {
		$this->assertTrue( is_same_size_format( 22000000, 22000000 ) );
	}

	public function test_not_both_same_size() {
		$this->assertFalse( is_same_size_format( 22000, 22000000 ) );
	}

	public function test_both_strings() {
		$this->assertFalse( is_same_size_format( '22', '22' ) );
	}
}
