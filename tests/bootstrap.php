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
} elseif ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} elseif ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$test_root = '../../../../tests/phpunit';
} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$test_root = '/tmp/wordpress-tests-lib';
}

require $test_root . '/includes/functions.php';

function _manually_load_plugin() {
	require_once( dirname( __DIR__ ) . '/backupwordpress.php' );
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

if ( ! file_exists( '/srv/www/wordpress-develop.dev/src/wp-content/plugins/backupwordpress' ) ) {
	symlink( dirname( __DIR__ ) , '/srv/www/wordpress-develop.dev/src/wp-content/plugins/backupwordpress' );
}

require $test_root . '/includes/bootstrap.php';

require_once dirname( __FILE__ ) . '/class-wp-test-hm-backup-testcase.php';
