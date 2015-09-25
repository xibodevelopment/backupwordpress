<?php

foreach ( HM\BackUpWordPress\Requirements::get_requirement_groups() as $group ) : ?>

	<h3><?php echo esc_html( ucwords( $group ) ); ?></h3>

	<table class="fixed widefat">

		<tbody>

		<?php foreach ( HM\BackUpWordPress\Requirements::get_requirements( $group ) as $requirement ) : ?>

			<?php if ( ( is_string( $requirement->raw_result() ) && strlen( $requirement->result() ) < 20 ) || is_bool( $requirement->raw_result() ) ) : ?>

				<tr>

					<td><?php echo esc_html( $requirement->name() ); ?></td>

					<td>
						<code><?php echo esc_html( $requirement->result() ); ?></code>
					</td>

				</tr>

			<?php elseif ( is_array( $requirement->raw_result() ) ) : ?>

				<tr>

					<td><?php echo esc_html( $requirement->name() ); ?></td>

					<td>
						<ul>

							<pre><?php var_export( $requirement->raw_result() ); ?></pre>

						</ul>
					</td>
				</tr>

			<?php else : ?>

				<tr>

					<td colspan="2">
						<?php echo esc_html( $requirement->name() ); ?>
						<pre><?php echo esc_html( $requirement->result() ); ?></pre>
					</td>

				</tr>

			<?php endif; ?>

		<?php endforeach; ?>

		</tbody>

	</table>

<?php endforeach;

foreach ( HM\BackUpWordPress\Services::get_services() as $file => $service ) {
	echo wp_kses_post( call_user_func( array( $service, 'intercom_data_html' ) ) );
}