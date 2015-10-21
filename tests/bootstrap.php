<?php

/**
 * Bootstrap the plugin unit testing environment.
 *
 * @package BackUpWordPress
 * @subpackage tests
 */

/**
 * Determine where the WP test suite lives.
 *
 * Support for:
 * 1. `WP_DEVELOP_DIR` environment variable, which points to a checkout
 *   of the develop.svn.wordpress.org repository (this is recommended)
 * 2. `WP_TESTS_DIR` environment variable, which points to a checkout
 * 3. `WP_ROOT_DIR` environment variable, which points to a checkout
 * 4. Plugin installed inside of WordPress.org developer checkout
 * 5. Tests checked out to /tmp
 */
if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} else if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$test_root = getenv( 'WP_TESTS_DIR' );
} else if ( false !== getenv( 'WP_ROOT_DIR' ) ) {
	$test_root = getenv( 'WP_ROOT_DIR' ) . '/tests/phpunit';
} else if ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$test_root = '../../../../tests/phpunit';
} else if ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$test_root = '/tmp/wordpress-tests-lib';
}

require $test_root . '/includes/functions.php';

function _manually_load_plugin() {
	require_once( dirname( __DIR__ ) . '/backupwordpress.php' );
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

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
