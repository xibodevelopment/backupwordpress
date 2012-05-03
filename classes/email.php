<?php

/**
 * Email notifications for backups
 *
 * @extends HMBKP_Service
 */
class HMBKP_Email_Service extends HMBKP_Service {

	/**
	 * Output the email form field
	 *
	 * @access  public
	 */
	public function field() { ?>

		<label>

            <?php _e( 'Email notification', 'hmbkp' ); ?>

            <input type="email" name="<?php echo $this->get_field_name( 'email' ); ?>" value="<?php echo $this->get_field_value( 'email' ); ?>" />

            <p class="description">Receive a notification email when a backup completes, if the backup is small enough (&larr;) 10mb) then it will be attached to the email. Separate multiple email address's with a comma.</p>

        </label>

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

	/**
	 * The sentence fragment that is output as part of the schedule sentence
	 *
	 * @return string The sentence fragment
	 */
	public function display() {

		if ( $emails = $this->get_email_address_array() ) {

			$email = '<code>' . implode( '</code>, <code>', $emails ) . '</code>';

			return sprintf( __( 'Send an email notification to %s', 'hmbkp' ), $email );

		}

	}

	/**
	 * Validate the email and return an error if validation fails
	 *
	 * @param  array  &$new_data Array of new data, passed by reference
	 * @param  array  $old_data  The data we are replacing
	 * @return null|array        Null on success, array of errors if validation failed
	 */
	public function update( &$new_data, $old_data ) {

		$errors = array();

		if ( isset( $new_data['email'] ) ) {

			if ( ! empty( $new_data['email'] ) )
				foreach( explode( ',', $new_data['email'] ) as $email )
					if ( ! is_email( trim( $email ) ) )
						$errors['email'] = sprintf( __( '%s isn\'t a valid email',  'hmbkp' ), esc_attr( $email ) );


			if ( ! empty( $errors['email'] ) )
				$new_data['email'] = '';

		}

		return $errors;

	}

	private function get_email_address_array() {

		$emails = array_map( 'trim', explode( ',', $this->get_field_value( 'email' ) ) );

		return array_filter( array_unique( $emails ), 'is_email' );

	}

	public function action( $action ) {

		if ( $action == 'hmbkp_backup_complete' && $this->get_email_address_array() ) {

			$file = $this->schedule->get_archive_filepath();

			$sent = false;

			$download = add_query_arg( 'hmbkp_download', base64_encode( $file ), HMBKP_ADMIN_URL );
			$domain = parse_url( home_url(), PHP_URL_HOST ) . parse_url( home_url(), PHP_URL_PATH );

			$headers = 'From: BackUpWordPress <' . get_bloginfo( 'admin_email' ) . '>' . "\r\n";
			$subject = sprintf( __( 'Backup of %s', 'hmbkp' ), $domain );

			// The email failed, send a message saying as much
			if ( ! file_exists( $file ) && $schedule->errors() ) {

				error_log( 'failed' );

			}

			// If it's larger than 10MB assume it's not going to be able to send the backup
			if ( filesize( $file ) < 1000 * 1000 * 10 ) {

				error_log( 'attached' );
				$message = sprintf( __( "BackUpWordPress has completed a backup of your site %s.\n\nThe backup file should be attached to this email.\n\nYou can also download the backup file by clicking the link below:\n\n%s\n\nKind Regards\n\n The Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), home_url(), $download );

				$sent = wp_mail( $this->get_email_address_array(), $subject, $message, $headers, $file );

			}

			// If we didn't send the email above then send just the notification
			if ( ! $sent ) {

				error_log( 'notify' );

				$message = sprintf( __( "BackUpWordPress has completed a backup of your site %s.\n\nUnfortunately the backup file was too large to attach to this email.\n\nYou can download the backup file by clicking the link below:\n\n%s\n\nKind Regards\n\n The Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), home_url(), $download );

				$sent = wp_mail( $this->get_email_address_array(), $subject, $message, $headers );

			}

		}

	}

}

// Register the service
HMBKP_Services::register( __FILE__, 'HMBKP_Email_Service' );