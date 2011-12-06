<?php

// If max backups has changed
if ( ! hmbkp_is_in_progress() )
	hmbkp_delete_old_backups();

if ( ( $backup_archives = hmbkp_get_backups() ) && count( $backup_archives ) ) : ?>

<table class="widefat" id="hmbkp_manage_backups_table">
    <thead>
    	<tr>
    		<th scope="col"><?php printf( _n( '1 backup completed', '%d backups completed', count( $backup_archives ),  'hmbkp' ), count( $backup_archives ) ); ?></th>
    		<th scope="col"><?php _e( 'Size', 'hmbkp' ); ?></th>
    		<th scope="col"><?php _e( 'Actions', 'hmbkp' ); ?></th>
    	</tr>
    </thead>

    <tfoot>
    	<tr>
    		<th><?php printf( _n( 'Only the most recent backup will be saved', 'The %d most recent backups will be saved', hmbkp_max_backups(), 'hmbkp' ), hmbkp_max_backups() ); ?></th>
    		<th><?php printf( __( 'Total %s', 'hmbkp' ), hmbkp_total_filesize() ); ?></th>
    		<th></th>
    	</tr>
    </tfoot>

    <tbody id="the-list">

    <?php foreach ( (array) $backup_archives as $file ) :

        if ( ! file_exists( $file ) )
        	continue;

        hmbkp_get_backup_row( $file );

    endforeach; ?>

    </tbody>
</table>

<?php endif; ?>