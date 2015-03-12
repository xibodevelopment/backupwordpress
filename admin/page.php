<div class="wrap">

	<?php if ( hmbkp_possible() ) : ?>

		<?php include_once( HMBKP_PLUGIN_PATH . 'admin/backups.php' ); ?>

		<p class="howto"><?php printf( __( 'If you\'re finding BackUpWordPress useful, please %1$s rate it on the plugin directory%2$s.', 'backupwordpress' ), '<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/backupwordpress">', '</a>' ); ?></p>

		<?php include_once( HMBKP_PLUGIN_PATH . 'admin/upsell.php' ); ?>

	<?php endif; ?>

</div>
