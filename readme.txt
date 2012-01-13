=== BackUpWordPress ===
Contributors: humanmade, joehoyle, mattheu, tcrsavage, willmot
Tags: back up, backup, backups, database, zip, db, files, archive, wp-cli, humanmade
Requires at least: 3.1
Tested up to: 3.3
Stable tag: 1.6.4

Simple automated back ups of your WordPress powered website.

== Description ==

BackUpWordPress will back up your entire site including your database and all your files once every day.

= Features =

* Super simple to use, no setup required.
* Uses `zip` and `mysqldump` for faster back ups if they are available.
* Works in low memory, "shared host" environments.
* Option to have each backup file emailed to you.
* Works on Linux & Windows Server.
* Exclude files and folders from your back ups.
* Control advanced settings by defining any of the optional `Constants`.
* Good support should you need help.
* (Partial) Spanish & Russian translations.

= Help develop this plugin =

The BackUpWordPress plugin is hosted github, if you want to help out with development or testing then head over to https://github.com/humanmade/backupwordpress/.

We'd also love help translating the plugin into more languages, if you can help then please contact support@humanmade.co.uk or send us a pull request.

== Installation ==

1. Install BackUpWordPress either via the WordPress.org plugin directory, or by uploading the files to your server.
2. Activate the plugin.
3. Sit back and relax safe in the knowledge that your whole site will be backed up every day.

The plugin will try to use the `mysqldump` and `zip` commands via shell if they are available, using these will greatly improve the time it takes to back up your site.

== Frequently Asked Questions ==

**Where does BackUpWordPress store the backup files?**

Backups are stored on your server in `/wp-content/backups`, you can change the directory.

**Important:** By default BackUpWordPress backs up everything in your site root as well as your database, this includes any non WordPress folders that happen to be in your site root. This does means that your backup directory can get quite large.

**How do I restore my site from a backup?**

You need to download the latest backup file either by clicking download on the backups page or via `FTP`. `Unzip` the files and upload all the files to your server overwriting your site. You can then import the database using your hosts database management tool (likely `phpMyAdmin`).

See this post for more details http://hmn.md/backupwordpress/

**Does BackUpWordPress back up the backups directory?**

No.

**I'm not receiving my backups by email**

Most servers have a filesize limit on email attachments, it's generally about 10mb. If your backup file is over that limit it won't be sent attached to the email, instead you should receive an email with a link to download the backup, if you aren't even receiving that then you likely have a mail issue on your server that you'll need to contact your host about.

**How many backups are stored by default?**

BackUpWordPress stores the last 10 backups by default.

**How long should a backup take?**

Unless your site is very large (many gigabytes) it should only take a few minutes to perform a back up, if your back up has been running for longer than an hour it's safe to assume that something has gone wrong, try de-activating and re-activating the plugin, if it keeps happening, contact support.

**What do I do if I get the wp-cron error message**

The issue is that your `wp-cron.php` is not returning a `200` response when hit with a http request originating from your own server, it could be several things, most of the time it's an issue with the server / site and not with BackUpWordPress.

Some things you can test are.

* Are scheduled posts working? (They use wp-cron too).
* Are you hosted on Heart Internet? (wp-cron is known not to work with them).
* If you click manual backup does it work?
* Try adding `define( 'ALTERNATE_WP_CRON', true ); to your `wp-config.php`, do automatic backups work?
* Is your site private (I.E. is it behind some kind of authentication, maintenance plugin, .htaccess) if so wp-cron won't work until you remove it, if you are and you temporarily remove the authentication, do backups start working?

If you have tried all these then feel free to contact support.

**Further Support & Feedbask**

General support questions should be posted in the <a href="http://wordpress.org/tags/backupwordpress?forum_id=10">WordPress support forums, tagged with backupwordpress.</a>

For development issues, feature requests or anybody wishing to help out with development checkout <a href="https://github.com/humanmade/backupwordpress/">BackUpWordPress on GitHub.</a>

You can also tweet <a href="http://twitter.com/humanmadeltd">@humanmadeltd</a> or email support@humanmade.co.uk for further help/support.

== Screenshots ==

1. Simple Automated Backups

== Changelog ==

#### 1.6.4

* Don't show warning message as they cause to much panic.
* Move previous methods errors to warnings in fallback methods.
* Wrap `.htaccess` rewrite rules in if mod_rewrite check.
* Add link to new restore help article to FAQ.
* Fix issue that could cause "not using latest stable version" message to show when you were in-fact using the latest version.
* Bug fix in `zip command` check that could cause an incorrect zip path to be used.
* Detect and pass MySQL port to `mysqldump`.

#### 1.6.3

* Don't fail archive verification for errors in previous archive methods.
* Improved detection of zip and mysqldump command.
* Fix issues when `ABSPATH` is `/`.
* Remove relience on `SECURE_AUTH_KEY` as it's often not defined.
* Use `warning()` not `error()` for issues reported by `zip`, `ZipArchive` or PclZip`.
* Fix download zip on Windows when `ABSPATH` contains a trailing forward slash.
* Send backup email after backup completes so that fatal errors in email code don't stop the backup from completing.
* Add missing / to `PCLZIP_TEMPORARY_DIR` define.
* Catch and display errors during `mysqldump`.

#### 1.6.2

* Track PHP errors as backup warnings not errors.
* Only show warning message for PHP errors in BackUpWordPress files.
* Ability to dismiss the error / warning messages.
* Disable use of PclZip for full archive checking for now as it causes memory issues on some large sites.
* Don't delete "number of backups" setting on update.
* Better handling of multibite characters in archive and database dump filenames.
* Mark backup as running and increase callback timeout to 500 when firing backup via ajax.
* Don't send backup email if backup failed.
* Filter out duplicate exclude rules.

#### 1.6.1

* Fix fatal error on PHP =< 5.3

#### 1.6

* Fixes issue with backups dir being included in backups on some Windows Servers.
* Consistent handling of symlinks across all archive methods (they are followed).
* Use .htaccess rewrite cond authentication to allow for secure http downloads of backup files.
* Track errors and warnings that happen during backup and expose them through admin.
* Fire manual backups using ajax instead of wp-cron, `HMBKP_DISABLE_MANUAL_BACKUP_CRON` is no longer needed and has been removed.
* Ability to cancel a running backup.
* Zip files are now integrity checked after every backup.
* More robust handling of failed / corrupt zips, backup process now fallsback through the various zip methods until one works.
* Use `mysql_query` instead of the depreciated `mysql_list_tables`.

#### 1.5.2

* Better handling of unreadable files in ZipArchive and the backup size calculation.
* Support for wp-cli, usage: `wp backup [--files_only] [--database_only] [--path<dir>] [--root<dir>] [--zip_command_path=<path>] [--mysqldump_command_path=<path>]`

#### 1.5.1

* Better detection of `zip` command.
* Don't delete user settings on update / deactivate.
* Use `ZipArchive` if `zip` is not available, still falls back to `PclZip` if neither `zip` nor `ZipArchive` are installed.
* Better exclude rule parsing, fixes lots of edge cases, excludes now pass all 52 unit tests.
* Improved the speed of the backup size calculation.

#### 1.5

* Re-written core backup engine should be more robust especially in edge case scenarios.
* 48 unit tests for the core backup engine, yay for unit tests.
* Remove some extraneous status information from the admin interface.
* Rename Advanced Options to Settings
* New `Constant` `HMBKP_CAPABILITY` to allow the default `add_menu_page` capability to be changed.
* Suppress possible filemtime warnings in some edge cases.
* 3.3 compatability.
* Set proper charset of MySQL backup, props valericus.
* Fix some inconsistencies between the estimated backup size and actual backup size when excluding files.

#### 1.4.1

* 1.4 was incorrectly marked as beta.

#### 1.4

* Most options can now be set on the backups page, all options can still be set by defining them as `Constants`.
* Russian translation, props valericus.
* All dates are now translatable.
* Fixed some strings which weren't translatable.
* New Constant `HMBKP_DISABLE_MANUAL_BACKUP_CRON` which enable you to disable the use of `wp_cron` for manual backups.
* Manual backups now work if `DISABLE_WP_CRON` is defined as `true`.

#### 1.3.2

* Spanish translation
* Bump PHP version check to 5.2.4
* Fallback to PHP mysqldump if shell_exec fails for any reason.
* Silently ignore unreadable files / folders
* Make sure binary data is properly exported when doing a mysqldump
* Use 303 instead of 302 when redirecting in the admin.
* Don't `set_time_limit` inside a loop
* Use WordPress 3.2 style buttons
* Don't pass an empty password to mysqldump

#### 1.3.1

* Check for PHP version. Deactivate plugin if running on PHP version 4.

#### 1.3

* Re-written back up engine, no longer copies everything to a tmp folder before zipping which should improve speed and reliability.
* Support for excluding files and folders, define `HMBKP_EXCLUDE` with a comma separated list of files and folders to exclude, supports wildcards `*`, path fragments and absolute paths.
* Full support for moving the backups directory, if you define a new backups directory then your existing backups will be moved to it.
* Work around issues caused by low MySQL `wait_timeout` setting.
* Add FAQ to readme.txt.
* Pull FAQ into the contextual help tab on the backups page.
* Block activation on old versions of WordPress.
* Stop guessing compressed backup file size, instead just show size of site uncompressed.
* Fix bug in `safe_mode` detection which could cause `Off` to act like `On`.
* Better name for the database dump file.
* Better name for the backup files.
* Improve styling for advanced options.
* Show examples for all advanced options.
* Language improvements.
* Layout tweaks.

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