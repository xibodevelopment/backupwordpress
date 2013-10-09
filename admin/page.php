<div class="wrap">

	<?php screen_icon( HMBKP_PLUGIN_SLUG ); ?>

	<h2>
		<?php _e( 'Manage Backups', 'hmbkp' ); ?>
		<a id="Intercom" class="add-new-h2" href="mailto:support@hmn.md"><?php _e( 'Support', 'hmbkp' ); ?></a>
	</h2>

<?php if ( hmbkp_possible() ) : ?>

	<?php include_once( HMBKP_PLUGIN_PATH . '/admin/backups.php' ); ?>

<?php else : ?>

	<p><strong><?php _e( 'You need to fix the issues detailed above before BackUpWordPress can start.', 'hmbkp' ); ?></strong></p>

<?php endif; ?>

	<p class="howto"><?php printf( __( 'If you\'re finding BackUpWordPress useful, please %1$s rate it on the plugin directory. %2$s', 'hmbkp' ), '<a href="http://wordpress.org/support/view/plugin-reviews/backupwordpress">', '</a>' ); ?></p>

	<p class="howto"><?php printf( __( 'If you need help getting things working then check the FAQ by clicking on help in the top right hand corner of this page.', 'hmbkp' ), '<a href="mailto:support@hmn.md">support@hmn.md</a>' ); ?></p>

	<p class="howto"><?php printf( __( 'Wish you could store your backups in a safer place? Our %1$spremium extensions%2$s enable automatic backups to Dropbox, FTP, Google Drive and more.', 'hmbkp' ), '<a href="http://bwp.hmn.md">', '</a>' ); ?></p>

	<p id="optin-message" class="howto">

		<?php $opted_in = get_option( 'hmbkp_intercom_opt_in' ); ?>

		<input id="toggle_optin_value" type="checkbox" <?php checked( $opted_in ); ?>>

		<?php printf( __( 'Allow BackUpWordPress to send information about my configuration for support purposes.', 'hmbkp' ) ); ?>

		<a id="intercom-info" class="button secondary" href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'load_server_info' ), admin_url( 'admin-ajax.php' ) ), 'hmbkp_nonce' ); ?>">More info</a>

	</p>

</div>
