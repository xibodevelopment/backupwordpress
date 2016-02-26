<?php

namespace HM\BackUpWordPress;

class Pclzip_File_Backup_Engine_Tests extends Common_File_Backup_Engine_Tests {

	protected $backup;

	public function setUp() {
		$this->backup = new Pclzip_File_Backup_Engine;
		parent::setUp();
	}
}
