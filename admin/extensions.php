<?php

namespace HM\BackUpWordPress;

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

?>

<div class="wrap">

	<h1>

		<a class="page-title-action" href="<?php echo esc_url( get_settings_url() ); ?>"><?php esc_html_e( '&larr; Backups', 'backupwordpress' ); ?></a>

		<?php esc_html_e( 'BackUpWordPress Extensions', 'backupwordpress' ); ?>

	</h1>

	<div class="wp-filter">
		<p><?php esc_html_e( 'Extend BackUpWordPress by installing extensions. Extensions allow you to pick and choose the exact features you need whilst also supporting us, the developers, so we can continue working on BackUpWordPress.', 'backupwordpress' ); ?></p>
	</div>

	<?php
	$extensions_data = Extensions::get_instance()->get_edd_data();

	// Sort by title.
	usort( $extensions_data, function( $a, $b ) {
		return strcmp( $b->title->rendered, $a->title->rendered );
	});

	$installed_plugins = array();
	foreach ( get_plugins() as $path => $plugin_info ) {
		$installed_plugins[ strtolower( $plugin_info['Name'] ) ] = array(
			'version'   => $plugin_info['Version'],
			'is_active' => is_plugin_active( $path ),
			'path'      => $path,
		);
	}
	?>

	<h3><?php esc_html_e( 'Remote Storage', 'backupwordpress' ); ?></h3>

	<p><?php esc_html_e( 'It\'s important to store your backups somewhere other than on your site. Using the extensions below you can easily push your backups to one or more Cloud providers.', 'backupwordpress' ); ?></p>

	<div class="wp-list-table widefat plugin-install">

		<div id="the-list">

			<?php $first = true; ?>

			<?php foreach ( $extensions_data as $extension ) :

				$extension_name_lowcase = strtolower( $extension->title->rendered );
				$is_extension_installed = in_array( $extension_name_lowcase, array_keys( $installed_plugins ) );
				?>

				<div class="plugin-card plugin-card-<?php echo esc_attr( $extension->slug ); ?>">

					<div class="plugin-card-top">

						<div class="name column-name">

							<h3>

								<a href="<?php echo esc_url( $extension->link ); ?>" class="thickbox">

									<?php echo esc_html( $extension->title->rendered ); ?>

									<img src="<?php echo esc_url( $extension->featured_image_url ); ?>" class="plugin-icon" alt="">

								</a>

							</h3>

						</div>

						<div class="action-links">

							<ul class="plugin-action-buttons">

								<li>
									<?php
									// Update Now - Installed and update is available.
									if (
										$is_extension_installed &&
										version_compare( $installed_plugins[ $extension_name_lowcase ]['version'], $extension->_edd_sl_version, '<' )
									) :

										$update_url = wp_nonce_url(
											self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $installed_plugins[ $extension_name_lowcase ]['path'] ),
											'upgrade-plugin_' . $installed_plugins[ $extension_name_lowcase ]['path']
										);
										?>

										<a
											class="update-now button aria-button-if-js"
											data-plugin="<?php echo esc_attr( $installed_plugins[ $extension_name_lowcase ]['path'] ); ?>"
											data-slug="<?php echo esc_attr( $extension->slug ); ?>"
											href="<?php echo esc_url( $update_url ); ?>"
											aria-label="<?php echo esc_attr( sprintf( __( 'Update %s now', 'backupwordpress' ), $extension->title->rendered ) ) ?>"
											data-name="<?php esc_attr( $extension->title->rendered ); ?>">
											<?php esc_html_e( 'Update Now', 'backupwordpress' ); ?>
										</a>

									<?php
									// Active - Installed and activated, but no update.
									elseif (
										$is_extension_installed &&
										$installed_plugins[ $extension_name_lowcase ]['is_active']
									) : ?>

										<button
											type="button"
											class="button button-disabled"
											disabled="disabled">
											<?php echo esc_html_x( 'Active', 'Plugin status', 'backupwordpress' ); ?>
										</button>

									<?php
									// Activate - Installed, but not activated.
									elseif (
										$is_extension_installed &&
										! $installed_plugins[ $extension_name_lowcase ]['is_active']
									) :

										$activate_url = add_query_arg( array(
											'_wpnonce'    => wp_create_nonce( 'activate-plugin_' . $installed_plugins[ $extension_name_lowcase ]['path'] ),
											'action'      => 'activate',
											'plugin'      => $installed_plugins[ $extension_name_lowcase ]['path'],
											), network_admin_url( 'plugins.php' ) );

										// TODO: Network Activate?
										?>

										<a
											href="<?php echo esc_url( $activate_url ); ?>"
											class="button activate-now button-secondary"
											aria-label="<?php esc_attr( sprintf( __( 'Activate %s', 'backupwordpress' ), $extension->title->rendered ) ); ?>">
											<?php esc_html_e( 'Activate', 'backupwordpress' ); ?>
										</a>

									<?php
									// Buy Now - Not installed.
									else : ?>

										<a
											class="install-now button-primary"
											data-slug="<?php echo esc_attr( $extension->slug ); ?>"
											href="<?php echo esc_url( $extension->link ); ?>"
											aria-label="Install <?php echo esc_attr( $extension->title->rendered ); ?> now"
											data-name="<?php echo esc_attr( $extension->title->rendered ); ?>">
											<?php printf( esc_html__( 'Buy Now &#36;%s', 'backupwordpress' ), esc_html( $extension->edd_price ) ); ?>
										</a>

									<?php endif; ?>

								</li>

								<li>

									<a
										href="<?php echo esc_url( $extension->link ); ?>"
										class="thickbox"
										aria-label="<?php printf( esc_attr__( 'More information about %s', 'backupwordpress' ), esc_attr( $extension->title->rendered ) ) ; ?>"
										data-title="<?php echo esc_attr( $extension->title->rendered ); ?>">
										<?php esc_html_e( 'More Details', 'backupwordpress' ); ?>
									</a>

								</li>

							</ul>

						</div>

						<div class="desc column-description">

							<p><?php echo wp_kses_post( $extension->content->rendered ); ?></p>

						</div>

					</div>

					<?php
					$style = $first === true ? 'background-color:aliceblue;' : '';
					$first = false;
					?>

					<div class="plugin-card-bottom" style="<?php echo esc_attr( $style ); ?>">

						<div class="vers column-rating">
						</div>

						<div class="column-updated">

							<?php printf(
								wp_kses(
									__( '<strong>Last Updated:</strong> %s ago', 'backupwordpress' ),
									array(
										'strong' => array(),
									)
								),
								esc_html( human_time_diff( strtotime( $extension->modified ) ) )
							); ?>

						</div>

					</div>

				</div>

			<?php endforeach; ?>

		</div>

	</div>

</div>
