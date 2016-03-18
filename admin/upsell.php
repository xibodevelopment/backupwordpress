<?php namespace HM\BackUpWordPress; ?>
<div class="hmbkp-upsell">

<p>

<?php
/** translators:  the 1st placeholder is the first part of the anchor tag with the link to the extensions admin page and the second is the closing anchor tag */
printf(
	__( 'Store your backups securely in the Cloud with %1$sour extensions%2$s', 'backupwordpress' ),
	'<a href="' . esc_url( get_settings_url( HMBKP_PLUGIN_SLUG . '_extensions' ) ) . '">',
	'</a>'
);
?>

</p>

</div>
