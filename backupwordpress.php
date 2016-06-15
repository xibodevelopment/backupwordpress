<?php
/*
Plugin Name: BackUpWordPress
Plugin URI: http://bwp.hmn.md/
Description: Simple automated backups of your WordPress powered website. Once activated you'll find me under <strong>Tools &rarr; Backups</strong>. On multisite, you'll find me under the Network Settings menu.
Version: 3.6.1
Author: Human Made Limited
Author URI: http://hmn.md/
License: GPL-2+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: backupwordpress
Domain Path: /languages
Network: true
*/

/*
Copyright 2011 - 2014 Human Made Limited  (email : backupwordpress@hmn.md)

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

// Only load if >= PHP 5.3


if ( ! defined( 'HMBKP_PLUGIN_PATH' ) ) {
	define( 'HMBKP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'HMBKP_BASENAME' ) ) {
	define( 'HMBKP_BASENAME', plugin_basename( __FILE__ ) );
}

require_once( HMBKP_PLUGIN_PATH . 'classes/class-setup.php' );

register_activation_hook( __FILE__, array( 'HMBKP_Setup', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HMBKP_Setup', 'deactivate' ) );

if ( HMBKP_Setup::meets_requirements() ) {
	require_once( HMBKP_PLUGIN_PATH . 'classes/class-plugin.php' );
} else {
	add_action( 'admin_init', array( 'HMBKP_Setup', 'self_deactivate' ) );
	add_action( 'all_admin_notices', array( 'HMBKP_Setup', 'display_admin_notices' ) );
}
