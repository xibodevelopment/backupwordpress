<?php

namespace HM\BackUpWordPress;

/**
 * Webhook notifications for backups on WPRemote
 *
 * @extends Service
 */
class WPRemote_Webhook_Service extends Webhook_Service {

	/**
	 * Human readable name for this service
	 * @var string
	 */
	public $name = 'WP Remote Webhook';

	private $wpremote_webhook_url = 'http://wpremote.com/api/json/backupwordpress/webhook';

	/**
	 * Not in use
	 *
	 * @see  field
	 * @return null
	 */
	public function form() {}

	/**
	 * Not in use
	 *
	 * @return null
	 */
	public function field() {}

	/**
	 * Not in use
	 *
	 * @return null
	 */
	public function display() {}

	/**
	 * Not in use
	 * $return null
	 */
	public function update( &$new_data, $old_data ) {}

	/**
	 * Used to determine if the service is in use or not
	 */
	public function is_service_active() {
		return strlen( $this->get_url() ) > 0;
	}

	/**
	 * @return string
	 */
	protected function get_url() {
		return ( defined( 'WPRP_PLUGIN_SLUG' ) && get_option( 'wpr_api_key' ) ) ? $this->wpremote_webhook_url : false;
	}

	/**
	 * @return string
	 */
	protected function get_secret_key() {
		return get_option( 'wpr_api_key' );
	}

}

// Register the service
Services::register( __FILE__, 'HM\BackUpWordPress\WPRemote_Webhook_Service' );