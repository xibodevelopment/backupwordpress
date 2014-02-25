<div class="wrap">

	<h2>
		<?php _e( 'Manage Backups', 'backupwordpress' ); ?>

		<?php if ( get_option( 'hmbkp_enable_support' ) ) { ?>

		<a id="intercom" class="add-new-h2" href="mailto:support@hmn.md"><?php _e( 'Support', 'backupwordpress' ); ?></a>

		<?php } else { ?>

		<a id="intercom-info" class="colorbox add-new-h2" href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'load_enable_support' ), is_multisite() ? admin_url( 'admin-ajax.php' ) : network_admin_url( 'admin-ajax.php' ) ), 'hmbkp_nonce' ); ?>"><?php _e( 'Enable Support', 'hmbkp' ); ?></a>

		<?php } ?>


	</h2>

<?php if ( hmbkp_possible() ) : ?>

	<?php include_once( HMBKP_PLUGIN_PATH . '/admin/backups.php' ); ?>

<?php else : ?>

	<p><strong><?php _e( 'You need to fix the issues detailed above before BackUpWordPress can start.', 'backupwordpress' ); ?></strong></p>

<?php endif; ?>

	<p class="howto"><?php printf( __( 'If you\'re finding BackUpWordPress useful, please %1$s rate it on the plugin directory. %2$s', 'backupwordpress' ), '<a href="http://wordpress.org/support/view/plugin-reviews/backupwordpress">', '</a>' ); ?></p>

	<p class="howto"><?php _e( 'If you need help getting things working then check the FAQ by clicking on help in the top right hand corner of this page.', 'backupwordpress' ); ?></p>

	<p class="howto"><strong><?php printf( __( 'Wish you could store your backups in a safer place? Our %1$spremium extensions%2$s enable automatic backups to Dropbox, FTP, Google Drive and more.', 'backupwordpress' ), '<a href="http://bwp.hmn.md">', '</a>' ); ?></strong></p>


</div>