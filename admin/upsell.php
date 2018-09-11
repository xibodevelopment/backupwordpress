<?php namespace HM\BackUpWordPress; ?>
<div class="hmbkp-upsell">

<?php
/** translators:  the 1st placeholder is the first part of the anchor tag with the link to the plugin review page and the second is the closing anchor tag */
$cta_message = sprintf(
	__( 'If you\'re finding BackUpWordPress useful, please %1$s rate it on the plugin directory%2$s.', 'backupwordpress' ),
	'<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/backupwordpress">',
	'</a>'
);
$hide_notice = get_site_option( 'hmbkp_hide_rate_notice', false );
if(empty($hide_notice) || time() >= $hide_notice) {
?>
<div id="hmbkp-cta-message" class="updated rate notice is-dismissible">
    <p><?php echo wp_kses_post( $cta_message ); ?></p>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'backupwordpress' ); ?></span></button>
</div>

</div>
<?php
}