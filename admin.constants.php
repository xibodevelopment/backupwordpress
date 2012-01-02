<div id="hmbkp-constants">

    <p><?php printf( __( 'You can %s any of the following %s in your %s to control advanced settings. %s. Defined %s will be highlighted.', 'hmbkp' ), '<code>define</code>', '<code>Constants</code>', '<code>wp-config.php</code>', '<a href="http://codex.wordpress.org/Editing_wp-config.php">' . __( 'The Codex can help', 'hmbkp' ) . '</a>', '<code>Constants</code>' ); ?></p>

    <dl>

        <dt<?php if ( defined( 'HMBKP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_PATH</code></dt>
        <dd><p><?php printf( __( 'The path to folder you would like to store your backup files in, defaults to %s.', 'hmbkp' ), '<code>' . hmbkp_path_default() . '</code>' ); ?></p><p class="example">e.g. <code>define( 'HMBKP_PATH', '/home/willmot/backups' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_MYSQLDUMP_PATH</code></dt>
        <dd><p><?php printf( __( 'The path to your %s executable. Will be used for the %s part of the back up if available.', 'hmbkp' ), '<code>mysqldump</code>', '<code>' . __( 'database', 'hmbkp' ) . '</code>' ); ?></p><p class="example">e.g. <code>define( 'HMBKP_MYSQLDUMP_PATH', '/opt/local/bin/mysqldump' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_ZIP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_ZIP_PATH</code></dt>
        <dd><p><?php printf( __( 'The path to your %s executable. Will be used to zip up your %s and %s if available.', 'hmbkp' ), '<code>zip</code>', '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<code>' . __( 'database', 'hmbkp' ) . '</code>' ); ?><p class="example">e.g. <code>define( 'HMBKP_ZIP_PATH', '/opt/local/bin/zip' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_DISABLE_AUTOMATIC_BACKUP' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_DISABLE_AUTOMATIC_BACKUP</code></dt>
        <dd><p><?php printf( __( 'Completely disables the automatic back up. You can still back up using the "Back Up Now" button. Defaults to %s.', 'hmbkp' ), '<code>(bool) false</code>' ); ?><p class="example">e.g. <code>define( 'HMBKP_DISABLE_AUTOMATIC_BACKUP', true );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_MAX_BACKUPS' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_MAX_BACKUPS</code></dt>
        <dd><p><?php printf( __( 'Number of backups to keep, older backups will be deleted automatically when a new backup is completed. Detaults to %s.', 'hmbkp' ), '<code>(int) 10</code>' ); ?><p class="example">e.g. <code>define( 'HMBKP_MAX_BACKUPS', 5 );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_FILES_ONLY' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_FILES_ONLY</code></dt>
        <dd><p><?php printf( __( 'Backup %s only, your %s will %s be backed up. Defaults to %s.', 'hmbkp' ), '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<code>' . __( 'database', 'hmbkp' ) . '</code>', '<strong>' . __( 'not', 'hmbkp' ) . '</strong>', '<code>(bool) false</code>' ); ?><p class="example">e.g. <code>define( 'HMBKP_FILES_ONLY', true );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_DATABASE_ONLY' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_DATABASE_ONLY</code></dt>
        <dd><p><?php printf( __( 'Backup %s only, your %s will %s be backed up. Defaults to %s.', 'hmbkp' ), '<code>' . __( 'database', 'hmbkp' ) . '</code>', '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<strong>' . __( 'not', 'hmbkp' ) . '</strong>', '<code>(bool) false</code>' ); ?><p class="example">e.g. <code>define( 'HMBKP_DATABASE_ONLY', true );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_DAILY_SCHEDULE_TIME' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_DAILY_SCHEDULE_TIME</code></dt>
        <dd><p><?php printf( __( 'The time that the daily back up should run. Defaults to %s.', 'hmbkp' ), '<code>23:00</code>' ); ?><p class="example">e.g. <code>define( 'HMBKP_DAILY_SCHEDULE_TIME', '07:30' );</code></p></dd>

    	<dt<?php if ( defined( 'HMBKP_EMAIL' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_EMAIL</code></dt>
        <dd><p><?php printf( __( 'Attempt to email a copy of your backups. Value should be email address to send backups to. Defaults to %s.', 'hmbkp' ), '<code>(bool) false</code>' ); ?><p class="example">e.g. <code>define( 'HMBKP_EMAIL', 'email@example.com' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_EXCLUDE' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_EXCLUDE</code></dt>
        <dd><p><?php _e( 'Comma separated list of files or directories to exclude, the backups directory is automatically excluded.', 'hmbkp' ); ?><p class="example">e.g. <code>define( 'HMBKP_EXCLUDE', '/wp-content/uploads/, /stats/, .svn/, *.txt' );</code></p></dd>
        
        <dt<?php if ( defined( 'HMBKP_CAPABILITY' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_CAPABILITY</code></dt>
        <dd><p><?php printf( __( 'The capability to use when calling %s. Defaults to %s.', 'hmbkp' ), '<code>add_menu_page</code>', '<code>manage_options</code>' ); ?><p class="example">e.g. <code>define( 'HMBKP_CAPABILITY', 'edit_posts' );</code></p></dd>

    </dl>

</div>