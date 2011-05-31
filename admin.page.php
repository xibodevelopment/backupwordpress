<div class="wrap<?php if ( hmbkp_is_in_progress() ) { ?> hmbkp_running<?php } ?>">

	<?php screen_icon( 'backupwordpress' ); ?>

	<h2>

		<?php _e( 'Manage Backups', 'hmbkp' ); ?>

<?php if ( hmbkp_is_in_progress() ) : ?>
		<a class="button add-new-h2" <?php disabled( true ); ?>><img src="<?php echo site_url( 'wp-admin/images/wpspin_light.gif' ); ?>" width="16" height="16" /><?php echo hmbkp_get_status(); ?></a>

<?php elseif ( !hmbkp_possible() ) : ?>
		<a class="button add-new-h2" <?php disabled( true ); ?>><?php _e( 'Back Up Now', 'hmbkp' ); ?></a>

<?php else : ?>
		<a class="button add-new-h2" href="tools.php?page=<?php echo $_GET['page']; ?>&amp;action=hmbkp_backup_now"><?php _e( 'Back Up Now', 'hmbkp' ); ?></a>

<?php endif; ?>

		<a href="#hmbkp_advanced-options" class="button add-new-h2 hmbkp_advanced-options-toggle"><?php _e( 'Advanced Options' ); ?></a>

	</h2>

<?php if ( hmbkp_possible() ) : ?>

	<?php include_once( HMBKP_PLUGIN_PATH . '/admin.status.php' ); ?>

	<?php include_once( HMBKP_PLUGIN_PATH . '/admin.backups-table.php' ); ?>

<?php else : ?>

	<p><strong><?php _e( 'You need to fix the issues detailed above before BackUpWordPress can start.', 'hmbkp' ); ?></strong></p>

<?php endif; ?>

	<?php include_once( HMBKP_PLUGIN_PATH . '/admin.advanced-options.php' ); ?>
	
	<p class="howto"><?php printf( __( 'If you need help getting things working you are more than welcome to email us at %s and we\'ll do what we can to help.', 'hmbkp' ), '<a href="mailto:support@humanmade.co.uk">support@humanmade.co.uk</a>' ); ?></p>

</div>