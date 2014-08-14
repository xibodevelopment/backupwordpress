<?php if ( HMBKP_Schedules::get_instance()->get_schedule( $schedule->get_id() ) ) { ?>

	<div class="hmbkp-schedule-actions row-actions">

		<a class="hmbkp-run" href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_run_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php _e( 'Run now', 'hmbkp' ); ?></a>  |

		<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_settings', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php _e( 'Settings', 'hmbkp' ); ?></a> |

<?php // Only show excludes if we are backing up files
if ( $schedule->get_type() !== 'database' ) { ?>
		<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_excludes', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php _e( 'Excludes', 'hmbkp' ); ?></a> |
<?php } ?>

		<?php foreach ( HMBKP_Services::get_services( $schedule ) as $service ) :

			if ( ! $service->has_form() )
				continue; ?>

			<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_settings_' . $service->get_slug() , 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php echo esc_html( $service->name ); ?></a> |

		<?php endforeach; ?>

		<a class="delete-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'hmbkp_delete_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ), 'hmbkp-delete_schedule' ) ); ?>"><?php _e( 'Delete', 'hmbkp' ); ?></a>

	</div>

<?php } ?>

<?php if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_edit_schedule' || ! isset( $_GET['hmbkp_panel'] ) ) {
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
	foreach ( HMBKP_Services::get_services( $schedule ) as $service ) : ?>

		<?php if ( $_GET['action'] === 'hmbkp_edit_schedule' && $_GET['hmbkp_panel'] === 'hmbkp_edit_schedule_settings_' . $service->get_slug() ) { ?>

			<h3><?php echo esc_html( $service->name ); ?></h3>

			<?php global $hmbkp_form_errors; ?>

			<?php if ( ! empty( $hmbkp_form_errors ) ) { ?>

				<div id="hmbkp-warning" class="error settings-error">

					<?php foreach ( $hmbkp_form_errors as $error ) { ?>
						<p><strong><?php echo esc_html( $error ); ?></strong></p>
					<?php } ?>

				</div>

			<?php } ?>

			<form method="post" novalidate>

			<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />
			<input type="hidden" name="action" value="hmbkp_edit_schedule_service" />

			<?php wp_nonce_field( 'hmbkp-edit_schedule_service' ); ?>

			<?php $service->form(); ?>

			<p class="submit">
				<a class="button-secondary" href="<?php echo esc_url( hmbkp_get_settings_url() ) ?>">Close</a>
				<button type="submit" class="button-primary"><?php _e( 'Save Changes', 'hmbkp' ); ?></button>
			</p>

			</form>

			<?php break; ?>

		<?php } ?>

	<?php endforeach; ?>

</div>