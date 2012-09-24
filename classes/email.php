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

            <p class="description"><?php _e( 'Receive a notification email when a backup completes, if the backup is small enough (&lt; 10mb) then it will be attached to the email. Separate multiple email address\'s with a comma.', 'hmbkp' ); ?></p>

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

	/**
	 * Get an array or validated email address's
	 * @return array An array of validated email address's
	 */
	private function get_email_address_array() {

		$emails = array_map( 'trim', explode( ',', $this->get_field_value( 'email' ) ) );

		return array_filter( array_unique( $emails ), 'is_email' );

	}

	/**
	 * Fire the email notification on the hmbkp_backup_complete
	 *
	 * @see  HM_Backup::do_action
	 * @param  string $action The action received from the backup
	 * @return void
	 */
	public function action( $action ) {

		if ( $action == 'hmbkp_backup_complete' && $this->get_email_address_array() ) {

			$file = $this->schedule->get_archive_filepath();

			$sent = false;

			$download = add_query_arg( 'hmbkp_download', base64_encode( $file ), HMBKP_ADMIN_URL );
			$domain = parse_url( home_url(), PHP_URL_HOST ) . parse_url( home_url(), PHP_URL_PATH );

			$headers = 'From: BackUpWordPress <' . get_bloginfo( 'admin_email' ) . '>' . "\r\n";

			// The backup failed, send a message saying as much
			if ( ! file_exists( $file ) && ( $errors = array_merge( $this->schedule->get_errors(), $this->schedule->get_warnings() ) ) ) {

				$error_message = '';

				foreach ( $errors as $error_set )
					$error_message .= implode( "\n - ", $error_set );

				if ( $error_message )
					$error_message = ' - ' . $error_message;

				$subject = sprintf( __( 'Backup of %s Failed', 'hmbkp' ), $domain );

				$message = sprintf( __( 'BackUpWordPress was unable to backup your site %1$s.', 'hmbkp' ) . "\n\n" . __( 'Here are the errors that we\'re encountered:', 'hmbkp' ) . "\n\n" . '%2$s' . "\n\n" . __( 'If the errors above look like Martian, forward this email to %3$s and we\'ll take a look', 'hmbkp' ) . "\n\n" . __( "Kind Regards,\nThe Apologetic BackUpWordPress Backup Emailing Robot", 'hmbkp' ), home_url(), $error_message, 'support@hmn.md' );

				$sent = wp_mail( $this->get_email_address_array(), $subject, $message, $headers );

				return;

			}

			$subject = sprintf( __( 'Backup of %s', 'hmbkp' ), $domain );

			// If it's larger than 10MB assume it's not going to be able to send the backup
			if ( filesize( $file ) < 1000 * 1000 * 10 ) {

				$message = sprintf( __( 'BackUpWordPress has completed a backup of your site %1$s.', 'hmbkp' ) . "\n\n" . __( 'The backup file should be attached to this email.', 'hmbkp' ) . "\n\n" . __( 'You can download the backup file by clicking the link below:', 'hmbkp' ) . "\n\n" . '%2$s' . "\n\n" . __( "Kind Regards,\nThe Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), home_url(), $download );

				$sent = wp_mail( $this->get_email_address_array(), $subject, $message, $headers, $file );

			}

			// If we didn't send the email above then send just the notification
			if ( ! $sent ) {

				$message = sprintf( __( 'BackUpWordPress has completed a backup of your site %1$s.', 'hmbkp' ) . "\n\n" . __( 'Unfortunately the backup file was too large to attach to this email.', 'hmbkp' ) . "\n\n" . __( 'You can download the backup file by clicking the link below:', 'hmbkp' ) . "\n\n" . '%2$s' . "\n\n" . __( "Kind Regards,\nThe Happy BackUpWordPress Backup Emailing Robot", 'hmbkp' ), home_url(), $download );

				$sent = wp_mail( $this->get_email_address_array(), $subject, $message, $headers );

			}

		}

	}

}

// Register the service
HMBKP_Services::register( __FILE__, 'HMBKP_Email_Service' );