<?php

/**
 * Webhook notifications for backups on WPRemote
 *
 * @extends HMBKP_Service
 */
class HMBKP_Webhook_Custom_Service extends HMBKP_Webhooks_Service {

	/**
	 * Human readable name for this service
	 * @var string
	 */
	public $name = 'Webhook';

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
	 * Not used as we only need a field
	 *
	 * @see  field
	 * @return string Empty string
	 */
	public function form() {
		return '';
	}

	/**
	 * Output the form field
	 *
	 * @access  public
	 */
	public function field() {
		?>
		<div>

			<div>
				<label for="<?php echo esc_attr( $this->get_field_name( 'webhook_url' ) ); ?>"><?php _e( 'Webhook URL', 'hmbkp' ); ?></label>
				<input type="url" id="<?php echo esc_attr( $this->get_field_name( 'webhook_url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'webhook_url' ) ); ?>" value="<?php echo esc_attr( $this->get_field_value( 'webhook_url' ) ); ?>" />
			</div>
			<div>
				<label for="<?php echo esc_attr( $this->get_field_name( 'webhook_secret_key' ) ); ?>"><?php _e( 'Webhook Secret', 'hmbkp' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $this->get_field_name( 'webhook_secret_key' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'webhook_secret_key' ) ); ?>" value="<?php echo esc_attr( $this->get_field_value( 'webhook_secret_key' ) ); ?>" />
			</div>

			<p class="description"><?php  _e( 'Send a notification to an external service.', 'hmbkp' ); ?></p>

		</div>
	<?php
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

		if ( isset ( $new_data['webhook_url'] ) ) {

			if ( ! empty( $new_data['webhook_url'] ) ) {

				if ( false === filter_var( $new_data['webhook_url'], FILTER_VALIDATE_URL ) ) {
					$errors['webhook_url'] = sprintf( __( '%s isn\'t a valid URL',  'hmbkp' ), $new_data['webhook_url'] );
				}

				if ( ! empty( $errors['webhook_url'] ) ) {

					$new_data['webhook_url'] = '';
				} else {

					$new_data['webhook_url'] = esc_url_raw( $new_data['webhook_url'] );
				}
			}
		}

		if ( isset ( $new_data['webhook_secret_key'] ) ) {

			if ( ! empty( $new_data['webhook_secret_key'] ) ) {

				if ( ! empty( $errors['webhook_secret_key'] ) ) {
					$new_data['webhook_secret_key'] = '';
				} else {
					$new_data['webhook_secret_key'] = sanitize_text_field( $new_data['webhook_secret_key'] );
				}
			}
		}

		return ( $errors ) ? $errors : null;

	}

	/**
	 * Used to determine if the service is in use or not
	 */
	public function is_service_active() {

		return strlen( $this->get_field_value( 'webhook_url' ) ) > 0;
	}

	/**
	 * @return string
	 */
	protected function get_secret_key() {
		return $this->get_field_value( 'webhook_secret_key' );
	}

	/**
	 * @return string
	 */
	protected function get_url() {
		return $this->get_field_value( 'webhook_url' );
	}

}

// Register the service
HMBKP_Services::register( __FILE__, 'HMBKP_Webhook_Custom_Service' );