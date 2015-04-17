<?php require_once HMBKP_PLUGIN_PATH . 'classes/class-requirements.php'; ?>

<h2><?php _e( 'Enable BackUpWordPress Support', 'backupwordpress' ); ?></h2>

<p class="howto"><?php printf( __( 'BackUpWordPress uses %s to provide support. In addition to allowing you to send and receive messages we also send the following server information along with your requests:', 'backupwordpress' ), '<a target="blank" href="https://www.intercom.io">Intercom</a>' ); ?></p>

<div class="server-info">

<?php foreach ( HM\BackUpWordPress\Requirements::get_requirement_groups() as $group ) : ?>

	<table class="fixed widefat">

		<thead>
			<tr>
				<th scope="col" colspan="2"><?php echo esc_html( ucwords( $group ) ); ?></th>
			</tr>
		</thead>

		<tbody>

		<?php foreach ( HM\BackUpWordPress\Requirements::get_requirements( $group ) as $requirement ) : ?>

			<?php if ( ( is_string( $requirement->raw_result() ) && strlen( $requirement->result() ) < 20 ) || is_bool( $requirement->raw_result() ) ) { ?>

			<tr>

				<td><?php echo esc_html( $requirement->name() ); ?></td>

				<td>
					<code><?php echo esc_html( $requirement->result() ); ?></code>
				</td>

			</tr>

			<?php } else { ?>

			<tr>

				<td colspan="2">
					<?php echo esc_html( $requirement->name() ); ?>
					<pre><?php echo esc_html( $requirement->result() ); ?></pre>
				</td>

			</tr>

			<?php } ?>

		<?php endforeach; ?>

		</tbody>

	</table>

<?php endforeach; ?>

</div>

<p class="howto"><?php _e( 'You can disable support in the future by deactivating BackUpWordPress.', 'backupwordpress' ); ?></p>

<a href="#" class="button-secondary hmbkp-thickbox-close"><?php _e( 'No thanks', 'backupwordpress' ); ?></a>
<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'hmbkp_request_enable_support' ), admin_url( 'admin-post.php' ) ), 'hmbkp_enable_support', 'hmbkp_enable_support_nonce' ) ); ?>" class="button-primary right"><?php _e( 'Yes I want to enable support', 'backupwordpress' ); ?></a>