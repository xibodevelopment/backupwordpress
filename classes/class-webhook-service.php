<?php

namespace HM\BackUpWordPress;

/**
 * Webhook notifications for backups
 *
 * @extends Service
 */
abstract class Webhook_Service extends Service {

	/**
	 * Human readable name for this service
	 * @var string
	 */
	public $name = 'Webhook';

	/**
	 * @return string
	 */
	abstract protected function get_secret_key();

	/**
	 * @return string
	 */
	abstract protected function get_url();

	/**
	 * Fire the webhook notification on the hmbkp_backup_complete
	 *
	 * @see  Backup::do_action
	 * @param  string $action The action received from the backup
	 * @return void
	 */
	public function action( $action, Backup $backup ) {

		if ( 'hmbkp_backup_complete' !== $action || ! $this->is_service_active() ) {
			return;
		}

		$webhook_url = $this->get_url();
		$file        = $backup->get_backup_filepath();
		$download    = add_query_arg( 'hmbkp_download', base64_encode( $file ), HMBKP_ADMIN_URL );
		$domain      = parse_url( home_url(), PHP_URL_HOST ) . parse_url( home_url(), PHP_URL_PATH );

		// The backup failed, send a message saying as much
		if ( ! file_exists( $file ) && ( $errors = array_merge( $backup->get_errors(), $backup->get_warnings() ) ) ) {

			$error_message = '';

			foreach ( $errors as $error_set ) {
				$error_message .= implode( "\n - ", $error_set );
			}

			if ( $error_message ) {
				$error_message = ' - ' . $error_message;
			}

			$subject = sprintf( __( 'Backup of %s Failed', 'backupwordpress' ), $domain );

			$body = array(
				'type'         => 'backup.error',
				'site_url'     => site_url(),
				'backup'       => array(
					'id'           => 'backup_' . pathinfo( $file, PATHINFO_FILENAME ),
					'start'        => '0',
					'end'          => '0',
					'download_url' => '',
					'type'         => $this->schedule->get_type(),
					'status'       => array(
						'message'      => $subject . ' - ' . $error_message,
						'success'      => '0',
					),
				),
			);

		} else {

			$body = array(
				'type'         => 'backup.success',
				'site_url'     => site_url(),
				'backup'      => array(
					'id'           => 'backup_' . $this->schedule->get_id(),
					'start'        => '0',
					'end'          => '0',
					'download_url' => $download,
					'type'         => $this->schedule->get_type(),
					'status'       => array(
						'message'      => 'Backup complete',
						'success'      => '1',
					),
				),
			);

		}

		$signature    = hash_hmac( 'sha1', serialize( $body ), $this->get_secret_key() );
		$webhook_args = array( 'headers' => array( 'X-BWP-Signature' => $signature ), 'body' => $body );

		$ret = wp_remote_post( $webhook_url, $webhook_args );

		if ( is_wp_error( $ret ) ) {
			$backup->warning( 'Webhook', sprintf( __( 'Error: %s', 'backupwordpress' ), $ret->get_error_message() ) );
		}

	}

	public static function intercom_data() {
		return array();
	}

	public static function intercom_data_html() {}
}
