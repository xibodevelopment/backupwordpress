<?php $display = get_option( 'hmbkp_upsell' );

if ( isset( $_GET['hmbkp_upsell'] ) ) {

	if ( ! $display || 'shown' === $display ) {

		update_option( 'hmbkp_upsell', 'hidden' );
	};
	if ( 'hidden' === $display ) {

		update_option( 'hmbkp_upsell', 'shown' );

	};
}; ?>

<div class="hmbkp-upsell">

	<h3>
		Want to backup to any of our remote destinations and receive priority support? 

		<span class="howto">just &dollar;24 each per site.</span>

		<a href="<?php echo add_query_arg( 'hmbkp_upsell', '1' ); ?>">
			<span class="hmbkp_hide add-new-h2">Show/Hide</span>
		</a>

	</h3>

	<?php if ( ! get_option( 'hmbkp_upsell' ) || get_option( 'hmbkp_upsell' ) === 'shown' ) { ?>
	<ul>

		<li class="manage-menus">
			<a target="_blank" href="http://bwp.hmn.md/downloads/backupwordpress-to-dropbox/">
				<img src="<?php echo HMBKP_PLUGIN_URL; ?>/assets/d-dropbox-alt.png" alt="Dropbox" />
			</a>
		</li>

		<li class="manage-menus">
			<a target="_blank" href="http://bwp.hmn.md/downloads/backupwordpress-to-google-drive/">
				<img src="<?php echo HMBKP_PLUGIN_URL; ?>/assets/d-drive-alt.png" alt="Google Drive" />
			</a>
		</li>

		<li class="manage-menus">
			<a target="_blank" href="http://bwp.hmn.md/downloads/backupwordpress-to-amazon-s3/">
				<img src="<?php echo HMBKP_PLUGIN_URL; ?>/assets/d-s3-alt.png" alt="Amazon S3" />
			</a>
		</li>

		<li class="manage-menus">
			<a target="_blank" href="http://bwp.hmn.md/downloads/backupwordpress-to-ftp/">
				<img src="<?php echo HMBKP_PLUGIN_URL; ?>/assets/d-ftp-alt.png" alt="FTP/SFTP" />
			</a>
		</li>

		<li class="manage-menus">
			<a target="_blank" href="http://bwp.hmn.md/downloads/backupwordpress-to-rackspace-cloud/" alt="Rackspace Cloud">
				<img src="<?php echo HMBKP_PLUGIN_URL; ?>/assets/d-rack-alt.png" />
			</a>
		</li>

		<li class="manage-menus">
			<a target="_blank" href="http://bwp.hmn.md/downloads/backupwordpress-to-windows-azure/">
				<img src="<?php echo HMBKP_PLUGIN_URL; ?>/assets/d-azure-alt.png" alt="Windows Azure" />
			</a>
		</li>

		<li class="manage-menus">
			<a target="_blank" href="http://bwp.hmn.md/downloads/backupwordpress-to-dreamobjects/">
				<img src="<?php echo HMBKP_PLUGIN_URL; ?>/assets/d-dream-alt.png" alt="Dreamhost Dream Objects" />
			</a>
		</li>

		<!--
		<li class="manage-menus">
			<a target="_blank" href="http://hmn.dev/checkout?edd_action=add_to_cart&amp;download_id=36">
				<img src="" alt="Buy the Developer Bundle now for &dollar; 99. All Destinations &amp; Unlimited Sites" />
			</a>
		</li>
		-->

	</ul> 
	<?php }; ?>
</div>