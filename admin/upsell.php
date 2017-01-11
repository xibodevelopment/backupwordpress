<?php namespace HM\BackUpWordPress; ?>
<div class="hmbkp-upsell">

<p>

<?php
echo whitelist_html(
	sprintf(
		/* translators: Link to plugin's extensions page in WordPress admin */
		__( 'Store your backups securely in the Cloud with <a href="%s">our extensions</a>', 'backupwordpress' ),
		esc_url( get_settings_url( HMBKP_PLUGIN_SLUG . '_extensions' ) )
	), 'a'
)

);
?>

</p>

</div>
