<?php

namespace HM\BackUpWordPress;

class Mock_Database_Backup_Engine extends Database_Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function backup() {
		return true;
	}

}

class Mock_Failing_Database_Backup_Engine extends Database_Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function backup() {
		return false;
	}

}

class Mock_File_Backup_Engine extends File_Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function backup() {
		return true;
	}

}

class Mock_Backup_Engine extends Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function backup() {
		return true;
	}

	public function verify_backup() {
		return true;
	}

}

class Mock_Failing_Backup_Engine extends Backup_Engine {

	public function __construct() {
		parent::__construct();
	}

	public function backup() {
		return false;
	}

	public function verify_backup() {
		return false;
	}

}