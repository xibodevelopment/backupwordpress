<?php

namespace HM\BackUpWordPress;

class Extensions {

	private static $instance;

	function __construct( $root_url ) {

		$this->root_url  = $root_url;

	}

	public static function get_instance() {

		if ( ! static::$instance ) {

			$root_url = 'https://bwp.hmn.md/wp-json/wp/v2/';

			static::$instance = new static( $root_url );
		}

		return static::$instance;
	}

	function get_edd_data() {

		$response = $this->fetch( 'edd-downloads', 600 );

		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			return false;
		}

		return json_decode( $response['body'] );

	}

	function fetch( $endpoint, $ttl = 10 ) {

		$request_url = $this->root_url . $endpoint;

		$cache_key = md5( $request_url );

		$cached = get_transient( 'bwp_' . $cache_key );

		if ( $cached ) {
			return $cached;
		}

		$response = wp_remote_get( $request_url );

		set_transient( $cache_key, $response, $ttl );

		return $response;

	}

}
