<?php

namespace HM\BackUpWordPress;

class Extensions_Tests extends \HM_Backup_UnitTestCase {

	private $extensions;

	public function setUp() {
		parent::setUp();
		$this->extensions = Extensions::get_instance();
	}

	public function tearDown() {

	}

	public function test_instance() {
		$instance = Extensions::get_instance();

		$this->assertInstanceOf( 'HM\BackUpWordPress\Extensions', $instance );
	}

	public function test_fetch_api_data() {
		add_filter( 'pre_http_request', $this->get_http_request_overide( 'https://bwp.hmn.md/wp-json/wp/v2/edd-downloads', file_get_contents( __DIR__ . '/data/response.json' )
		), 10, 3 );

		$extensions_data = $this->extensions->get_edd_data();

		$this->assertInternalType( 'array', $extensions_data );
		$this->assertGreaterThan( 0, count( $extensions_data ) );
	}

	private function get_http_request_overide( $matched_url, $response_body ) {

		$func = null;

		return $func = function( $return, $request, $url ) use ( $matched_url, $response_body, &$func ) {

			remove_filter( 'pre_http_request', $func );

			if ( $url !== $matched_url ) {
				return $return;
			}

			$response = array(
				'headers'  => array(),
				'body'     => $response_body,
				'response' => 200,
			);

			return $response;
		};

	}
}
