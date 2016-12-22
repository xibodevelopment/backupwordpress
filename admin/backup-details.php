<?php

namespace HM\BackUpWordPress;

$backup = base64_decode( urldecode( $_GET['hmbkp_backup_archive'] ) );

?>

<div class="wrap">

	<h1>

		<a class="page-title-action" href="<?php echo esc_url( get_settings_url() ); ?>"><?php _e( '&larr; Backups', 'backupwordpress' ); ?></a>

		<?php printf( esc_html__( 'Backup from %s', 'backupwordpress' ), date_i18n( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), @filemtime( $backup ) + $offset ) ); ?>

	</h1>

	<?php $zip = new \ZipArchive;

	if ( ! $zip->open( $backup ) ) { ?>

		<div class="error notice">
			<p>Unable to open backup.</p>
		</div>

	<?php } ?>

	<div class="wp-filter">
		<h2>Details</h2>
	</div>

	<?php
		if ( $zip->getArchiveComment() ) {
			$metadata = json_decode( $zip->getArchiveComment() ); ?>

			<table class="widefat">
				<thead>
					<tr>
						<?php foreach ( $metadata as $key => $value ) { ?>
							<th scope="col"><?php echo esc_html( $key ); ?></th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<tr>
						<?php foreach ( $metadata as $key => $value ) { ?>
							<td><?php echo esc_html( $value ); ?></td>
						<?php } ?>
					</tr>
				</tbody>
			</table>

		<?php } ?>


<div class="wp-filter">
	<?php
     for( $i = 0; $i < $zip->numFiles; $i++ ) {
          echo 'Filename: ' . $zip->getNameIndex( $i ) . '<br />';
     }
?>
</div>
