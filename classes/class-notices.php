<?php

namespace HM\BackUpWordPress;

/**
 * Class for managing notices
 *
 * Notices are messages along with an associated context, by default
 * they are stored in the db and thus persist between page loads.
 */
class Notices {

	/**
	 * The array of notice messages
	 *
	 * This is a multidimensional array of
	 * `array( context => array( 'message' ) );``
	 *
	 * @var array
	 */
	private $notices = array();

	/**
	 * Contains the instantiated Notices instance
	 *
	 * @var Notices $this->get_instance
	 */
	private static $instance;

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning of the instance of the *Singleton* instance.
	 */
	public function __clone() { throw new \Exception('may not be cloned'); }

	/**
	 * Prevent unserializing of the *Singleton* instance.
	 */
	public function __wakeup() { throw new \Exception('may not be serialized'); }

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @staticvar Notices $instance The *Singleton* instances of this class.
	 *
	 * @return Notices The *Singleton* instance.
	 */
	public static function get_instance() {

		if ( ! ( self::$instance instanceof Notices ) ) {
			self::$instance = new Notices();
		}

		return self::$instance;

	}

	/**
	 * Set an array of notice messages for a specific context
	 *
	 * @param string  $context    The context of the notice message
	 * @param array   $messages   The array of messages
	 * @param boolean $persistent Whether the notice should persist via the database. Defaults to true.
	 */
	public function set_notices( $context, array $messages, $persistent = true ) {

		// Clear any empty messages and avoid duplicates
		$messages = array_unique( array_filter( $messages ) );

		if ( empty( $context ) || empty( $messages ) ) {
			return false;
		}

		$this->notices[ $context ] = array_merge( $this->get_notices( $context ), $messages );

		if ( $persistent ) {

			$new_notices = $notices = $this->get_persistent_notices();

			// Make sure we merge new notices into to any existing notices
			if ( ! empty( $notices[ $context ] ) ) {
				$new_notices[ $context ] = array_unique( array_merge( $new_notices[ $context ], $messages ) );
			} else {
				$new_notices[ $context ] = $messages;
			}

			// Only update the database if the notice array has changed
			if ( $new_notices !== $notices ) {
				update_option( 'hmbkp_notices', $new_notices );
			}
		}

		return true;

	}

	/**
	 * Fetch an array of notices messages
	 *
	 * If you specify a context then you'll just get messages for that context otherwise
	 * you get multidimensional array of all contexts and their messages.
	 *
	 * @param string $context The context that you'd like the messages for
	 *
	 * @return array The array of notice messages
	 */
	public function get_notices( $context = '' ) {

		$notices = $this->get_all_notices();

		if ( ! empty( $notices ) ) {

			if ( ! empty( $context ) ) {
				return isset( $notices[ $context ] ) ? $notices[ $context ] : array();
			}

			return $notices;
		}

		return array();

	}

	/**
	 * Get both standard and persistent notices
	 *
	 * @return array The array of contexts and notices
	 */
	private function get_all_notices() {
		return array_map( 'array_unique', array_merge_recursive( $this->notices, $this->get_persistent_notices() ) );
	}

	/**
	 * Load the persistent notices from the database
	 *
	 * @return array The array of notices
	 */
	private function get_persistent_notices() {
		$notices = get_option( 'hmbkp_notices' );
		return ! empty( $notices ) ? $notices : array();
	}

	/**
	 * Clear all notices including persistent ones
	 */
	public function clear_all_notices() {
		$this->notices = array();
		delete_option( 'hmbkp_notices' );
	}
}
