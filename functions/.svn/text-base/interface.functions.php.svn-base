<?php
/**
 * Displays a row in the manage backups table
 *
 * @param string $file
 */
function hmbkp_get_backup_row( $file ) {

	$encode = base64_encode( $file['file'] ); ?>

	<tr class="hmbkp_manage_backups_row<?php if ( get_option( 'hmbkp_complete' ) ) : ?> completed<?php delete_option( 'hmbkp_complete' ); endif; ?>">

		<th scope="row">
			<?php echo date( get_option('date_format'), filemtime( $file['file'] ) ) . ' ' . date( 'H:i', filemtime($file['file'] ) ); ?>
		</th>

		<td>
			<?php echo hmbkp_size_readable( filesize( $file['file'] ) ); ?>
		</td>

		<td>

			<a href="tools.php?page=<?php echo HMBKP_PLUGIN_SLUG; ?>&amp;hmbkp_download=<?php echo $encode; ?>"><?php _e( 'Download', 'hmbkp' ); ?></a> |
			<a href="tools.php?page=<?php echo HMBKP_PLUGIN_SLUG; ?>&amp;hmbkp_delete=<?php echo $encode ?>" class="delete"><?php _e( 'Delete', 'hmbkp' ); ?></a>

		</td>

	</tr>

<?php }