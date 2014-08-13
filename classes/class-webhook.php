<?php

/**
 * Webhook notifications for backups
 *
 * @extends HMBKP_Service
 */
class HMBKP_Webhook_Service extends HMBKP_Service {

	/**
	 * Human readable name for this service
	 * @var string
	 */
	public $name = 'Webhook';

	/**
	 * Determines whether to show or hide the service tab in destinations form
	 * @var boolean
	 */
	public $isTabVisible = false;

	/**
	 * Output the form field
	 *
	 * @access  public
	 */
	public function field() { ?>

		<div>

			<label for="<?php echo esc_attr( $this->get_field_name( 'webhook_url' ) ); ?>"><?php _e( 'Webhook URL', 'hmbkp' ); ?></label>

			<input type="url" id="<?php echo esc_attr( $this->get_field_name( 'webhook_url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'webhook_url' ) ); ?>" value="<?php echo esc_attr( $this->get_field_value( 'webhook_url' ) ); ?>" />

			<p class="description"><?php  _e( 'Send a notification to an external service.', 'hmbkp' ); ?></p>

		</div>

	<?php }

	/**
	 * Not used as we only need a field
	 *
	 * @see  field
	 * @return string Empty string
	 */
	public function form() {
		return '';
	}

	public static function constant() {}

	/**
	 * The sentence fragment that is output as part of the schedule sentence
	 *
	 * @return string
	 */
	public function display() {

		$webhook_url = $this->get_field_value( 'webhook_url' );

		return sprintf( __( 'Notify this URL %s', 'hmbkp' ), $webhook_url );


	}

	/**
	 * Used to determine if the service is in use or not
	 */
	public function is_service_active() {
		return strlen( $this->get_field_value( 'webhook_url' ) ) > 0;
	}

	/**
	 * Validate the URL and return an error if validation fails
	 *
	 * @param  array  &$new_data Array of new data, passed by reference
	 * @param  array  $old_data  The data we are replacing
	 * @return null|array        Null on success, array of errors if validation failed
	 */
	public function update( &$new_data, $old_data ) {

		$errors = array();

		if ( isset( $new_data['webhook_url'] ) ) {

			if ( ! empty( $new_data['webhook_url'] ) ) {

				if ( false === filter_var( $new_data['webhook_url'], FILTER_VALIDATE_URL ) ) {
					$errors['webhook_url'] = sprintf( __( '%s isn\'t a valid URL',  'hmbkp' ), $new_data['webhook_url'] );
				}

				if ( ! empty( $errors['webhook_url'] ) ) {
					$new_data['webhook_url'] = '';
				}

			}

			return $errors;

		}

	}

	/**
	 * Fire the webhook notification on the hmbkp_backup_complete
	 *
	 * @see  HM_Backup::do_action
	 * @param  string $action The action received from the backup
	 * @return void
	 */
	public function action( $action ) {

		if ( 'hmbkp_backup_complete' !== $action )
			return;

		$webhook_url = $this->get_field_value( 'webhook_url' );

		$file = $this->schedule->get_archive_filepath();

		$download = add_query_arg( 'hmbkp_download', base64_encode( $file ), HMBKP_ADMIN_URL );

		$domain   = parse_url( home_url(), PHP_URL_HOST ) . parse_url( home_url(), PHP_URL_PATH );

		// The backup failed, send a message saying as much
		if ( ! file_exists( $file ) && ( $errors = array_merge( $this->schedule->get_errors(), $this->schedule->get_warnings() ) ) ) {

			$error_message = '';

			foreach ( $errors as $error_set )
				$error_message .= implode( "\n - ", $error_set );

			if ( $error_message )
				$error_message = ' - ' . $error_message;

			$subject = sprintf( __( 'Backup of %s Failed', 'hmbkp' ), $domain );

			$data = array(
				'type' => 'backup.error',
				'payload' => array(
					'id' => 'backup_' . $this->schedule->get_id(),
					'start' => 0,
					'end' => 0,
					'download_url' => null,
					'type' => $this->schedule->get_type(),
					'status' => array(
						'message' => $subject . ' - ' . $error_message,
						'success' => false
					)
				)
			);

			$webhook_args = array(

				'body' => $data

			);

		} else {
			$data = array(
				'type' => 'backup.success',
				'payload' => array(
					'id' => 'backup_' . $this->schedule->get_id(),
					'start' => 0,
					'end' => 0,
					'download_url' => $download,
					'type' => $this->schedule->get_type(),
					'status' => array(
						'message' => 'Backup complete',
						'success' => true
					)
				)
			);
		}


		$webhook_args = array(

			'body' => $data

		);

		$ret = wp_remote_post( $webhook_url, $webhook_args );

		if ( is_wp_error( $ret ) )
			return $ret->get_error_message();

	}

	public static function intercom_data() { return array(); }

	public static function intercom_data_html() {}

}

// Register the service
HMBKP_Services::register( __FILE__, 'HMBKP_Webhook_Service' );
