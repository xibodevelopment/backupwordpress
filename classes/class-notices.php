<?php

namespace HM\BackUpWordPress;

/**
 * Class Notices
 */
class Notices {

	/**
	 * @var
	 */
	private static $_instance;

	private $notices;

	/**
	 *
	 */
	private function __construct() {}

	/**
	 * @return Notices
	 */
	public static function get_instance() {

		if ( ! ( self::$_instance instanceof Notices ) ) {
			self::$_instance = new Notices();
		}

		return self::$_instance;

	}

	/**
	 * @param string $context
	 * @param array $messages
	 * @param bool $persistant 		whether to save the notices to the database
	 *
	 * @return mixed|void
	 */
	public function set_notices( $context, array $messages, $persistant = true ) {

		$this->notices[ $context ] = $messages;

		if ( $persistant ) {
			$notices = get_option( 'hmbkp_notices' );
			$notices[ $context ] = $messages;
			update_option( 'hmbkp_notices', $notices );
		}

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

		$notices = $this->get_all_notices();

		if ( $notices ) {

			if ( $context && isset( $notices[ $context ] ) ) {
				return $notices[ $context ];
			}

			return $notices;
		}

		return array();

	}

	private function get_all_notices() {
		return array_merge_recursive( (array) $this->notices, (array) get_option( 'hmbkp_notices' ) );
	}

	/**
	 * Delete all notices from the DB.
	 */
	public function clear_all_notices() {

		$this->notices = array();

		delete_option( 'hmbkp_notices' );

	}

}
