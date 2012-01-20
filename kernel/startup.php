<?php

namespace e;
use Exception;
use stack;
use e;

/**
 * Ensure the user can't stop the script
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
 * Start Timer
 */
e\timer();

/**
 * Define common directories and use cache as working directory
 */
define('e\\site', convert_backslashes(\EvolutionSite));
define('e\\root', convert_backslashes(\EvolutionSDK));
define('e\\kernel', convert_backslashes(__DIR__));
define('e\\bundles', '/bundles');
define('e\\siteCache', root.'/cache/'.basename(site));

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
 * Use cache dir as working directory
 */
if(!is_dir(root.'/cache') && !mkdir(root.'/cache')) throw new Exception("Could not create cache folder, run command `dir=".root."/cache; mkdir \$dir;chmod 777 \$dir`");

/**
 * Use cache dir as working directory
 */
if(!is_dir(siteCache) && !mkdir(siteCache)) throw new Exception("Could not create site cache folder, run command `dir=".siteCache."; mkdir \$dir;chmod 777 \$dir`");

/**
 * Security
 * @todo move this somewhere else
 */
chdir(root.'/cache');

/**
 * Include autoloader
 */
require_once('autoload.php');

/**
 * Use Evolution framework
 */
require_once('framework.php');

/**
 * Check for site folder and load bundles
 */
Stack::loadSite(root, site, bundles);

/**
 * Trigger an event
 */
e::$events->ready();

/**
 * Complete the page if not completed
 */
e\complete();