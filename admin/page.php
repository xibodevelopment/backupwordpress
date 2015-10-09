<?php if ( hmbkp_is_first_run() ) : ?>

	<div class="wrap about-wrap">

		<h1>Welcome to BackUpWordPress</h1>

		<div class="about-text">Thank you for installing BackUpWordPress, your site will soon be safely backed up. Follow the steps below to get going. Or you can <a href>Skip Setup</a>.</div>

		<div class="steps">

			<div class="step wp-filter collapsed">

				<header>
					<div class="icon"><span class="dashicons dashicons-marker"></span></div>
					<div class="title"><h2>Test the BackUp Process</h2></div>
				</header>

				<div class="detail">


				</div>

			</div>

			<div class="step wp-filter">

				<header>
					<div class="icon"><span class="dashicons dashicons-yes"></span></div>
					<div class="title"><h2>Test the BackUp Process</h2></div>
				</header>

				<div class="detail">


				</div>

			</div>

			<div class="step wp-filter collapsed">

				<header>
					<div class="icon"><span class="dashicons dashicons-no"></span></div>
					<div class="title"><h2>Test the BackUp Process</h2></div>
				</header>

				<div class="detail">


				</div>

			</div>

		</div>

	</div>

<?php return;
endif; ?>

<div class="wrap">

	<h1>
		BackUpWordPress

		<?php if ( get_option( 'hmbkp_enable_support' ) ) { ?>
			<a class="page-title-action" href="mailto:backupwordpress@hmn.md"><?php _e( 'Support', 'backupwordpress' ); ?></a>
		<?php } else {
			add_thickbox(); ?>
			<a id="intercom-info" class="thickbox page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'load_enable_support', 'width' => '600', 'height' => '420' ), self_admin_url( 'admin-ajax.php' ) ), 'hmbkp_nonce' ) ); ?>"><span class="dashicons dashicons-admin-users"></span>&nbsp;<?php _e( 'Enable Support', 'backupwordpress' ); ?></a>
		<?php } ?>
	</h1>

	<?php if ( hmbkp_possible() ) : ?>

		<?php include_once( HMBKP_PLUGIN_PATH . 'admin/backups.php' ); ?>

		<p class="howto"><?php printf( __( 'If you\'re finding BackUpWordPress useful, please %1$s rate it on the plugin directory%2$s.', 'backupwordpress' ), '<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/backupwordpress">', '</a>' ); ?></p>

		<?php include_once( HMBKP_PLUGIN_PATH . 'admin/upsell.php' ); ?>

	<?php endif; ?>

</div>
