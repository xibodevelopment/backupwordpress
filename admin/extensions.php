<?php

namespace HM\BackUpWordPress;

?>

<div class="wrap">

	<h1>

		<a class="page-title-action" href="<?php echo get_settings_url() ?>">&larr; Backups</a>

		BackUpWordPress Extensions

	</h1>

	<div class="wp-filter">
		<p>Extend BackUpWordPress by installing extensions. Extensions allows you to pick and choose the exact features you need whilst also supporting us, the developers, so we can continue working on BackUpWordPress.</p>
	</div>

	<?php
	$extensions_data = Extensions::get_instance()->fetch_data();
	$installed_plugins = array_map( 'strtolower' , wp_list_pluck( get_plugins(), 'Name' ) );
	?>

	<h3>Remote Storage</h3>

	<p>It's important to store your backups somewhere other than on your site. Using the extensions below you can easily push your backups to one or more Cloud providor.</p>

	<div class="wp-list-table widefat plugin-install">
		<div id="the-list">

			<?php foreach ( $extensions_data as $extension ) : ?>

				<div class="plugin-card plugin-card-<?php echo esc_attr( $extension->slug ); ?>">

					<div class="plugin-card-top">

						<div class="name column-name">
							<h4>
								<a href="<?php echo esc_url( $extension->link ); ?>" class="thickbox">
									<?php echo esc_html( $extension->title->rendered ); ?>
									<img src="<?php // echo esc_url( $extension->icon ); ?>" class="plugin-icon" alt="">
								</a>
							</h4>
						</div>

						<div class="action-links">
							<ul class="plugin-action-buttons">
								<li>
									<?php if ( in_array( strtolower( $extension->title->rendered ), $installed_plugins ) ) : ?>
										<span class="button button-disabled" title="This extension is already installed and is up to date ">Installed</span>
									<?php else : ?>
										<a class="install-now button-primary" data-slug="<?php echo esc_attr( $extension->slug ); ?>" href="<?php echo esc_url( $extension->link ); ?>" aria-label="Install <?php echo esc_attr( $extension->title->rendered ); ?> now" data-name="<?php echo esc_attr( $extension->title->rendered ); ?>">Buy Now &dollar;<?php echo esc_html( $extension->edd_price ); ?></a>
									<?php endif; ?>
								</li>
								<li>
									<a href="<?php echo esc_url( $extension->link ); ?>" class="thickbox" aria-label="More information about <?php echo esc_attr( $extension->title->rendered ); ?>" data-title="<?php echo esc_attr( $extension->title->rendered ); ?>">More Details</a>
								</li>
							</ul>
						</div>

						<div class="desc column-description">
							<p><?php echo wp_kses_post( $extension->content->rendered ); ?></p>
							<p class="authors"> <cite>By <a href="<?php //echo esc_url( $extension->author_url ); ?>"><?php echo esc_html( $extension->author ); ?></a></cite></p>
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
