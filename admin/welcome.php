<div class="wrap about-wrap">

	<h1>Welcome to BackUpWordPress</h1>

	<div class="about-text">Thank you for installing BackUpWordPress, your site will soon be safely backed up. Follow the steps below to get going. Or you can <a href>Skip Setup</a> just use the plugin.</div>

	<div class="steps">

		<div class="step wp-filter collapsed">

			<header>
				<div class="icon"><span class="dashicons dashicons-marker"></span></div>
				<div class="title"><h2>Test the BackUp Process</h2></div>
			</header>

			<div class="detail">

				<?php $schedule = HM\BackUpWordPress\Schedules::get_instance()->get_schedule( 'complete' ); ?>

				<p>First things first, let's make sure we're able to create a backup of your site.</p>

				<?php // TODO should be able to manage excludes here
				//require_once( HMBKP_PLUGIN_PATH . 'admin/schedule-form-excludes.php' ); ?>

				<p><a href="" class="button">Verify Backup Process</a> </p>

			</div>

		</div>

		<div class="step wp-filter">

			<header>
				<div class="icon"><span class="dashicons dashicons-yes"></span></div>
				<div class="title"><h2>Store your backups somewhere safe</h2></div>
			</header>

			<div class="detail">

				<p>It's important that you store your backups in a safe place, not on the same server as your website. As standard BackUpWordPress can copy your backups to a remote FTP server. We also sell extensions which allow you to store backups in Dropbox, Google Drive and other Cloud providers.</p>

				<p><a href="" class="button">View available extensions &rarr;</a></p>				
				
				<?php //require_once( HMBKP_PLUGIN_PATH . 'admin/upsell.php' ); ?>

			</div>

		</div>

		<?php if ( ! get_option( 'hmbkp_enable_support' ) ) : ?>

			<div class="step wp-filter">

				<header>
					<div class="icon"><span class="dashicons dashicons-admin-users"></span></div>
					<div class="title"><h2>Enable Support</h2></div>
				</header>

				<div class="detail">

					<?php add_thickbox(); ?>

					<p>BackUpWordPress comes with fantastic support, if you're ever in need, we're here to help.</p>

					<p>
						<a class="thickbox button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'load_enable_support', 'width' => '600', 'height' => '420' ), self_admin_url( 'admin-ajax.php' ) ), 'hmbkp_nonce' ) ); ?>"><?php _e( 'Enable Support', 'backupwordpress' ); ?></a>
					</p>
				
				</div>

			</div>

		<?php else : ?>

			<div class="step wp-filter">

				<header>
					<h2><span class="dashicons dashicons-yes"></span> Support enabled, good job!</h2>
				</header>

			</div>

		<?php endif; ?>

	</div>

</div>