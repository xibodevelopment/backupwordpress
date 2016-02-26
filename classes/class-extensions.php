<?php

namespace HM\BackUpWordPress;

class Extensions {

	const EXTENSIONS_DATA = 'hmbkp_extensions_data';

	protected $extensions_data = '';

	private static $_instance;

	public static function get_instance() {

		if ( ! ( self::$_instance instanceof Extensions ) ) {
			self::$_instance = new Extensions();
		}

		return self::$_instance;

	}

	private function __construct() {}

	public function fetch_data() {

		if ( false === get_transient( self::EXTENSIONS_DATA ) ) {
			$response = wp_remote_get( 'https://bwp.hmn.md/wp-json/wp/v2/edd-downloads' );
		}

		if ( is_array( $response ) ) {
			$header = $response['headers'];
			$body = $response['body'];
		}

		if ( ! empty( $body ) ) {
			return json_decode( $body );
		}
	}
}
