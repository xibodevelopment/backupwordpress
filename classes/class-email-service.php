<?php

namespace HM\BackUpWordPress;

/**
 * Email notifications for backups
 *
 * @extends Service
 */
class Email_Service extends Service {

	/**
	 * Human readable name for this service
	 * @var string
	 */
	public $name = 'Email';

	/**
	 * Output the email form field
	 *
	 * @access  public
	 */
	public function field() {

	?>

		<tr>

			<th scope="row">
				<label for="<?php echo esc_attr( $this->get_field_name( 'email' ) ); ?>"><?php esc_html_e( 'Email notification', 'backupwordpress' ); ?></label>
			</th>

			<td>
				<input type="text" id="<?php echo esc_attr( $this->get_field_name( 'email' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'email' ) ); ?>" value="<?php echo esc_attr( $this->get_field_value( 'email' ) ); ?>" placeholder="name@youremail.com" />

				<p class="description"><?php printf( esc_html__( 'Receive a notification email when a backup completes. If the backup is small enough (%s), then it will be attached to the email. Separate multiple email addresses with a comma.', 'backupwordpress' ), '<code> &lt; ' . esc_html( size_format( get_max_attachment_size() ) ) . '</code>' ); ?></p>
			</td>

		</tr>

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

	public static function constant() {

	?>

		<tr<?php if ( defined( 'HMBKP_ATTACHMENT_MAX_FILESIZE' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_ATTACHMENT_MAX_FILESIZE</code></td>

			<td>

				<?php if ( defined( 'HMBKP_ATTACHMENT_MAX_FILESIZE' ) ) { ?>
				<p><?php printf(
					/* translators: Constant value specified in wp-config.php */
					esc_html__( 'You\'ve set it to: %s', 'backupwordpress' ),
					'<code>' . esc_html( HMBKP_ATTACHMENT_MAX_FILESIZE ) . '</code>'
					); ?></p>
				<?php } ?>

				<p><?php printf(
					/* translators: 1: Default value 2: Code example of how to specify the constant in wp-config.php */
					esc_html__( 'The maximum file size of your backup that will be attached to your notification emails. Defaults to %1$s. e.g. %2$s', 'backupwordpress' ),
					'<code>10MB</code>',
					"<code>define( 'HMBKP_ATTACHMENT_MAX_FILESIZE', '25MB' );</code>"
					); ?></p>

			</td>

		</tr>

	<?php }

	/**
	 * The sentence fragment that is output as part of the schedule sentence
	 *
	 * @return string
	 */
	public function display() {

		if ( $emails = $this->get_email_address_array() ) {

			$email = '<code>' . implode( '</code>, <code>', array_map( 'esc_html', $emails ) ) . '</code>';

			return sprintf(
				/* translators: List of email addresses */
				esc_html__( 'Send an email notification to %s', 'backupwordpress' ),
				$email
			);

		}

		return '';

	}

	/**
	 * Used to determine if the service is in use or not
	 */
	public function is_service_active() {
		return (bool) $this->get_email_address_array();
	}

	/**
	 * Validate the email and return an error if validation fails
	 *
	 * @param array &$new_data Array of new data, passed by reference.
	 * @param array $old_data  The data we are replacing.
	 *
	 * @return array|null      Null on success, array of errors if validation failed.
	 */
	public function update( &$new_data, $old_data ) {

		$errors = array();

		if ( isset( $new_data['email'] ) ) {

			if ( ! empty( $new_data['email'] ) ) {

				foreach ( explode( ',', $new_data['email'] ) as $email ) {

					$email = trim( $email );

					if ( ! is_email( $email ) ) {
						$errors['email'] = sprintf( esc_html__( '%s isn\'t a valid email',  'backupwordpress' ), $email );
					}
				}
			}

			if ( ! empty( $errors['email'] ) ) {
				$new_data['email'] = '';
			}

			return $errors;

		}
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
	 * @see  Backup::do_action
	 * @param  string $action The action received from the backup
	 */
	public function action( $action, Backup $backup ) {

		if ( 'hmbkp_backup_complete' === $action && $this->get_email_address_array() ) {

			$file = $backup->get_backup_filepath();

			$sent = false;

			$download = add_query_arg( 'hmbkp_download', base64_encode( $file ), HMBKP_ADMIN_URL );
			$domain   = parse_url( home_url(), PHP_URL_HOST ) . parse_url( home_url(), PHP_URL_PATH );

			$headers  = 'From: BackUpWordPress <' . apply_filters( 'hmbkp_from_email', get_bloginfo( 'admin_email' ) ) . '>' . "\r\n";

			// The backup failed, send a message saying as much
			if ( ! file_exists( $file ) && ( $errors = array_merge( $backup->get_errors(), $backup->get_warnings() ) ) ) {

				$error_message = '';

				foreach ( $errors as $error_set ) {
					$error_message .= implode( "\n - ", $error_set );
				}

				if ( $error_message ) {
					$error_message = ' - ' . $error_message;
				}

				$subject = wp_strip_all_tags( sprintf(
					/* translators: Domain URL */
					__( 'Backup of %s failed', 'backupwordpress' ),
					$domain
				) );

				$message = sprintf(
						/* translators: 1: Site URL 2: List of errors */
						esc_html__(
								'BackUpWordPress was unable to backup your site %1$s.' . "\n\n" .
								'Here are the errors that we\'ve encountered: %2$s' . "\n\n" .
								'If the errors above look like Martian, forward this email to %3$s and we\'ll take a look.' . "\n\n", 'backupwordpress'
						),
						esc_url( home_url() ),
						$error_message,
						'backupwordpress@hmn.md'
				);

				wp_mail( $this->get_email_address_array(), $subject, $message, $headers );

				return;
			}

			$subject = wp_strip_all_tags( sprintf(
				/* translators: Domain URL */
				__( 'Backup of %s', 'backupwordpress' ),
				$domain
			) );

			// If it's larger than the max attachment size limit assume it's not going to be able to send the backup
			if ( @filesize( $file ) < get_max_attachment_size() ) {

				$message = sprintf(
						/* translators: 1: Site URL 2: WordPress admin URL to BackupWordPress page */
						esc_html__(
								'BackUpWordPress has completed a backup of your site %1$s' . "\n\n" .
								'The backup file should be attached to this email.' . "\n\n" .
								'You can download the backup file by clicking the link below:' . "\n\n" . '%2$s' . "\n\n" .
								'Kind Regards,\nThe Happy BackUpWordPress Backup Emailing Robot', 'backupwordpress'
						),
						esc_url( home_url() ),
						esc_url( $download )
				);

				$sent = wp_mail( $this->get_email_address_array(), $subject, $message, $headers, $file );
			}

			// If we didn't send the email above then send just the notification
			if ( ! $sent ) {

				$message = sprintf(
						/* translators: 1: Site URL 2: WordPress admin URL to BackupWordPress page */
						esc_html__(
								'BackUpWordPress has completed a backup of your site %1$s' . "\n\n" .
								'Unfortunately, the backup file was too large to attach to this email.' . "\n\n" .
								'You can download the backup file by clicking the link below:' . "\n\n" . '%2$s' . "\n\n" .
								'Kind Regards,\nThe Happy BackUpWordPress Backup Emailing Robot', 'backupwordpress'
						),
						esc_url( home_url() ),
						esc_url( $download )
				);

				wp_mail( $this->get_email_address_array(), $subject, $message, $headers );
			}
		}
	}

	public static function intercom_data() {
		return array();
	}

	public static function intercom_data_html() {}
}

// Register the service
Services::register( __FILE__, 'HM\BackUpWordPress\Email_Service' );
