<?php
echo '<p><strong>' . __( 'Where does BackUpWordPress store the backup files?', 'hmbkp' ) . '</strong></p>' .

'<p>' . __( 'Backups are stored on your server in `/wp-content/backups`, you can change the directory.', 'hmbkp' ) . '</p>' .

'<p>' . __( 'Important: By default BackUpWordPress backs up everything in your site root as well as your database, this includes any non WordPress folders that happen to be in your site root. This does means that your backup directory can get quite large.', 'hmbkp' ) . '</p>' .

'<p><strong>' . __( 'What if I want to back up my site to another destination?', 'hmbkp' ) . '</strong></p>' .

'<p>' . __( 'BackUpWordPress Pro supports Dropbox, Google Drive, Amazon S3, Rackspace, Azure, DreamObjects and FTP/SFTP. Check it out here: [https://bwp.hmn.md](http://bwp.hmn.md/?utm_source=wordpress-org&utm_medium=plugin-page&utm_campaign=freeplugin)', 'hmbkp' ) . '</p>' .

'<p><strong>' . __( 'How do I restore my site from a backup?', 'hmbkp' ) . '</strong></p>' .

'<p>' . __( 'You need to download the latest backup file either by clicking download on the backups page or via `FTP`. `Unzip` the files and upload all the files to your server overwriting your site. You can then import the database using your hosts database management tool (likely `phpMyAdmin`).', 'hmbkp' ) . '</p>' .

'<p>' . __( 'See this post for more details http://hmn.md/backupwordpress-how-to-restore-from-backup-files/.', 'hmbkp' ) . '</p>' .

'<p><strong>' . __( 'Does BackUpWordPress back up the backups directory?', 'hmbkp' ) . '</strong></p>' .

'<p>' . __( 'No.', 'hmbkp' ) . '</p>' .

'<p>' . __( 'I\'m not receiving my backups by email', 'hmbkp' ) . '</p>' .

'<p>' . __( 'Most servers have a filesize limit on email attachments, it\'s generally about 10mb. If your backup file is over that limit it won\'t be sent attached to the email, instead you should receive an email with a link to download the backup, if you aren\'t even receiving that then you likely have a mail issue on your server that you\'ll need to contact your host about.', 'hmbkp' ) . '</p>' .

'<p><strong>' . __( 'How many backups are stored by default?', 'hmbkp' ) . '</strong></p>' .

'<p>' . __( 'BackUpWordPress stores the last 10 backups by default.', 'hmbkp' ) . '</p>' .

'<p><strong>' . __( 'How long should a backup take?', 'hmbkp' ) . '</strong></p>' .

'<p>' . __( 'Unless your site is very large (many gigabytes) it should only take a few minutes to perform a back up, if your back up has been running for longer than an hour it\'s safe to assume that something has gone wrong, try de-activating and re-activating the plugin, if it keeps happening, contact support.', 'hmbkp' ) . '</p>' .

'<p><strong>' . __( 'What do I do if I get the wp-cron error message?', 'hmbkp' ) . '</strong></p>' .

'<p>' . __( 'The issue is that your `wp-cron.php` is not returning a `200` response when hit with a http request originating from your own server, it could be several things, most of the time it\'s an issue with the server / site and not with BackUpWordPress.', 'hmbkp' ) . '</p>' .

'<p>' . __( 'Some things you can test are.', 'hmbkp' ) . '</p>' .

'<ul><li>' . __( 'Are scheduled posts working? (They use wp-cron too).', 'hmbkp' ) . '</li>' .
'<li>' . __( 'Are you hosted on Heart Internet? (wp-cron is known not to work with them).', 'hmbkp' ) . '</li>' .
'<li>' . __( 'If you click manual backup does it work?', 'hmbkp' ) . '</li>' .
'<li>' . __( 'Try adding `define( \'ALTERNATE_WP_CRON\', true ); to your `wp-config.php`, do automatic backups work?', 'hmbkp' ) . '</li>' .
'<li>' . __( 'Is your site private (I.E. is it behind some kind of authentication, maintenance plugin, .htaccess) if so wp-cron won\'t work until you remove it, if you are and you temporarily remove the authentication, do backups start working?', 'hmbkp' ) . '</li></ul>' .

'<p>' . __( 'If you have tried all these then feel free to contact support.', 'hmbkp' ) . '</p>' .

'<p><strong>' . __( 'How to get BackUpWordPress working in Heart Internet', 'hmbkp' ) . '</strong></p>' .

'<p>' . __( 'The script to be entered into the Heart Internet cPanel is: `/usr/bin/php5 /home/sites/yourdomain.com/public_html/wp-cron.php` (note the space between php5 and the location of the file). The file `wp-cron.php` `chmod` must be set to `711`.', 'hmbkp' ) . '</p>';