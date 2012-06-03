<?php

namespace e\Commands;
use e;

$sites = array();

/**
 * Clear all caches
 * @author Kelly Becker
 */
foreach(new \DirectoryIterator(e\cache) as $dir) {
	if(strpos($dir, '.') === 0 || strlen($dir) < 1 || !$dir->isDir())
		continue;

	/**
	 * Get the full directory path + name
	 */
	$dir = $dir->getPathname();

	/**
	 * Handle updates
	 */
	$sites[basename($dir)] = e::$events->e_command_update($dir, basename($dir));
}

/**
 * Show result
 */
dump($sites);