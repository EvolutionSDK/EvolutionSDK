<?php

namespace e;
use e;

/**
 * Display the exception
 */
if(isset($exception)) {
	header("Content-Type: text/html");
	$title = "EvolutionSDK&trade; &bull; Exception";
	$css = file_get_contents(__DIR__.'/theme.css');
	echo "<!doctype html><html><head><title>$title</title><style>$css</style></head><body class='_e_dump'>";
	echo render_exception($exception);
	echo "</body></html>";
	e\complete(true);
}