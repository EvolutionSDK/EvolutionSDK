<?php

namespace evolution;
use e;

/**
 * Display the exception
 */
if(isset($exception)) {
	$title = "EvolutionSDK&trade; Exception";
	$css = file_get_contents(__DIR__.'/theme.css');
	echo "<!doctype html><html><head><title>$title</title><style>$css</style></head><body><h1>$title</h1>";
	echo e\render_exception($exception);
	echo "</body></html>";
	e\complete(true);
}