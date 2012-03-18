<?php

namespace e\Commands;
use e;

$sites = array();

foreach(glob(e\root . '/cache/*/', GLOB_ONLYDIR) as $dir) {

	/**
	 * Handle updates
	 */
	$sites[basename($dir)] = e::$events->e_command_update($dir);
}

/**
 * Show result
 */
dump($sites);