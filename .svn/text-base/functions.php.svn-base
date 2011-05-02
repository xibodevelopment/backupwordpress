<?php

function bkpwp_activate() {
	$role = get_role('administrator');
	if(!$role->has_cap('manage bkpwp')) {
		$role->add_cap('manage bkpwp');
	} 
	$options = new BKPWP_OPTIONS();
	$options->bkpwp_set_defaults();
}

function bkpwp_exit() {
	//clear hooks for those
	wp_clear_scheduled_hook("bkpwp_schedule_bkpwp_hook");
	//delete all options
	delete_option("bkpwp_schedules");
	delete_option("bkpwppath");
	delete_option("bkpwp_presets");
	delete_option('bkpwp_listmax_backups');
	delete_option("bkpwp_domain");
	delete_option("bkpwp_domain_path");
	delete_option("bkpwp_archive_types");
	delete_option("bkpwp_easy_mode");
	delete_option("bkpwp_reccurrences");
	delete_option("bkpwp_calculation");
	delete_option("bkpwp_listmax_backups");
	delete_option("bkpwp_automail");
	delete_option("bkpwp_automail_maxsize");
	delete_option("bkpwp_status");
	delete_option("bkpwp_status_config");
	
	// configuration options to keep
	//delete_option("bkpwp_excludelists");
	//delete_option("bkpwp_max_backups");
	//delete_option("bkpwp_automail_address");
	//delete_option("bkpwp_automail_receiver");
	//delete_option("bkpwp_automail_from");
}

function bkpwp_setup() {
   if(current_user_can('manage bkpwp')) {
	   
	$locale = get_locale();
	$mofile = dirname(__FILE__) . "/locale/".$locale.".mo";
	load_textdomain('bkpwp', $mofile);
	
	   get_currentuserinfo();
	   $options = new BKPWP_OPTIONS();
	   $options->bkpwp_handle_modeswitch();
   }
}

function bkpwp_load_menu_page() {
	if (!empty($_REQUEST['page']) && file_exists(BKPWP_PLUGIN_PATH."bkpwp-pages/".$_REQUEST['page'].".php")) {
		include_once(BKPWP_PLUGIN_PATH."bkpwp-pages/".$_REQUEST['page'].".php");
	} else {
		include_once(BKPWP_PLUGIN_PATH."bkpwp-pages/bkpwp_manage_backups.php");
	}
	include_once(BKPWP_PLUGIN_PATH."bkpwp-pages/bkpwp_footer.php");
}

function bkpwp_add_menu() {
	$interface = new BKPWP_INTERFACE();
	$interface->menu();
}

function bkpwp_help_zeitgeist() {
$options = new BKPWP_OPTIONS();
	?>
		<div id="zeitgeist">
			<b><?php _e("BackUpWordPress Help Index","bkpwp"); ?></b>
			<p>
			<?php _e("Looking for a less komplex Solution? Please swith to ","bkpwp"); ?>
			<?php if (!$options->bkpwp_easy_mode()) { ?>
			<a href="admin.php?page=<?php echo $_REQUEST['page']; ?>&bkpwp_modeswitch=1"><?php _e("EasyMode","bkpwp"); ?> &raquo;</a>
			<?php } else { ?>
			<a href="admin.php?page=<?php echo $_REQUEST['page']; ?>&bkpwp_modeswitch=1"><?php _e("AdvancedMode","bkpwp"); ?> &raquo;</a>
			<?php } ?>
			</p>
			<p>
			<ul>
			<li><a href="admin.php?page=bkpwp_help#help_manage_backups"><?php _e("Manage Backups","bkpwp"); ?></a></li>
			<li><a href="admin.php?page=bkpwp_help#help_manage_presets"><?php _e("Manage Backup Presets","bkpwp"); ?></a></li>
			<li><a href="admin.php?page=bkpwp_help#help_manage_schedules"><?php _e("Manage Backup Schedules","bkpwp"); ?></a></li>
			<li><a href="admin.php?page=bkpwp_help#help_options"><?php _e("Options","bkpwp"); ?></a></li>
			</ul>
			</p>
		</div>
	<?php
}

function bkpwp_check_unfinished_backup() {
	$status = get_option("bkpwp_status");
	if (!empty($status)) {
		if (!is_dir(get_option("bkpwppath")."/".$status['name'])) {
			return false;
		}
		if (($status['time']-5) < time()) {
			return true;
		}
	}
	return false;
}

function bkpwp_proceed_unfinished() {
	// no unfinished backup
	if(!bkpwp_check_unfinished_backup()) {
		return;
	}
	
	$status = get_option("bkpwp_status");
	
	// no working directory
	if (!is_dir(get_option("bkpwppath")."/".$status['name'])) {
		return;
	}
	// wait a little
	$timeout = ini_get("max_execution_time")-5;
	if (($status['time']+$timeout) > time()) {
		return;
	}
	
	// okay, schedule that one for finishing up
	if (!wp_next_scheduled('bkpwp_finish_bkpwp_hook', $status)) {
		wp_schedule_single_event(time(), 'bkpwp_finish_bkpwp_hook', $status);
		return;
	}
}

function bkpwp_date_diff($earlierDate, $laterDate) {
  //returns an array of numeric values representing days, hours, minutes & seconds respectively
  $ret=array('days'=>0,'hours'=>0,'minutes'=>0,'seconds'=>0);

  $totalsec = $laterDate - $earlierDate;
  if ($totalsec >= 86400) {
    $ret['days'] = floor($totalsec/86400);
    $totalsec = $totalsec % 86400;
  }
  if ($totalsec >= 3600) {
    $ret['hours'] = floor($totalsec/3600);
    $totalsec = $totalsec % 3600;
  }
  if ($totalsec >= 60) {
    $ret['minutes'] = floor($totalsec/60);
  }
  $ret['seconds'] = $totalsec % 60;
  return $ret;
}
		
function bkpwp_latest_activity() {
   if(current_user_can('manage bkpwp')) {
	echo "<h3>".__("BackUpWordPress","bkpwp")." <a href=\"admin.php?page=backupwordpress/backupwordpress.php\">&raquo;</a></h3>";
	echo "<p><a href=\"http://wordpress.designpraxis.at/\">".__("Check for a new version of BackUpWordPress!","bkpwp")."</a></p>";
	if (bkpwp_check_unfinished_backup()) {
		echo "<span style=\"color:red\">".__("You have unfinished Backups!","bkpwp")." <a href=\"admin.php?page=backupwordpress/backupwordpress.php\">".__("Please finish them manually","bkpwp")." &raquo;</a></span>";
	}
	echo "<p><b>".__("Latest Backups","bkpwp")."</b>:</p>";
	$backup = new BKPWP_MANAGE();
	$backups = $backup->bkpwp_get_backups();
	if(count($backups) > 0) {
		echo "<ul style=\"list-style: none;\">";
		$i=0;
		foreach ($backups as $f) {
			if (!file_exists($f['file'])) {
				continue;
			}
			$info = new BKPWP_BACKUP_ARCHIVE();
			$info = $info->bkpwp_view_backup_info(base64_encode($f['file']),1);
			echo "<li><a href=\"admin.php?page=backupwordpress/backupwordpress.php&amp;bkpwp_download=".base64_encode($f['file'])."\"><img src=\"".get_bloginfo("wpurl")."/wp-content/plugins/backupwordpress/images/disk.png\" alt=\"download\" title=\"Download Backup\" /></a> ";
			echo date(get_option('date_format'),filemtime($f['file']))." ".date("H:i",filemtime($f['file']));
			echo ": ";
			echo "<b>".$info['filesize']."</b> ";
			echo "</li>";
			$i++;
			if ($i > 2) { break; }
		}
		echo "</ul>";
	}
	
	$schedules = get_option("bkpwp_schedules");
	if(count($schedules) < 1) { return; }
	echo "<b>".__("Next scheduled Backup","bkpwp")."</b>: ";
	$sarray = array();
	foreach ($schedules as $options) {
		if ($options['status'] == "active") {
			$timestamp = wp_next_scheduled("bkpwp_schedule_bkpwp_hook",$options);
			$sarray[$timestamp] = $options['info'];
		}
	}
	if(count($sarray) < 1) { echo __("Schedules inactive","bkpwp"); }
	arsort ($sarray);
	foreach ($sarray as $key => $value) {
		echo $value;
		?>
		<div id="countdowncontainer<?php echo $key; ?>"></div>
		<script type="text/javascript">
		var futuredate=new cdtime("countdowncontainer<?php echo $key; ?>", "<?php echo date("F j, Y H:i:s",$key); ?>")
		futuredate.displaycountdown("days", formatresultsdh)
		</script>
		<?php 
		break;
	}
   }
}

function bkpwp_conform_dir($dir,$rel=false) {
	$dir = str_replace("\\","/",$dir);
		if (substr($dir,-1) == "/") {
			$dir = substr($dir,0,-1);
		}
	if ($rel == true) {
		$dir = str_replace(bkpwp_conform_dir(ABSPATH),"",$dir);
	}
	return $dir;
}

function bkpwp_size_readable($size, $unit = null, $retstring = null, $si = true) {
    // Units
    if ($si === true) {
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
        $mod   = 1000;
    } else {
        $sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $mod   = 1024;
    }
    $ii = count($sizes) - 1;
 
    // Max unit
    $unit = array_search((string) $unit, $sizes);
    if ($unit === null || $unit === false) {
        $unit = $ii;
    }
 
    // Return string
    if ($retstring === null) {
        $retstring = '%01.2f %s';
    }
 
    // Loop
    $i = 0;
    while ($unit != $i && $size >= 1024 && $i < $ii) {
        $size /= $mod;
        $i++;
    }
 
    return sprintf($retstring, $size, $sizes[$i]);
}

function bkpwp_ajax_create($preset="") {
	$backup = new BKPWP_BACKUP();
	$backup->preset = new BKPWP_MANAGE();
	$preset = $backup->preset->bkpwp_get_preset($preset);
	$preset['bkpwp_schedule'] = __("manually","bkpwp");
	$ret = $backup->bkpwp_do_backup($preset);
	return $ret;
}

function bkpwp_ajax_view_backup($backupfile) {
	$backup = new BKPWP_BACKUP_ARCHIVE();
	$ret = $backup->bkpwp_view_backup_info($backupfile);
	return $ret;
}

function bkpwp_ajax_view_preset($preset="") {
	$backup = new BKPWP_MANAGE();
	$preset = $backup->bkpwp_get_preset($preset);
	$ret = $backup->bkpwp_view_preset($preset);
	return $ret;
}

function bkpwp_ajax_load_preset($preset="") {
	$backup = new BKPWP_MANAGE();
	$preset = $backup->bkpwp_get_preset($preset);
	$ret = $backup->bkpwp_load_preset($preset);
	return $ret;
}

function bkpwp_ajax_shownobfiles($excludelist) {
	$options = new BKPWP_OPTIONS();
	$ret = $options->bkpwp_ajax_shownobfiles($excludelist);
	return $ret;
}

function bkpwp_ajax_calculater($preset="") {
	$backup = new BKPWP_BACKUP();
	$backup->preset = new BKPWP_MANAGE();
	$preset = $backup->preset->bkpwp_get_preset($preset);
	$ret = $backup->bkpwp_calculate($preset);
	return $ret;
}

function bkpwp_more_reccurences($recc) {
	$bkpwp_reccurrences = get_option("bkpwp_reccurrences");
	if(empty($bkpwp_reccurrences)) {
			// scheduling reccurrences
			$bkpwp_reccurrences = array(
				'bkpwp_weekly' => array('interval' => 604800, 'display' => 'every week'),
				'bkpwp_daily' => array('interval' => 86400, 'display' => 'every day')
			);
			update_option("bkpwp_reccurrences",$bkpwp_reccurrences);
			$bkpwp_reccurrences = get_option("bkpwp_reccurrences");
	}
	foreach ($bkpwp_reccurrences as $key => $value) {
		$recc[$key] = $value;
	}
	return $recc;
}

function bkpwp_finish_bkpwp($status) {
	$backup = new BKPWP_BACKUP();
	$backup->bkpwp_do_backup("",$status);
}

function bkpwp_schedule_bkpwp($options) {

	$backup = new BKPWP_BACKUP();
	$backup->preset = new BKPWP_MANAGE();
	$preset = $backup->preset->bkpwp_get_preset($options);
	$preset['bkpwp_schedule'] = "scheduled";
	$backup->bkpwp_do_backup($preset);
	$schedules['lastrun'] = date("Y-m-d H:i");
}

function bkpwp_download_files() {
	if (!empty($_REQUEST['bkpwp_download'])) {
		if (!current_user_can("download_backups")) {
			die("Permission denied");
		}
		$file = base64_decode($_REQUEST['bkpwp_download']);
		bkpwp_send_file($file);
	}
}

function bkpwp_send_file($path) {
    session_write_close();
    ob_end_clean();
    if (!is_file($path) || connection_status()!=0)
        return(FALSE);

    //to prevent long file from getting cut off from     //max_execution_time

    @set_time_limit(0);

    $name=basename($path);

    //filenames in IE containing dots will screw up the
    //filename unless we add this

    if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
        $name = preg_replace('/\./', '%2e', $name, substr_count($name, '.') - 1);

    //required, or it might try to send the serving     //document instead of the file

    header("Cache-Control: ");
    header("Pragma: ");
    header("Content-Type: application/octet-stream");
    header("Content-Length: " .(string)(filesize($path)) );
    header('Content-Disposition: attachment; filename="'.$name.'"');
    header("Content-Transfer-Encoding: binary\n");

    if($file = fopen($path, 'rb')){
        while( (!feof($file)) && (connection_status()==0) ){
            print(fread($file, 1024*8));
            flush();
        }
        fclose($file);
    }
    return((connection_status()==0) and !connection_aborted());
}

function bkpwp_security_check() {
	// secure the backup directory with .htaccess
	// deny from all 
	$path = get_option("bkpwppath");
	if (empty($path)) { return; }
	$filename = $path."/.htaccess";
	    if (!$handle = fopen($filename, 'w')) {
		    echo "Cannot open file ($filename)";
		 // should be checked at configuration
	    }
	    if (fwrite($handle, "deny from all") === FALSE) {
		    echo "Cannot write to file ($filename)";
		// todo: warn the blog owner
	    }
	    fclose($handle);
}
?>
