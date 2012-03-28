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
	echo render_exception($exception, isset($overrideUrl) ? $overrideUrl : null);
	if(!empty($additional))
		echo $additional;
	echo "</body></html>";
	e\complete(true);
}

if(isset($title)) {
	header("Content-Type: text/html");
	$htmlt = "EvolutionSDK&trade; &bull; $title";
	$css = file_get_contents(__DIR__.'/theme.css');
	echo "<!doctype html><html><head><title>$htmlt</title><style>$css</style></head><body class='_e_dump'>";
	if(isset($body))
		echo "<div class='section'><h1>$title</h1><p>$body</p></div>";
	echo "</body></html>";
	e\complete(true);
}