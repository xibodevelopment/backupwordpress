<?php

namespace HM\BackUpWordPress;

?>

<div class="wrap">

	<h1>

		BackUpWordPress

	</h1>

	<?php if ( has_server_permissions() ) : ?>

		<?php include_once( HMBKP_PLUGIN_PATH . 'admin/backups.php' ); ?>
  
		<?php include_once( HMBKP_PLUGIN_PATH . 'admin/upsell.php' ); ?>

	<?php else : ?>

		<?php include_once( HMBKP_PLUGIN_PATH . 'admin/filesystem-credentials.php' ); ?>

	<?php endif; ?>

</div>
