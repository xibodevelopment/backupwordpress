<?php namespace HM\BackUpWordPress; ?>
<div class="hmbkp-upsell">

<p>

<?php
printf(
	wp_kses(
		/* translators: Link to plugin's extensions page in WordPress admin */
		__( 'Store your backups securely in the Cloud with <a href="%s">our extensions</a>', 'backupwordpress' ),
		array(
			'a' => array(
				'href' => array(),
			),
		)
	),
	esc_url( get_settings_url( HMBKP_PLUGIN_SLUG . '_extensions' ) )
);
?>

</p>

</div>
