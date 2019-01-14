<?php
/**
 * PHPUnit bootstrap file
 */
global $_tests_dir;
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
	$_tmpdir = getenv('TMPDIR');
	if (!$_tmpdir) {
		$_tmpdir = '/tmp';
	}
	$_tests_dir = preg_replace('#/$#', '', $_tmpdir) . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
	throw new Exception("Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?");
}

require_once $_tests_dir . '/includes/functions.php';

function load_composer() {
	require dirname(__DIR__) . '/vendor/autoload.php';
}
tests_add_filter('muplugins_loaded', 'load_composer');

require $_tests_dir . '/includes/bootstrap.php';