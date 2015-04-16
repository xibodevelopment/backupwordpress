<?php

/**
 * Bootstrap the plugin unit testing environment.
 *
 * @package BackUpWordPress
 * @subpackage tests
 */

// Support for:
// 1. Local SaltyWP
// 2. `WP_DEVELOP_DIR` environment variable
// 3. Plugin installed inside of WordPress.org developer checkout
// 4. Tests checked out to /tmp

if ( file_exists( '/srv/www/wordpress-develop.dev/tests/phpunit/includes/bootstrap.php' ) ) {
	$test_root = '/srv/www/wordpress-develop.dev/tests/phpunit';
	$plugins_dir = '/srv/www/wordpress-develop.dev/src/wp-content/plugins';
} elseif ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
	$plugins_dir = getenv( 'WP_DEVELOP_DIR' ) . '/src/wp-content/plugins';
} elseif ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$test_root = '../../../../tests/phpunit';
	$plugins_dir = '../../../../src/wp-content/plugins';
} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$test_root = '/tmp/wordpress-tests-lib';
	$plugins_dir = '/tmp/wordpress/wp-content/plugins';
}

require $test_root . '/includes/functions.php';

function _manually_load_plugin() {
	require_once( dirname( __DIR__ ) . '/backupwordpress.php' );
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

if ( ! file_exists( '/srv/www/wordpress-develop.dev/src/wp-content/plugins/backupwordpress' ) ) {
	symlink( dirname( __DIR__ ) , $plugins_dir . '/backupwordpress' );
}

require $test_root . '/includes/bootstrap.php';

require_once dirname( __FILE__ ) . '/class-wp-test-hm-backup-testcase.php';

/**
 * Dumps the contents of param to the CLI
 *
 * @param $to_inspect
 */
function hmbkp_var_dump( $to_inspect ) {
	ob_start();
	var_dump( $to_inspect );
	$display = ob_get_clean();
	fwrite( STDERR, $display );
}
