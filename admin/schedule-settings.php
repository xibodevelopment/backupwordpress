<?php

if ( HM\BackUpWordPress\Schedules::get_instance()->get_schedule( $schedule->get_id() ) ) { ?>

	<div class="hmbkp-schedule-actions row-actions">

		<a class="hmbkp-run" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'hmbkp_run_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), admin_url( 'admin-ajax.php' ) ), 'hmbkp_run_schedule', 'hmbkp_run_schedule_nonce' ) ); ?>"><?php _e( 'Run now', 'backupwordpress' ); ?></a>  |

		<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_settings', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ), 'hmbkp-edit-schedule' ); ?>"><?php _e( 'Settings', 'backupwordpress' ); ?></a> |

		<?php

		// Only show excludes if we are backing up files
		if ( 'database' !== $schedule->get_type() ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_excludes', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php _e( 'Excludes', 'backupwordpress' ); ?></a> |
		<?php endif; ?>

		<?php foreach ( HM\BackUpWordPress\Services::get_services( $schedule ) as $service ) :

			if ( ! $service->has_form() )
				continue; ?>

			<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_settings_' . $service->get_slug() , 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php echo esc_html( $service->name ); ?></a> |

		<?php endforeach; ?>

		<a class="delete-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'hmbkp_request_delete_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), admin_url( 'admin-post.php' ) ), 'hmbkp_delete_schedule', 'hmbkp_delete_schedule_nonce' ) ); ?>"><?php _e( 'Delete', 'backupwordpress' ); ?></a>

	</div>

<?php } ?>

<?php if ( ! isset( $_GET['action'] ) || 'hmbkp_edit_schedule' !== $_GET['action'] || ! isset( $_GET['hmbkp_panel'] ) ) {
	return;
} ?>

<div class="hmbkp-schedule-settings">

	<?php if ( $_GET['action'] === 'hmbkp_edit_schedule' && $_GET['hmbkp_panel'] === 'hmbkp_edit_schedule_settings' ) {
		require( HMBKP_PLUGIN_PATH . 'admin/schedule-form.php' );
	} ?>

	<?php if ( $_GET['action'] === 'hmbkp_edit_schedule' && $_GET['hmbkp_panel'] === 'hmbkp_edit_schedule_excludes' ) {
		require( HMBKP_PLUGIN_PATH . 'admin/schedule-form-excludes.php' );
	} ?>

	<?php // Show the service form if we are viewing one
	foreach ( HM\BackUpWordPress\Services::get_services( $schedule ) as $service ) : ?>

		<?php if ( $_GET['action'] === 'hmbkp_edit_schedule' && $_GET['hmbkp_panel'] === 'hmbkp_edit_schedule_settings_' . $service->get_slug() ) { ?>

			<h3><?php echo esc_html( $service->name ); ?></h3>

			<?php

			$hmbkp_form_errors = hmbkp_get_settings_errors();

			if ( ! empty( $hmbkp_form_errors ) ) :

				?>

				<div id="hmbkp-warning" class="error settings-error">

					<?php foreach ( $hmbkp_form_errors as $error ) { ?>
						<p><strong><?php echo esc_html( $error ); ?></strong></p>
					<?php } ?>

				</div>

			<?php

			endif;

			// We can clear them now we've displayed them
			hmbkp_clear_settings_errors();

			?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

				<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />
				<input type="hidden" name="action" value="hmbkp_edit_schedule_services_submit" />

				<?php wp_nonce_field( 'hmbkp-edit-schedule-services', 'hmbkp-edit-schedule-services-nonce' ); ?>

				<?php $service->form(); ?>

				<?php submit_button( __( 'Done', 'backupwordpress' ) ); ?>

			</form>

			<?php break; ?>

		<?php } ?>

	<?php endforeach; ?>

</div>