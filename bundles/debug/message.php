<?php

namespace e;
use e;

/**
 * Display the exception
 */
if(isset($exception)) {
	$title = "EvolutionSDK&trade; SDK &bull; Exception";
	$css = file_get_contents(__DIR__.'/theme.css');
	echo "<!doctype html><html><head><title>$title</title><style>$css</style></head><body>";
	echo render_exception($exception);
	echo "</body></html>";
	e\complete(true);
}