<ul>

	<?php foreach ( HMBKP_Requirements::get_requirements() as $requirement ) : ?>

		<li><?php echo $requirement->name(); ?> : <code><?php echo $requirement->result(); ?></code></li>

	<?php endforeach; ?>

</ul>