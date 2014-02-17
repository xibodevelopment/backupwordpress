<?php foreach( HMBKP_Requirements::get_requirement_groups() as $group ) : ?>

	<h3><?php echo ucwords( $group ); ?></h3>

	<table class="fixed widefat">

		<tbody>

		<?php foreach ( HMBKP_Requirements::get_requirements( $group ) as $requirement ) : ?>

			<tr>
				<td><?php echo $requirement->name(); ?></td>
				<td><pre><?php echo $requirement->result(); ?></pre></td>
			</tr>

		<?php endforeach; ?>

		</tbody>

	</table>

<?php endforeach;

foreach ( HMBKP_Services::get_services() as $file => $service )
	echo wp_kses_post( call_user_func( array( $service, 'intercom_data_html' ) ) );
