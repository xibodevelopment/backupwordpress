<?php

namespace HM\BackUpWordPress;

class IMysqldump_Database_Backup_Engine_Tests extends \Database_Backup_Engine_Common_Tests {

	protected $backup;

	public function setUp() {
		$this->backup = new IMysqldump_Database_Backup_Engine;
	}

}