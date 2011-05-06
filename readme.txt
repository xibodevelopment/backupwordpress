=== BackUpWordPress ===
Contributors: willmot, matheu, joehoyle, humanmade
Tags: back up, back up, backup, backups, database, zip, db, files, archive
Requires at least: 3.0
Tested up to: 3.1.2
Stable tag: 1.2

Simple automated backups of your WordPress powered website.

== Description ==

BackUpWordPress will back up your entire site including your database and all your files once every day. It has several advanced options for power users.

Features include:

* Super simple to use, no setup required.
* Uses `zip` and `mysqldump` for faster backups if they are available.
* Option to have each backup file emailed to you.
* Works on Linux & Windows Server.
* Control advanced options by defining any of the optional `Constants`.
* Good support should you need help.


== Installation ==

1. Install BackUpWordPress either via the WordPress.org plugin directory, or by uploading the files to your server.
2. Activate the plugin.
3. Sit back and relax safe in the knowledge that your whole site will be backed up every day.

The plugin will try to use the `mysqldump` and `zip` commands via shell if they are available, using these will greatly improve the time it takes to back up your site. If you know about such things then you can point the plugin in the right direction by defining `HMBKP_ZIP_PATH` and `HMBKP_MYSQLDUMP_PATH` in your `wp-config.php`.

== Frequently Asked Questions ==

Contact support@humanmade.co.uk for help/support.

== Screenshots ==

1. Simple Automated Backups

== Changelog ==

#### 1.2

* Show live backup status in the back up now button when a back up is running.
* Show free disk space after total used by backups.
* Several langauge changes.
* Work around the 1 cron every 60 seconds limit.
* Store backup status in a 2 hour transient as a last ditch attempt to work around the "stuck on backup running" issue.
* Show a warning and disable backups when PHP is in Safe Mode, may try to work round issues and re-enable in the future.
* Highlight defined `Constants`.
* Show defaults for all `Constants`.
* Show a warning if both `HMBKP_FILES_ONLY` and `HMBKP_DATABASE_ONLY` are defined at the same time.
* Make sure options added in 1.1.4 are cleared on de-activate.
* Support `mysqldump on` Windows if it's available.
* New option to have each backup emailed to you on completion. Props @matheu for the contribution.
* Improved windows server support.

#### 1.1.4

* Fix a rare issue where database backups could fail when using the mysqldump PHP fallback if `mysql.max_links` is set to 2 or less.
* Don't suppress `mysql_connect` errors in the mysqldump PHP fallback.
* One time highlight of the most recent completed backup when viewing the manage backups page after a successful backup.
* Fix a spelling error in the `shell_exec` disabled message.
* Store the BackUpWordPress version as a `Constant` rather than a `Variable`.
* Don't `(float)` the BackUpWordPress version number, fixes issues with minor versions numbers being truncated.
* Minor PHPDoc improvements.

#### 1.1.3

* Attempt to re-connect if database connection hits a timeout while a backup is running, should fix issues with the "Back Up Now" button continuing to spin even though the backup is completed.
* When using `PCLZIP` as the zip fallback don't store the files with absolute paths. Should fix issues unzipping the file archives using "Compressed (zipped) Folders" on Windows XP.

#### 1.1.2

* Fix a bug that stopped `HMBKP_DISABLE_AUTOMATIC_BACKUP` from working.

#### 1.1.1

* Fix a possible `max_execution_timeout` fatal error when attempting to calculate the path to `mysqldump`.
* Clear the running backup status and reset the calculated filesize on update.
* Show a link to the manage backups page in the plugin description.
* Other general fixes.

#### 1.1

* Remove the logging facility as it provided little benefit and complicated the code, your existing logs will be deleted on update.
* Expose the various `Constants` that can be defined to change advanced settings.
* Added the ability to disable the automatic backups completely `define( 'HMBKP_DISABLE_AUTOMATIC_BACKUP', true );`.
* Added the ability to switch to file only or database only backups `define( 'HMBKP_FILES_ONLY', true );` Or `define( 'HMBKP_DATABASE_ONLY', true );`.
* Added the ability to define how many old backups should be kept `define( 'HMBKP_MAX_BACKUPS', 20 );`
* Added the ability to define the time that the daily backup should run `define( 'HMBKP_DAILY_SCHEDULE_TIME', '16:30' );`
* Tweaks to the backups page layout.
* General bug fixes and improvements.

#### 1.0.5

* Don't ajax load estimated backup size if it's already been calculated.
* Fix time in backup complete log message.
* Don't mark backup as running until cron has been called, will fix issues with backup showing as running even if cron never fired.
* Show number of backups saved message.
* Add a link to the backups page to the plugin action links.

#### 1.0.4

Don't throw PHP Warnings when `shell_exec` is disabled

#### 1.0.3

Minor bug fix release.

* Suppress `filesize()` warnings when calculating backup size.
* Plugin should now work when symlinked.
* Remove all options on deactivate, you should now be able to deactivate then activate to fix issues with settings etc. becoming corrupt.
* Call setup_defaults for users who update from backupwordpress 0.4.5 so they get new settings.
* Don't ajax ping running backup status quite so often.

#### 1.0.1 & 1.0.2

Fix some silly 1.0 bugs

#### 1.0

1.0 represents a total rewrite & rethink of the BackUpWordPress plugin with a focus on making it "Just Work". The management and development of the plugin has been taken over by [humanmade](http://humanmade.co.uk) the chaps behind [WP Remote](https://wpremote.com)

#### Previous

Version 0.4.5 and previous were developed by [wpdprx](http://profiles.wordpress.org/users/wpdprx/)