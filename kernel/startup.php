<?php

namespace e;
use Exception;
use stack;
use e;

if(isset($_GET['--buffer']))
	ob_start('framework');

/**
 * Ensure the user can't stop the script
 * @author Nate Ferrero
 */
ignore_user_abort(true);

/**
 * Set UTC timezone
 * Do NOT change this, there are plenty of ways to display and work with other
 * timezones without crippling EvolutionSDK!
 * @author Nate Ferrero
 */
date_default_timezone_set('UTC');

/**
 * If nginx load the hacks file
 * @author Kelly Becker
 */
if(isset($_SERVER["SERVER_SOFTWARE"]) && strpos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false)
	require_once(__DIR__.'/hacks/nginx.php');

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
 * @author Nate Ferrero
 */
define('e\\site', convert_backslashes(\EvolutionSite));


/**
 * Get site name
 * @author Nate Ferrero
 */
if(is_file(e\site.'/configure/*.name'))
	$sitename = pathinfo(e\site.'/configure/*.name', PATHINFO_FILENAME);
define('e\\sitename', isset($sitename) ? $sitename : basename(e\site));

define('e\\root', convert_backslashes(\EvolutionSDK));
define('e\\kernel', convert_backslashes(__DIR__));
define('e\\bundles', '/bundles');

/**
 * Are we running the framework out of a Phar
 * @author Kelly Becker
 */
if(strpos(root, 'phar://') === 0)
	define('e\\phar', true);
else define('e\\phar', false);

/**
 * Define cache directories
 * @author Kelly Becker
 */
if(!defined('\CacheDir')) {
	define('e\\cache', root . '/cache');
	define('e\\siteCache', root . '/cache/' . sitename);
}

else {
	define('e\\cache', \CacheDir);
	define('e\\siteCache', \CacheDir . '/' . sitename);
}

/**
 * Support eval(d)
 * Dumps all variables referenced in the last few lines of code
 * @author Nate Ferrero
 */
define('d', 'preg_match("/^(.*)\\((\\d+)\\)\\s\\:\\seval\\(\\)\\\'d code/", __FILE__, $___DUMP);
	if(defined("e\dump"))throw new Exception("Evolution dump already loaded");
	require_once("'.root.bundles.'/debug/dump.php");');

/**
 * Handle Fatal Errors as Exceptions
 * @author Nate Ferrero
 */
set_error_handler('e\\error_handler');

/**
 * Show exceptions
 * @author Nate Ferrero
 */
set_exception_handler('e\\handle');

/**
 * Report All Errors
 * @author Nate Ferrero
 */
error_reporting(E_ALL | E_STRICT);

/**
 * Use cache dir as working directory
 * @author Nate Ferrero
 */
if(!is_dir(cache) && !mkdir(cache)) throw new Exception("Could not create cache folder, run command `dir=".cache."; mkdir \$dir;chmod 777 \$dir`");

/**
 * Use cache dir as working directory
 * @author Nate Ferrero
 */
if(!is_dir(siteCache) && !mkdir(siteCache)) throw new Exception("Could not create site cache folder, run command `dir=".siteCache."; mkdir \$dir;chmod 777 \$dir`");

/**
 * Security
 * 06-2-12 Changed constance to the cache variable
 * @todo move this somewhere else
 * @author Nate Ferrero
 * @author Kelly Becker
 */
chdir(cache);

/**
 * Include autoloader
 * @author Nate Ferrero
 */
require_once('autoload.php');

/**
 * Use Evolution framework
 * @author Nate Ferrero
 */
require_once('framework.php');

/**
 * Check for site folder and load bundles
 * @author Nate Ferrero
 */
Stack::loadSite(root, site, bundles);