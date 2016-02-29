<?php

namespace HM\BackUpWordPress;

?>

<div class="wrap">

	<h1>

		<a class="page-title-action" href="<?php echo get_settings_url() ?>"><?php _e( '&larr; Backups', 'backupwordpress' ); ?></a>

		<?php _e( 'BackUpWordPress Extensions', 'backupwordpress' ); ?>

	</h1>

	<div class="wp-filter">
		<p><?php _e( 'Extend BackUpWordPress by installing extensions. Extensions allows you to pick and choose the exact features you need whilst also supporting us, the developers, so we can continue working on BackUpWordPress.', 'backupwordpress' ); ?></p>
	</div>

	<?php
	$extensions_data = Extensions::get_instance()->get_edd_data();
	$installed_plugins = array_map( 'strtolower' , wp_list_pluck( get_plugins(), 'Name' ) );
	?>

	<h3><?php _e( 'Remote Storage', 'backupwordpress' ); ?></h3>

	<p><?php _e( 'It\'s important to store your backups somewhere other than on your site. Using the extensions below you can easily push your backups to one or more Cloud providor.', 'backupwordpress' ); ?></p>

	<div class="wp-list-table widefat plugin-install">
		<div id="the-list">

			<?php foreach ( $extensions_data as $extension ) : ?>

				<div class="plugin-card plugin-card-<?php echo esc_attr( $extension->slug ); ?>">

					<div class="plugin-card-top">

						<div class="name column-name">
							<h4>
								<a href="<?php echo esc_url( $extension->link ); ?>" class="thickbox">
									<?php echo esc_html( $extension->title->rendered ); ?>
									<img src="<?php echo esc_url( $extension->featured_image_thumbnail_url ); ?>" class="plugin-icon" alt="">
								</a>
							</h4>
						</div>

						<div class="action-links">
							<ul class="plugin-action-buttons">
								<li>
									<?php if ( in_array( strtolower( $extension->title->rendered ), $installed_plugins ) ) : ?>
										<span class="button button-disabled" title="<?php _e( 'This extension is already installed and is up to date', 'backupwordpress' ); ?>"><?php _e( 'Installed', 'backupwordpress' ); ?></span>
									<?php else : ?>
										<a class="install-now button-primary" data-slug="<?php echo esc_attr( $extension->slug ); ?>" href="<?php echo esc_url( $extension->link ); ?>" aria-label="Install <?php echo esc_attr( $extension->title->rendered ); ?> now" data-name="<?php echo esc_attr( $extension->title->rendered ); ?>"><?php _e( 'Buy Now &dollar;', 'backupwordpress' ); ?><?php echo esc_html( $extension->edd_price ); ?></a>
									<?php endif; ?>
								</li>
								<li>
									<a href="<?php echo esc_url( $extension->link ); ?>" class="thickbox" aria-label="<?php printf( __( 'More information about %s', 'backupwordpress' ), esc_attr( $extension->title->rendered ) ) ; ?>" data-title="<?php echo esc_attr( $extension->title->rendered ); ?>"><?php _e( 'More Details', 'backupwordpress' ); ?></a>
								</li>
							</ul>
						</div>

						<div class="desc column-description">
							<p><?php echo wp_kses_post( $extension->content->rendered ); ?></p>
							<p class="authors"> <cite><?php _e( 'By', 'backupwordpress' ); ?> <a href="https://hmn.md">Human Made Limited</a></cite></p>
						</div>
					</div>

					<div class="plugin-card-bottom">
						<div class="column-updated">
							<strong>Last Updated:</strong> <span title="<?php echo esc_attr( $extension->modified ); ?>"><?php echo esc_html( human_time_diff( strtotime( $extension->modified ) ) ); ?></span>
						</div>
					</div>
				</div>

			<?php endforeach; ?>

		</div>
	</div>

</div>
