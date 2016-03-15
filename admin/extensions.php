<?php

namespace HM\BackUpWordPress;

?>

<div class="wrap">

	<h1>

		<a class="page-title-action" href="<?php echo esc_url( get_settings_url() ); ?>"><?php _e( '&larr; Backups', 'backupwordpress' ); ?></a>

		<?php _e( 'BackUpWordPress Extensions', 'backupwordpress' ); ?>

	</h1>

	<div class="wp-filter">
		<p><?php _e( 'Extend BackUpWordPress by installing extensions. Extensions allows you to pick and choose the exact features you need whilst also supporting us, the developers, so we can continue working on BackUpWordPress.', 'backupwordpress' ); ?></p>
	</div>

	<?php
	$extensions_data = Extensions::get_instance()->get_edd_data();

	// Sort by title
	usort( $extensions_data, function( $a, $b ) {
		return strcmp( $b->title->rendered, $a->title->rendered );
	});

	$installed_plugins = array_reduce( get_plugins(), function( $carry, $item ) {
		$carry[ strtolower( $item['Name'] ) ] = $item['Version'];
		return $carry;
	}, array() );

	?>

	<h3><?php _e( 'Remote Storage', 'backupwordpress' ); ?></h3>

	<p><?php _e( 'It\'s important to store your backups somewhere other than on your site. Using the extensions below you can easily push your backups to one or more Cloud providers.', 'backupwordpress' ); ?></p>

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

										<span class="button button-disabled" title="<?php _e( 'This extension is already installed', 'backupwordpress' ); ?>"><?php _e( 'Installed', 'backupwordpress' ); ?></span>

									<?php else : ?>

										<a class="install-now button-primary" data-slug="<?php echo esc_attr( $extension->slug ); ?>" href="<?php echo esc_url( $extension->link ); ?>" aria-label="Install <?php echo esc_attr( $extension->title->rendered ); ?> now" data-name="<?php echo esc_attr( $extension->title->rendered ); ?>"><?php printf( __( 'Buy Now &dollar;%s', 'backupwordpress' ), $extension->edd_price ); ?></a>

									<?php endif; ?>

								</li>

								<li>

									<a href="<?php echo esc_url( $extension->link ); ?>" class="thickbox" aria-label="<?php printf( __( 'More information about %s', 'backupwordpress' ), esc_attr( $extension->title->rendered ) ) ; ?>" data-title="<?php echo esc_attr( $extension->title->rendered ); ?>"><?php _e( 'More Details', 'backupwordpress' ); ?></a>

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

								<?php esc_html_e( sprintf( __( 'Plugin version %s', 'backupwordpress' ), $extension->_edd_sl_version ) ); ?>

							</div>

							<div>

								<?php

								$text = '';

								if ( in_array( strtolower( $extension->title->rendered ), array_keys( $installed_plugins ) ) ) {

									$current_version = $installed_plugins[ strtolower( $extension->title->rendered ) ];

									if ( version_compare( $current_version, $extension->_edd_sl_version, '<' ) ) {

										$text = sprintf( __( 'A newer version (%1$s) is available. <a href="%2$s">Update now!</a>', 'backupwordpress' ), esc_html( $extension->_edd_sl_version ), esc_url( admin_url( 'update-core.php' ) ) );
									} else {

										$text = esc_html__( 'You have the latest version', 'backupwordpress' );

									}
								}

								echo $text;

								?>

							</div>

						</div>

						<div class="column-updated">

							<strong><?php _e( 'Last Updated:', 'backupwordpress' ); ?></strong> <span title="<?php echo esc_attr( $extension->modified ); ?>"><?php printf( __( '%s ago', 'backupwordpress' ), human_time_diff( strtotime( $extension->modified ) ) ); ?></span>

						</div>

					</div>

				</div>

			<?php endforeach; ?>

		</div>

	</div>

</div>
