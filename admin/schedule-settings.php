<?php if ( HMBKP_Schedules::get_instance()->get_schedule( $schedule->get_id() ) ) { ?>

	<div class="hmbkp-schedule-actions row-actions">

		<a class="hmbkp-run" href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_run_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php _e( 'Run now', 'hmbkp' ); ?></a>  |

		<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_settings', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php _e( 'Settings', 'hmbkp' ); ?></a> |

		<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_excludes', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php _e( 'Excludes', 'hmbkp' ); ?></a> |

		<?php foreach ( HMBKP_Services::get_services( $schedule ) as $service ) :

			if ( ! $service->has_form() )
				continue; ?>

			<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'hmbkp_edit_schedule', 'hmbkp_panel' => 'hmbkp_edit_schedule_settings_' . $service->get_slug() , 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ) ); ?>"><?php echo esc_html( $service->name ); ?></a> |

		<?php endforeach; ?>

		<a class="delete-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'hmbkp_delete_schedule', 'hmbkp_schedule_id' => $schedule->get_id() ), hmbkp_get_settings_url() ), 'hmbkp-delete_schedule' ) ); ?>"><?php _e( 'Delete', 'hmbkp' ); ?></a>

	</div>

<?php } ?>

<?php if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'hmbkp_edit_schedule' || ! isset( $_GET['hmbkp_panel'] ) )
	return; ?>

<div class="hmbkp-schedule-settings">

	<?php if ( $_GET['action'] === 'hmbkp_edit_schedule' && $_GET['hmbkp_panel'] === 'hmbkp_edit_schedule_settings' ) {
		require( HMBKP_PLUGIN_PATH . 'admin/schedule-form.php' );
	} ?>

	<?php if ( $_GET['action'] === 'hmbkp_edit_schedule' && $_GET['hmbkp_panel'] === 'hmbkp_edit_schedule_excludes' ) {
		require( HMBKP_PLUGIN_PATH . 'admin/schedule-form-excludes.php' );
	} ?>

	<?php foreach ( HMBKP_Services::get_services( $schedule ) as $service ) : ?>

		<?php if ( $_GET['action'] === 'hmbkp_edit_schedule' && $_GET['hmbkp_panel'] === 'hmbkp_edit_schedule_settings_' . $service->get_slug() ) {
			$service->form();
		} ?>

	<?php endforeach; ?>

</div>