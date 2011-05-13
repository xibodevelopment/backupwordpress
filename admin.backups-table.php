<?php $backup_archives = hmbkp_get_backups();
if ( count( $backup_archives ) ) :
	hmbkp_delete_old_backups(); ?>

<table class="widefat" id="hmbkp_manage_backups_table">
    <thead>
    	<tr>
    		<th scope="col"><?php printf( __( '%d Completed backups', 'hmbkp' ), count( $backup_archives ) ); ?></th>
    		<th scope="col"><?php _e( 'Size', 'hmbkp' ); ?></th>
    		<th scope="col"><?php _e( 'Actions', 'hmbkp' ); ?></th>
    	</tr>
    </thead>

    <tfoot>
    	<tr>
    		<th><?php printf( _n( 'Only the most recent backup will be saved', 'The %d most recent backups will be saved', hmbkp_max_backups(), 'hmbkp' ), hmbkp_max_backups() ); ?></th>
    		<th><?php printf( __( 'Total %s, %s available', 'hmbkp' ), hmbkp_total_filesize(), hmbkp_size_readable( disk_free_space( ABSPATH ), null, '%01u %s' ) ); ?></th>
    		<th></th>
    	</tr>
    </tfoot>

    <tbody id="the-list">

    <?php foreach ( (array) $backup_archives as $file ) :

        if ( !file_exists( $file ) )
        	continue;

        hmbkp_get_backup_row( $file );

    endforeach; ?>

    </tbody>
</table>

<?php endif; ?>