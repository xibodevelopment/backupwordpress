<?php

namespace HM\BackUpWordPress;

class IMysqldump_Database_Backup_Engine_Tests extends Common_Database_Backup_Engine_Tests {

	protected $backup;

	public function setUp() {
		$this->backup = new IMysqldump_Database_Backup_Engine;
        parent::setUp();
	}

}