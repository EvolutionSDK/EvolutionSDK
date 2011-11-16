<?php

namespace evolution;
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

/**
 * Render an exception
 */
function render_exception(&$exception) {
	
	// Get message
	$message = $exception->getMessage();
	$message = preg_replace('/`([^`]*)`/x', '<code>$1</code>', $message);
	
	$out = "<div class='section'><h1>Uncaught ".get_class($exception)."</h1>";
	
	// Show message
	if(strlen($message) > 1)
		$out .= "<p>$message</p>";
	
	/**
	 * Start reveal div
	 */	
	$out .= "<div class='reveal'>";
	
	$out .= "<p>Error happened on <span class='line'>line " . $exception->getLine() .
		'</span> of <code class="file">' . $exception->getFile() . '</code></p>';
	
	/**
	 * Show stack trace
	 */
	$out .= '<h4>Stack Trace</h4><div class="trace">';
	$trace = (array) $exception->getTrace();
	foreach($trace as $i => $step) {
		if($step['function'] == 'evolution\{closure}')
			continue;
		
		$class = isset($step['class']) 		? "<span class='class'>$step[class]</span>$step[type]" : '';
		$args = isset($step['args']) 		? implode(', ', e\stylize_array($step['args'], 1)) : '';
		$func = isset($step['function']) 	? "<span class='func'>$step[function]</span><span class='parens'>(</span>$args<span class='parens'>)</span>" : '';
		$file = isset($step['file']) 		? "<span class='file'>in $step[file]</span>" : '';
		$line = isset($step['line']) 		? "on <span class='line'>line $step[line]</span>" : '';
		
		$out .= "<div class='step'>$class$func $file $line</div>";
	}
	$out .= '</div>';
	
	/**
	 * End reveal div
	 */
	$out .= '</div>';
	
	// Check for previous exception
	$prev = $exception->getPrevious();
	if(is_object($prev))
		$out .= render_exception($prev);
		
	// Close section
	$out .= '</div>';
	
	// Return output
	return $out;
}
