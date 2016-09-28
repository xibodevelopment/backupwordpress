<?php

namespace HM\BackUpWordPress;

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

	$installed_plugins = array_reduce( get_plugins(), function( $carry, $item ) {
		$carry[ strtolower( $item['Name'] ) ] = $item['Version'];
		return $carry;
	}, array() );

	?>

	<h3><?php esc_html_e( 'Remote Storage', 'backupwordpress' ); ?></h3>

	<p><?php esc_html_e( 'It\'s important to store your backups somewhere other than on your site. Using the extensions below you can easily push your backups to one or more Cloud providers.', 'backupwordpress' ); ?></p>

	<div class="wp-list-table widefat plugin-install">

		<div id="the-list">

			<?php $first = true; ?>

			<?php foreach ( $extensions_data as $extension ) : ?>

				<div class="plugin-card plugin-card-<?php echo esc_attr( $extension->slug ); ?>">

					<div class="plugin-card-top">

						<div class="name column-name">

							<h4>

								<a href="<?php echo esc_url( $extension->link ); ?>" class="thickbox">

									<?php echo esc_html( $extension->title->rendered ); ?>

									<img src="<?php echo esc_url( $extension->featured_image_url ); ?>" class="plugin-icon" alt="">

								</a>

							</h4>

						</div>

						<div class="action-links">

							<ul class="plugin-action-buttons">

								<li>
									<?php if ( in_array( strtolower( $extension->title->rendered ), array_keys( $installed_plugins ) ) ) : ?>

										<span class="button button-disabled" title="<?php esc_attr_e( 'This extension is already installed', 'backupwordpress' ); ?>"><?php esc_html_e( 'Installed', 'backupwordpress' ); ?></span>

									<?php else : ?>

										<a class="install-now button-primary" data-slug="<?php echo esc_attr( $extension->slug ); ?>" href="<?php echo esc_url( $extension->link ); ?>" aria-label="Install <?php echo esc_attr( $extension->title->rendered ); ?> now" data-name="<?php echo esc_attr( $extension->title->rendered ); ?>"><?php printf( esc_html__( 'Buy Now &#36;%s', 'backupwordpress' ), esc_html( $extension->edd_price ) ); ?></a>

									<?php endif; ?>

								</li>

								<li>

									<a href="<?php echo esc_url( $extension->link ); ?>" class="thickbox" aria-label="<?php printf( esc_attr__( 'More information about %s', 'backupwordpress' ), esc_attr( $extension->title->rendered ) ) ; ?>" data-title="<?php echo esc_attr( $extension->title->rendered ); ?>"><?php esc_html_e( 'More Details', 'backupwordpress' ); ?></a>

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

							<div>

								<?php printf( esc_html__( 'Latest plugin version %s', 'backupwordpress' ), esc_html( $extension->_edd_sl_version ) ); ?>

							</div>

							<div>

								<?php
								if ( in_array( strtolower( $extension->title->rendered ), array_keys( $installed_plugins ) ) ) {

									$current_version = $installed_plugins[ strtolower( $extension->title->rendered ) ];

									if ( version_compare( $current_version, $extension->_edd_sl_version, '<' ) ) {

										printf(
											wp_kses(
												/* translators: 1: Currently installed extension version 2: URL to update an extension 3: Latest extension version */
												__( 'Your installed plugin version %1$s <a href="%2$s">Update now to the latest version</a>.', 'backupwordpress' ),
												array(
													'a' => array(
														'href' => array(),
													),
												)
											),
											esc_html( $current_version ),
											esc_url( admin_url( 'update-core.php' ) )
										);
									} else {

										esc_html_e( 'You have the latest version', 'backupwordpress' );
									}
								}

								?>

							</div>

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
