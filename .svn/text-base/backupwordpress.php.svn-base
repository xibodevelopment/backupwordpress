<?php
/*
Plugin Name: BackUpWordPress
Plugin URI: http://wordpress.designpraxis.at
Description: Manage <a href="admin.php?page=backupwordpress/backupwordpress.php">WordPress Backups</a>. Beta Release. Please help testing and give me feedback under the comments section of <a href="http://wordpress.designpraxis.at/plugins/backupwordpress/">the Plugin page</a>. Backup DB, Files & Folders, use .tar.gz, .zip, Exclude List, etc.
Author: Roland Rust
Version: 0.4.5
Author URI: http://wordpress.designpraxis.at
*/

/*  Copyright 2007  Roland Rust  (email : wordpress@designpraxis.at)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
define("BKPWP_PLUGIN_PATH", ABSPATH."wp-content/plugins/backupwordpress/");
define("BKPWP_VERSION", "0.4.5");

// get the functions
require_once(BKPWP_PLUGIN_PATH."functions.php");
// require_once the required PEAR::FILE_ARCHIVE package for files backup
require_once BKPWP_PLUGIN_PATH."Archive.php";
require_once BKPWP_PLUGIN_PATH."Type.php";
// BackUpWordPress classes
require_once(BKPWP_PLUGIN_PATH."bkpwp-classes/interface.php");
require_once(BKPWP_PLUGIN_PATH."bkpwp-classes/options.php");
require_once(BKPWP_PLUGIN_PATH."bkpwp-classes/manage_backups.php");
require_once(BKPWP_PLUGIN_PATH."bkpwp-classes/schedule.php");
require_once(BKPWP_PLUGIN_PATH."functions-interface.php");

// Plugin activation and deactivation e.g.: set 'manage bkpwp' capabilities to admin
add_action('activate_backupwordpress/backupwordpress.php', 'bkpwp_activate');
add_action('deactivate_backupwordpress/backupwordpress.php', 'bkpwp_exit');
	
// set up ajax stuff on init, to prevent header oputput
add_action('init', 'bkpwp_download_files');
add_action('init', 'bkpwp_setup');
add_action('init', 'bkpwp_security_check');
add_action('init', 'bkpwp_proceed_unfinished');

// cron jobs with wordpress' pseude-cron: add special reccurences
add_filter('cron_schedules', 'bkpwp_more_reccurences');

add_action('bkpwp_schedule_bkpwp_hook','bkpwp_schedule_bkpwp');
add_action('bkpwp_finish_bkpwp_hook','bkpwp_finish_bkpwp');

// ajax new: prototype replaces sajax
if (eregi("backupwordpress",$_REQUEST['page']) || eregi("bkpwp",$_REQUEST['page'])) {
	wp_enqueue_script('prototype');
	if (!empty($_POST['bkpwp_calculate_preset'])) {
		echo bkpwp_ajax_calculater($_POST['bkpwp_calculate_preset']); exit;
	}
	if (!empty($_POST['bkpwp_docreate_preset'])) {
		echo bkpwp_ajax_create($_POST['bkpwp_docreate_preset']); exit;
	}
	if (!empty($_POST['bkpwp_view_backup'])) {
		echo bkpwp_ajax_view_backup($_POST['bkpwp_view_backup']); exit;
	}
	if (!empty($_POST['bkpwp_view_excludelist'])) {
		echo bkpwp_ajax_shownobfiles($_POST['bkpwp_view_excludelist']); exit;
	}
	if (!empty($_POST['bkpwp_load_preset'])) {
		echo bkpwp_ajax_load_preset($_POST['bkpwp_load_preset']); exit;
	}
	if (!empty($_POST['bkpwp_view_preset'])) {
		echo bkpwp_ajax_view_preset($_POST['bkpwp_view_preset']); exit;
	}
	if (!empty($_POST['bkpwp_delete_preset'])) {
		echo bkpwp_ajax_delete_preset($_POST['bkpwp_delete_preset']); exit;
	}
	if (!empty($_POST['bkpwp_save_preset'])) {
		echo bkpwp_ajax_save_preset($_POST['bkpwp_save_preset'],$_POST['bkpwp_preset_archive_type'],$_POST['bkpwp_excludelist'],$_POST['bkpwp_sql_only']); exit;
	}
}
if (eregi("backupwordpress",$_REQUEST['page']) || eregi("bkpwp",$_REQUEST['page']) || $_SERVER['REQUEST_URI'] == "/wp-admin/index.php" || $_SERVER['REQUEST_URI'] == "/wp-admin/") {
add_action('admin_head', 'bkpwp_load_css_and_js');
}
add_action('admin_menu', 'bkpwp_add_menu');
add_action('activity_box_end', 'bkpwp_latest_activity',0);

// debug code. how do we get rid of the eaccelerator problem causing a redeclare error fpr pear?
/* if (eregi("backupwordpress",$_REQUEST['page']) || eregi("bkpwp",$_REQUEST['page'])) {
	//@ini_set('eaccelerator.enable',0)
	if ((ini_get('eaccelerator.enable') == 1)) {
		echo "eAcc is running";
	}
	if (!in_array("PEAR", get_declared_classes())) {
echo "<pre>";
		 //print_r(get_declared_classes());
		 require_once BKPWP_PLUGIN_PATH."PEAR.php";
echo "</pre>";
 }
//print_r(get_declared_classes());
} */
?>
