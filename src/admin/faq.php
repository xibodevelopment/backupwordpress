<?php
echo '<p><strong>' . __( 'Where does BackUpWordPress store the backup files?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . sprintf( __( 'Backups are stored on your server in %s, you can change the directory.', 'hmbkp' ), '<code>/wp-content/backups</code>' ) . '</p>' .

	'<p>' . __( 'Important: By default BackUpWordPress backs up everything in your site root as well as your database, this includes any non WordPress folders that happen to be in your site root. This does mean that your backup directory can get quite large.', 'hmbkp' ) . '</p>' .

	'<p><strong>' . __( 'What if I want to back up my site to another destination?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . sprintf( __( 'BackUpWordPress Pro supports Dropbox, Google Drive, Amazon S3, Rackspace, Azure, DreamObjects and FTP/SFTP. Check it out here: %s', 'hmbkp' ), '<a href="http://bwp.hmn.md/?utm_source=wordpress-org&utm_medium=plugin-page&utm_campaign=freeplugin" target="_blank" title="BackUpWordPress Homepage">https://bwp.hmn.md</a>' ) . '</p>' .

	'<p><strong>' . __( 'How do I restore my site from a backup?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . sprintf( __( 'You need to download the latest backup file either by clicking download on the backups page or via %s. %s the files and upload all the files to your server overwriting your site. You can then import the database using your hosts database management tool (likely %s).', 'hmbkp' ), '<code>FTP</code>', '<code>Unzip</code>', '<code>phpMyAdmin</code>' ) . '</p>' .

	'<p>' . sprintf( __( 'See this guide for more details - %s.', 'hmbkp' ), '<a href="https://bwp.hmn.md/support-center/restore-backup/" target="_blank">How to restore from backup</a>' ) . '</p>' .

	'<p><strong>' . __( 'Does BackUpWordPress back up the backups directory?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . __( 'No.', 'hmbkp' ) . '</p>' .

	'<p><strong>' . __( 'I\'m not receiving my backups by email?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . __( 'Most servers have a filesize limit on email attachments, it\'s generally about 10mb. If your backup file is over that limit it won\'t be sent attached to the email, instead you should receive an email with a link to download the backup, if you aren\'t even receiving that then you likely have a mail issue on your server that you\'ll need to contact your host about.', 'hmbkp' ) . '</p>' .

	'<p><strong>' . __( 'How many backups are stored by default?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . __( 'BackUpWordPress stores the last 10 backups by default.', 'hmbkp' ) . '</p>' .

	'<p><strong>' . __( 'How long should a backup take?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . __( 'Unless your site is very large (many gigabytes) it should only take a few minutes to perform a back up, if your back up has been running for longer than an hour it\'s safe to assume that something has gone wrong, try de-activating and re-activating the plugin, if it keeps happening, contact support.', 'hmbkp' ) . '</p>' .

	'<p><strong>' . __( 'What do I do if I get the wp-cron error message?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . sprintf( __( 'The issue is that your %s is not returning a %s response when hit with a HTTP request originating from your own server, it could be several things, in most cases, it\'s an issue with the server / site.', 'hmbkp' ), '<code>wp-cron.php</code>', '<code>200</code>' ) . '</p>' .

	'<p>' . __( 'There are some things you can test to confirm this is the issue.', 'hmbkp' ) . '</p>' .

	'<ul><li>' . __( 'Are scheduled posts working? (They use wp-cron as well ). ', 'hmbkp' ) . '</li>' .
	'<li>' . __( 'Are you hosted on Heart Internet? (wp-cron may not be supported by Heart Internet, see below for work-around).', 'hmbkp' ) . '</li>' .
	'<li>' . __( 'If you click manual backup does it work?', 'hmbkp' ) . '</li>' .
	'<li>' . sprintf( __( 'Try adding %s to your %s, do automatic backups work?', 'hmbkp' ), '<code>define( \'ALTERNATE_WP_CRON\', true );</code>', '<code>wp-config.php</code>' ) . '</li>' .
	'<li>' . __( 'Is your site private (I.E. is it behind some kind of authentication, maintenance plugin, .htaccess) if so wp-cron won\'t work until you remove it, if you are and you temporarily remove the authentication, do backups start working?', 'hmbkp' ) . '</li></ul>' .

	'<p>' . __( 'Report the results to our support team for further help. To do this, either enable suport from your Admin Dashboard (recommended), or email support@hmn.md', 'hmbkp' ) . '</p>' .

	'<p><strong>' . __( 'How to get BackUpWordPress working in Heart Internet', 'hmbkp' ) . '</strong></p>' .

	'<p>' . sprintf( __( 'The script to be entered into the Heart Internet cPanel is: %s (note the space between php5 and the location of the file). The file %s %s must be set to %s.', 'hmbkp' ), '<code>/usr/bin/php5 /home/sites/yourdomain.com/public_html/wp-cron.php</code>', '<code>wp-cron.php</code>', '<code>chmod</code>', '<code>711</code>' ) . '</p>' .

	'<p><strong>' . __( 'My backups seem to be failing?', 'hmbkp' ) . '</strong></p>' .

	'<p>' . __( 'If your backups are failing - it\'s commonly caused by lack of available resources on your server. The easiest way to establish this to exclude some [of] or your entire uploades folder, running a backup an if that succeeds. If so, we know it\'s probably a server issue. If not, report the results to our support team for further help. To do this, either enable suport from your Admin Dashboard (recommended), or email support@hmn.md', 'hmbkp' ) . '</p>';