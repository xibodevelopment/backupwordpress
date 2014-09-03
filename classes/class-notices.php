<?php

/**
 * Class HMBKP_Notices
 */
class HMBKP_Notices {

	/**
	 * @var
	 */
	private static $_instance;

	/**
	 *
	 */
	private function __construct() {}

	/**
	 * @return HMBKP_Notices
	 */
	public static function get_instance() {

		if ( ! ( self::$_instance instanceof HMBKP_Notices ) ) {
			self::$_instance = new HMBKP_Notices();
		}
		return self::$_instance;
	}

	/**
	 * @param string $context
	 * @param array $messages
	 *
	 * @return mixed|void
	 */
	public function set_notices( $context, array $messages ) {

		$all_notices = get_option( 'hmbkp_notices' );

		$all_notices[ $context ] = $messages;

		update_option( 'hmbkp_notices', $all_notices );

		return get_option( 'hmbkp_notices' );
	}

	/**
	 * Fetch the notices for the context.
	 * All notices by default.
	 *
	 * @param string $context
	 *
	 * @return array|mixed|void
	 */
	public function get_notices( $context = '' ) {

		if ( $all_notices = get_option( 'hmbkp_notices' ) ) {

			if ( 0 < trim( strlen( $context ) ) ) {
				return $all_notices[ $context ];
			}

			return $all_notices;
		}

		return array();

	}

	/**
	 * Delete all notices from the DB.
	 */
	public function clear_all_notices() {
		return delete_option( 'hmbkp_notices' );
	}
}
