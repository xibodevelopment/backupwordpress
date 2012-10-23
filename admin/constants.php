<div id="hmbkp-constants">

    <p><?php printf( __( 'You can %1$s any of the following %2$s in your %3$s to control advanced settings. %4$s. Defined %5$s will be highlighted.', 'hmbkp' ), '<code>define</code>', '<code>' . __( 'Constants', 'hmbkp' ) . '</code>', '<code>wp-config.php</code>', '<a href="http://codex.wordpress.org/Editing_wp-config.php">' . __( 'The Codex can help', 'hmbkp' ) . '</a>', '<code>' . __( 'Constants', 'hmbkp' ) . '</code>' ); ?></p>

    <dl>

        <dt<?php if ( defined( 'HMBKP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_PATH</code></dt>
        <dd><p><?php printf( __( 'The path to folder you would like to store your backup files in, defaults to %s.', 'hmbkp' ), '<code>' . hmbkp_path_default() . '</code>' ); ?></p><p class="example"><?php _e( 'e.g.', 'hmbkp' ); ?> <code>define( 'HMBKP_PATH', '/home/willmot/backups' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_MYSQLDUMP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_MYSQLDUMP_PATH</code></dt>
        <dd><p><?php printf( __( 'The path to your %1$s executable. Will be used for the %2$s part of the back up if available.', 'hmbkp' ), '<code>mysqldump</code>', '<code>' . __( 'database', 'hmbkp' ) . '</code>' ); ?></p><p class="example"><?php _e( 'e.g.', 'hmbkp' ); ?> <code>define( 'HMBKP_MYSQLDUMP_PATH', '/opt/local/bin/mysqldump' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_ZIP_PATH' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_ZIP_PATH</code></dt>
        <dd><p><?php printf( __( 'The path to your %1$s executable. Will be used to zip up your %2$s and %3$s if available.', 'hmbkp' ), '<code>zip</code>', '<code>' . __( 'files', 'hmbkp' ) . '</code>', '<code>' . __( 'database', 'hmbkp' ) . '</code>' ); ?><p class="example"><?php _e( 'e.g.', 'hmbkp' ); ?> <code>define( 'HMBKP_ZIP_PATH', '/opt/local/bin/zip' );</code></p></dd>

    	<dt<?php if ( defined( 'HMBKP_EMAIL' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_EMAIL</code></dt>
        <dd><p><?php printf( __( 'Attempt to email a copy of your backups. Value should be email address to send backups to. Defaults to %s.', 'hmbkp' ), '<code>(bool) false</code>' ); ?><p class="example"><?php _e( 'e.g.', 'hmbkp' ); ?> <code>define( 'HMBKP_EMAIL', 'email@example.com' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_EXCLUDE' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_EXCLUDE</code></dt>
        <dd><p><?php _e( 'Comma separated list of files or directories to exclude, the backups directory is automatically excluded.', 'hmbkp' ); ?><p class="example"><?php _e( 'e.g.', 'hmbkp' ); ?> <code>define( 'HMBKP_EXCLUDE', '/wp-content/uploads/, /stats/, .svn/, *.txt' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_CAPABILITY' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_CAPABILITY</code></dt>
        <dd><p><?php printf( __( 'The capability to use when calling %1$s. Defaults to %2$s.', 'hmbkp' ), '<code>add_menu_page</code>', '<code>manage_options</code>' ); ?><p class="example"><?php _e( 'e.g.', 'hmbkp' ); ?> <code>define( 'HMBKP_CAPABILITY', 'edit_posts' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_ROOT' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_ROOT</code></dt>
        <dd><p><?php printf( __( 'The root directory that is backed up. Defaults to %s.', 'hmbkp' ), '<code>' . HM_Backup::get_home_path() . '</code>' ); ?><p class="example"><?php _e( 'e.g.', 'hmbkp' ); ?> <code>define( 'HMBKP_ROOT', ABSPATH . 'wp/' );</code></p></dd>

        <dt<?php if ( defined( 'HMBKP_SCHEDULE_TIME' ) ) { ?> class="hmbkp_active"<?php } ?>><code>HMBKP_SCHEDULE_TIME</code></dt>
        <dd><p><?php printf( __( 'The time that your schedules should run. Defaults to %s.', 'hmbkp' ), '<code>23:00</code>' ); ?><p class="example"><?php _e( 'e.g.', 'hmbkp' ); ?> <code>define( 'HMBKP_SCHEDULE_TIME', '07:30' );</code></p></dd>

    </dl>

</div>