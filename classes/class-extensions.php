<?php

namespace HM\BackUpWordPress;

/**
 * Class Extensions
 * @package HM\BackUpWordPress
 */
final class Extensions {

	/**
	 * Contains the instantiated Extensions instance.
	 *
	 * @var Extensions $this->instance
	 */
	private static $instance;

	/**
	 * Holds the root URL of the API.
	 *
	 * @var string
	 */
	protected $root_url = '';

	/**
	 * Extensions constructor.
	 *
	 */
	private function __construct() {

		$this->root_url  = 'https://bwp.hmn.md/wp-json/wp/v2/';

	}

	public function __wakeup() { throw new \Exception('may not be serialized'); }

	public function __clone() { throw new \Exception('may not be cloned'); }

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @staticvar Extensions $instance The *Singleton* instances of this class.
	 *
	 * @return Extensions The *Singleton* instance.
	 */
	public static function get_instance() {

		if ( ! ( self::$instance instanceof Extensions ) ) {

			self::$instance = new Extensions();

		}

		return self::$instance;

	}

	/**
	 * Parses the body of the API response and returns it.
	 *
	 * @return array|bool|mixed|object
	 */
	public function get_edd_data() {

		$response = $this->fetch( 'edd-downloads' );

		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			return false;
		}

		return json_decode( $response['body'] );

	}

	/**
	 * Makes a request to the JSON API or retrieves the cached response. Caches the response for one day.
	 *
	 * @param $endpoint
	 * @param int $ttl
	 *
	 * @return array|mixed|\WP_Error
	 */
	protected function fetch( $endpoint, $ttl = DAY_IN_SECONDS ) {

		$request_url = $this->root_url . $endpoint;

		$cache_key = md5( $request_url );

		$cached = get_transient( 'bwp_' . $cache_key );

		if ( $cached ) {
			return $cached;
		}

		$response = wp_remote_get( $request_url );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new \WP_Error( 'hmbkp-error', 'Unable to fetch API response' );
		}

		set_transient( 'bwp_' . $cache_key, $response, $ttl );

		return $response;

	}

}
