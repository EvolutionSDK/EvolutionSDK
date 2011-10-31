<?php

namespace e;
use e;

/**
 * Fix backslashes in paths on Windows
 */
function convert_backslashes($str) {
	return str_replace(
		'\n', '/n', str_replace(
		'\r', '/r', str_replace(
		'\t', '/t', str_replace(
		'\\', '/', $str))));
}

/**
 * Define common directories and use cache as working directory
 */
define(__NAMESPACE__.'\\sites', convert_backslashes(dirname(dirname(__DIR__))));
define(__NAMESPACE__.'\\root', convert_backslashes(dirname(__DIR__)));
define(__NAMESPACE__.'\\kernel', convert_backslashes(__DIR__));
define(__NAMESPACE__.'\\bundles', '/bundles');
chdir(root.'/cache');

/**
 * Include autoloader
 */
require_once(kernel.'/autoload.php');

/**
 * Include some functions
 */
require_once(kernel.'/functions.php');

/**
 * Configure class
 */
require_once(kernel.'/configure.php');

/**
 * Handle Fatal Errors as Exceptions
 */
set_error_handler(function($no, $msg, $file, $line) {
	handle(new \ErrorException($msg, 0, $no, $file, $line));
});

/**
 * Show exceptions
 */
function handle($exception) {
	require_once(root.bundles.'/system/debug/message.php');
}

/**
 * Show exceptions
 */
set_exception_handler(__NAMESPACE__.'\\handle');

/**
 * Use Evolution framework
 */
require_once(kernel.'/framework.php');

/**
 * Check for site folder and load bundles
 */
e::__load(root, sites, bundles, $_SERVER['HTTP_HOST']);

/**
 * Route the request
 */
e::events()->route($_SERVER['REDIRECT_URL']);

/**
 * Complete the page if not completed
 */
e\complete();