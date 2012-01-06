<?php

namespace e;
use Exception;
use stack;
use e;

/**
 * Ensure the user can't stop the script.
 * @todo what is this about? Why is this here?
 */
ignore_user_abort(true);

/**
 * Set UTC timezone
 */
date_default_timezone_set('UTC');

/**
 * Include some functions
 */
require_once('functions.php');

/**
 * Define common directories.
 */
define(__NAMESPACE__.'\\site', 		convert_backslashes(\EVOLUTION_SITE_STARTUP_ROOT)	);
define(__NAMESPACE__.'\\sites', 	convert_backslashes(dirname(dirname(__DIR__)))		);
define(__NAMESPACE__.'\\root', 		convert_backslashes(dirname(__DIR__))				);
define(__NAMESPACE__.'\\kernel', 	convert_backslashes(__DIR__)						);
define(__NAMESPACE__.'\\bundles', 	'/bundles'											);

/**
 * Start Timer
 */
e\timer();

/**
 * Handle Fatal Errors as Exceptions
 */
set_error_handler('e\\error_handler');

/**
 * Show exceptions
 */
set_exception_handler('e\\handle');

/**
 * Report All Errors
 */
error_reporting(E_ALL | E_STRICT);

/**
 * Include autoloader
 */
require_once('autoload.php');

/**
 * Use Evolution framework
 */
require_once('framework.php');

/**
 * Use cache dir as working directory
 */
if(!is_dir(root.'/cache') && !mkdir(root.'/cache')) throw new Exception("Could not create cache folder, run command `dir=".root."/cache; mkdir \$dir;chmod 777 \$dir`");

/**
 * Check for site folder and load bundles
 */
Stack::loadSite(root, site, bundles);

/**
 * Route the request
 */
e::$events->route($_SERVER['REDIRECT_URL']);

/**
 * Complete the page if not completed
 */
e\complete();