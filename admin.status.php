<p>&#10003;

<?php if ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) && HMBKP_DISABLE_AUTOMATIC_BACKUP && !wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) ) : ?>

    <?php printf( __( 'Automatic backups are %s.', 'hmbkp' ), '<strong>' . __( 'disabled', 'hmbkp' ) . '</strong>' ); ?>

<?php else :

    if ( ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY || !defined( 'HMBKP_FILES_ONLY' ) ) && ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY || !defined( 'HMBKP_DATABASE_ONLY' ) ) )
    	$what_to_backup = '<code>' . __( 'database', 'hmbkp' ) . '</code> ' . __( '&amp;', 'hmbkp' ) . ' <code>' . __( 'files', 'hmbkp' ) . '</code>';

    elseif( defined( 'HMBKP_DATABASE_ONLY' ) && HMBKP_DATABASE_ONLY )
    	$what_to_backup = '<code>' . __( 'database', 'hmbkp' ) . '</code>';

    else
    	$what_to_backup = '<code>' . __( 'files', 'hmbkp' ) . '</code>'; ?>

    <?php printf( __( 'Your %s will be automatically backed up every day at %s to %s.', 'hmbkp' ), $what_to_backup , '<code title="' . sprintf( __( 'It\'s currently %s on the server.', 'hmbkp' ), date( 'H:i' ) ) . '">' . date( 'H:i', wp_next_scheduled( 'hmbkp_schedule_backup_hook' ) ) . '</code>', '<code>' . hmbkp_path() . '</code>' ); ?>

<?php endif; ?>

<p>&#10003; <span class="hmbkp_estimated-size"><?php printf( __( 'Your site is %s. Backups will be compressed and should be smaller than this.', 'hmbkp' ), get_transient( 'hmbkp_estimated_filesize' ) ? '<code>' . hmbkp_calculate() . '</code>' : '<code class="calculate">' . __( 'Calculating Size...', 'hmbkp' ) . '</code>' ); ?></span></p>

<?php if ( !hmbkp_shell_exec_available() ) : ?>
<p>&#10007; <?php printf( __( '%s is disabled which means we have to use the slower PHP fallbacks, you could try contacting your host and asking them to enable it.', 'hmbkp' ), '<code>shell_exec</code>' ); ?>
<?php endif; ?>

<?php if ( hmbkp_shell_exec_available() ) : ?>

    <?php if ( hmbkp_zip_path() && ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY || !defined( 'HMBKP_DATABASE_ONLY' ) ) ) : ?>
<p>&#10003; <?php printf( __( 'Your %s will be backed up using %s.', 'hmbkp' ), '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<code>' . hmbkp_zip_path() . '</code>' ); ?></p>
    <?php endif; ?>

    <?php if ( hmbkp_mysqldump_path() && ( defined( 'HMBKP_FILES_ONLY' ) && !HMBKP_FILES_ONLY || !defined( 'HMBKP_FILES_ONLY' ) ) ) : ?>
<p>&#10003; <?php printf( __( 'Your %s will be backed up using %s.', 'hmbkp' ), '<code>' . __( 'database', 'hmbkp' ) . '</code>', '<code>' . hmbkp_mysqldump_path() . '</code>' ); ?></p>
    <?php endif; ?>

<?php endif; ?>

<?php if ( defined( 'HMBKP_EMAIL' ) && HMBKP_EMAIL && is_email( HMBKP_EMAIL ) ) : ?>
<p>&#10003; <?php printf( __( 'A copy of each backup will be emailed to %s.', 'hmbkp' ), '<code>' . HMBKP_EMAIL . '</code>' ); ?></p>
<?php endif; ?>

<?php if ( ( $valid_excludes = hmbkp_valid_custom_excludes() ) && ( !defined( 'HMBKP_DATABASE_ONLY' ) || ( defined( 'HMBKP_DATABASE_ONLY' ) && !HMBKP_DATABASE_ONLY ) ) ) : ?>
<p>&#10003; <?php printf( __( 'The following paths will be excluded from your backups %s.', 'hmbkp' ), '<code>' . implode( '</code>, <code>', $valid_excludes ) . '</code>' ); ?></p>
<?php endif; ?>