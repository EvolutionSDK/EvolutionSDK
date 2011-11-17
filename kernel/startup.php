<?php

namespace e;
use e;
use Exception;

/**
 * Ensure the user can't stop the script
 */
ignore_user_abort(true);

/**
 * Set UTC timezone
 */
date_default_timezone_set('UTC');

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
 * Handle Fatal Errors as Exceptions
 */
set_error_handler(function($no, $msg, $file, $line) {
	/**
	* Ignore warnings and notices unless in strict mode 
	*/
	if(!isset($_GET['_strict'])) {
		switch ($no) {
			case E_WARNING:
			case E_NOTICE:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			case E_STRICT:
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
			return true;
		}
	}
	throw new \ErrorException($msg, 0, $no, $file, $line);
});

/**
 * Use this to debug weird errors
 */
//error_reporting(E_ALL);

/**
 * Show exceptions
 */
function handle($exception) {
	
	/**
	 * If Evolution framework is loaded, send out an exception event
	 */
	if(e::$loaded) {
		try {
			e::events()->exception($exception);
		} catch(Exception $exception) {}
	}
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