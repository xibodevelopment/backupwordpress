<?php

namespace HM\BackUpWordPress;

class Database_Backup_Engine_Tests extends \HM_Backup_UnitTestCase {

	function setUp() {
		$this->db = new Mock_Database_Backup_Engine;
	}

	function test_db_host_localhost() {

		$this->db->parse_db_host_constant( 'localhost' );

		$this->assertEquals( 'localhost', $this->db->get_host() );
		$this->assertEmpty( $this->db->get_port() );
		$this->assertEmpty( $this->db->get_socket() );

	}

	function test_db_host_localhost_port() {

		$this->db->parse_db_host_constant( 'localhost:3306' );

		$this->assertEquals( 'localhost', $this->db->get_host() );
		$this->assertEquals( 3306, $this->db->get_port() );
		$this->assertEmpty( $this->db->get_socket() );

	}


	function test_db_host_localhost_socket() {

		$this->db->parse_db_host_constant( 'localhost:/tmp/mysql5.sock' );

		$this->assertEquals( 'localhost', $this->db->get_host() );
		$this->assertEmpty( $this->db->get_port() );
		$this->assertEquals( '/tmp/mysql5.sock', $this->db->get_socket() );

	}

	function test_db_host_url() {

		$this->db->parse_db_host_constant( 'mysqlXY-AB.wcN.dfQ.stabletransit.com' );

		$this->assertEquals( 'mysqlXY-AB.wcN.dfQ.stabletransit.com', $this->db->get_host() );
		$this->assertEmpty( $this->db->get_port() );
		$this->assertEmpty( $this->db->get_socket() );

	}

	function test_db_host_ip() {

		$this->db->parse_db_host_constant( '127.0.0.1' );

		$this->assertEquals( '127.0.0.1', $this->db->get_host() );
		$this->assertEmpty( $this->db->get_port() );
		$this->assertEmpty( $this->db->get_socket() );

	}

}