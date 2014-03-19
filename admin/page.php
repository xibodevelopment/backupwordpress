<div class="wrap">

<?php if ( hmbkp_possible() ) : ?>

	<?php include_once( HMBKP_PLUGIN_PATH . 'admin/backups.php' ); ?>

	<p class="howto"><?php printf( __( 'If you\'re finding BackUpWordPress useful, please %1$s rate it on the plugin directory. %2$s', 'hmbkp' ), '<a href="http://wordpress.org/support/view/plugin-reviews/backupwordpress">', '</a>' ); ?></p>

	<p class="howto"><?php _e( 'If you need help getting things working then check the FAQ by clicking on help in the top right hand corner of this page.', 'hmbkp' ); ?></p>

	<p class="howto"><strong><?php printf( __( 'Wish you could store your backups in a safer place? Our %1$spremium extensions%2$s enable automatic backups to Dropbox, FTP, Google Drive and more.', 'hmbkp' ), '<a href="https://bwp.hmn.md/?utm_source=wordpress-org&utm_medium=wp-admin&utm_campaign=freeplugin">', '</a>' ); ?></strong></p>

<?php endif; ?>

</div>
