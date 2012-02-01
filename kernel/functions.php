<?php

namespace e;
use stack;
use e;

/**
 * Get global object ID
 * From: http://stackoverflow.com/questions/2872366/get-instance-id-of-an-object-in-php
 * By: Alix Axel, non-greedy regex fix & xdebug compat by Nate Ferrero
 * @author Nate Ferrero
 */
function get_object_id(&$obj) {
    if(!is_object($obj))
	    return false;
    ob_start();
    var_dump($obj);// object(foo)#INSTANCE_ID (0) { }
	$dump = substr(ob_get_clean(), 0, 250);
    preg_match('~^.+?(\#|\>)(\d+)~s', $dump, $oid);
    return isset($oid[2]) ? $oid[2] : 'unknown';
}

/**
 * Array key index
 * @author Nate Ferrero
 */
function array_key_index(&$arr, $key) {
	$i = 0;
	foreach(array_keys($arr) as $k) {
		if($k == $key) return $i;
		$i++;
	}
}

/**
 * Plural count function
 * @author Nate Ferrero
 */
function plural($num, $word = 'item/s', $empty = '0', $chr = '/') {
	if(is_array($num))
		$num = count($num);
	$word = explode('/', $word);
	return ($num === 0 ? $empty : $num) . ' ' . $word[0] . 
		(count($word) >= 3 ? 
			($num === 1 ? $word[1] : $word[2]) : 
			($num === 1 ? '' : $word[1])
		);
}
/**
 * Render an exception
 */
function render_exception(&$exception) {
	
	// Get message
	$message = $exception->getMessage();
	$message = preg_replace('/`([^`]*)`/x', '<code>$1</code>', str_replace('/', '/&#8203;', $message));
	
	$out = "<div class='section'><h1>Uncaught ".get_class($exception)."</h1>";
	
	// Show message
	if(strlen($message) > 1)
		$out .= "<p>$message</p>";
	
	/**
	 * Start reveal div
	 */	
	$out .= "<div class='reveal'>";
	
	$out .= "<p>Error happened on <span class='line'>line " . $exception->getLine() .
		'</span> of <code class="file">' . str_replace('/', '/&#8203;', $exception->getFile()) . '</code></p>';
	
	/**
	 * Show stack trace
	 */
	$out .= stylize_stack_trace($exception->getTrace());
	
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

/**
 * Time since filter
 * @author David Boskovic
 */
function time_since($source) {
    // array of time period chunks
    $chunks = array(
        array(60 * 60 * 24 * 365 , 'year'),
        array(60 * 60 * 24 * 30 , 'month'),
        array(60 * 60 * 24 * 7, 'week'),
        array(60 * 60 * 24 , 'day'),
        array(60 * 60 , 'hour'),
        array(60 , 'minute'),
    );

    $today = time(); /* Current unix time  */

	$original = $source ? (is_numeric($source) ? $source :  strtotime($source)) : time();
    $since = $today - $original;

	if($since > 604800) {
		$print = date("M jS", $original);

		if($since > 31536000) {
			$print .= ", " . date("Y", $original);
		}
		return $print;

	}

    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {

        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];

        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            // DEBUG print "<!-- It's $name -->\n";
            break;
        }
    }

    $print = ($count == 1) ? '1 '.$name : "$count {$name}s";
	$print = $count == 0 ? 'less than a minute' : $print;
	return $original > $today ? "just now" : $print . " ago";

}

/**
 * Deep count an array
 * @author Nate Ferrero
 */
function deep_count(&$arr) {
	$count = 0;
	foreach($arr as $key => $value) {
		if(is_array($value)) {
			$count += deep_count($value);
		} else {
			$count++;
		}
	}
	return $count;
}

/**
 * Decode a JSON file
 * @author Nate Ferrero
 */
function decode_file($file) {
	if(!is_file($file))
		return null;
	return json_decode(file_get_contents($file), true);
}

/**
 * Encode a JSON file
 * @author Nate Ferrero
 */
function encode_file($file, $arr) {
	return file_put_contents($file, json_encode($arr));
}

/**
* Decode a base64-encoded JSON file
 * @author Nate Ferrero
*/
function decode64_file($file) {
	if(!is_file($file))
		return null;
	return json_decode(base64_decode(file_get_contents($file)), true);
}

/**
 * Encode a JSON file with base64
 * @author Nate Ferrero
 */
function encode64_file($file, $arr) {
	return file_put_contents($file, base64_encode(json_encode($arr)));
}

/**
 * Disable Hit for this page load
 */
function disable_hit() {
	throw new Exception('Not the right way to call a framework feature, use `e::$session->disable_hit()`');
	$args = func_get_args();
	return call_user_func_array(array(e::$session, 'disable_hit'), $args);
}

/**
 * Add Hit
 */
function add_hit() {
	throw new Exception('Not the right way to call a framework feature, use `e::$session->add_hit()`');
	$args = func_get_args();
	return call_user_func_array(array(e::$session, 'add_hit'), $args);
}

/**
 * Trace class to store variables
 * @author Nate Ferrero
 */
class TraceVars {
	public static $arr = array();
	public static $enabled = true;
	public static $stack = array();
	public static $disabled_at = null;
}

/**
 * Trace disable
 * @author Nate Ferrero
 */
function disable_trace() {
	TraceVars::$disabled_at = debug_backtrace();
	TraceVars::$enabled = false;
}

/**
 * Trace display
 * @author Nate Ferrero
 */
function display_trace() {
	if(TraceVars::$enabled)
		require_once(root.bundles.'/debug/trace.php');
	else if(isset($_GET['--debug'])) {
		echo '<style>';
		include(root.bundles.'/debug/theme.css');
		echo '</style>';
		echo '<div style="clear:both"></div><div class="section"><h1>Trace Disabled</h1>';
		echo stylize_stack_trace(TraceVars::$disabled_at);
		echo '</div>';
	}
}

/**
 * Trace execution
 * @author Nate Ferrero
 */
function trace($title, $message = '', $args = array(), $priority = 0, $argdepth = 1) {
	TraceVars::$arr[] = array('title' => $title, 'message' => $message, 'args' => $args, 'argdepth' => 1,
		'priority' => $priority, 'depth' => count(TraceVars::$stack), 'time' => microtime(1));
}

/**
 * Trace exception
 * @author Nate Ferrero
 */
function trace_exception($ex) {
	trace('<span class="exception">' . get_class($ex) . '</span>', $ex->getMessage());
}

/**
* Enter an execution block
 * @author Nate Ferrero
*/
function trace_enter($title, $message = '', $args = array(), $priority = 0) {
	trace($title, $message, $args, $priority);
	array_push(TraceVars::$stack, $title);
}

/**
 * Exit an execution block
 * @author Nate Ferrero
 */
function trace_exit() {
	array_pop(TraceVars::$stack);
}

/**
 * Complete page output
 * @author Nate Ferrero
 */
function complete($exception = false) {
	
	/**
	 * Exception occurred before framework was defined
	 */
	if(!class_exists('Stack', false))
		die;
	
	/**
	 * If Evolution framework is loaded, send out an complete event
	 */
	if(Stack::$loaded && !$exception)
		e::$events->complete();
	
	if(!Stack::$loaded)
		$dev = 'yes';
	else if(e::$environment->active) {
		$dev = 'yes';
	} else
		$dev = e::$environment->requireVar('Development.Master', 'yes | no');
	
	if($dev === 'yes' || $dev === true) {
		trace('Completed with <code class="alt2">e\\complete()</code>');
		if(!defined('E_COMPLETE_RAN')) {
			define('E_COMPLETE_RAN', true);
			$trace = e::$environment->requireVar('Development.Trace', 'yes | no');
			if(e::$security->isDeveloper() || ($trace !== 'no' && $trace != false))
				display_trace();
		}
	}
	
	/**
	 * Can only save hits if the framework is operational
	 */
	if(Stack::$loaded) {
		/**
		 * Save total time required to exec to hit
		 */
		e::$events->complete_hit(timer(true));
	}
	
	/**
	 * This should be the only time exit or die is used in all code
	 */
	exit;
}

/**
 * Full-featured redirection
 * @author Nate Ferrero
 */
function redirect($url) {
	if(stack::$loaded) {
		if(e::$environment->active)
			$dev = 'yes';
		else
			$dev = e::$environment->requireVar('Development.Master', 'yes | no');
	}
	else
		$dev = 'no';
	if($dev == 'yes' && false) {
		trace('Redirected with <code class="alt2">e\\redirect()</code>');
		echo "<div style='font-family: sans, sans-serif; font-size: 12px; padding: 3em'>
			<h1><a href='$url' id='redirect'>Continue...</a></h1><p>Redirecting to <code>$url</code></p></div>";
		echo "<script>if(false && confirm('Redirect now?'))window.location.replace(document.getElementById('redirect').href);</script>";
	}
	else {
		if(!headers_sent())
			header("Location: $url");
		echo "<meta http-equiv=\"refresh\" content=\"0;url=$url\">";
	}
	if(stack::$loaded)
		complete();
	exit;
}

/**
 * Stylize newlines in variables for HTML output
 * @author Nate Ferrero
 */
function stylize_string(&$value) {
	$value = htmlspecialchars($value);
	$replace_nl = '<span class="invisibles crarr">&crarr;</span>';
	$replace_tab = '<span class="invisibles rarr">&rarr;</span>';
	$value = str_replace("\r\n", $replace_nl, $value);
	$value = str_replace("\n",   $replace_nl, $value);
	$value = str_replace("\r",   $replace_nl, $value);
	$value = str_replace("\t",   $replace_tab, $value);
}

/**
 * Color PHP values in an array
 * @author Nate Ferrero
 */
function stylize_array($array, $depth = 0) {
	foreach($array as $key => $value) {
		if(is_array($value)) {
			if($depth > 0)
				$array[$key] = "<span class='key'>$key</span>[".implode(', ', stylize_array($value, $depth - 1))."]";
			else
				$array[$key] = "<span class='key'>$key</span><span class='array'>Array [".count($value)."]</span>";
		} else if(is_string($value)) {
			stylize_string($value);
			$array[$key] = "<span class='key'>$key</span><span class='string'>&apos;$value&apos;</span>";
		} else if($value === null) {
			$array[$key] = "<span class='key'>$key</span><span class='boolean'>null</span>";
		} else if($value === false) {
			$array[$key] = "<span class='key'>$key</span><span class='boolean'>false</span>";
		} else if($value === true) {
			$array[$key] = "<span class='key'>$key</span><span class='boolean'>true</span>";
		} else if(is_numeric($value)) {
			$array[$key] = "<span class='key'>$key</span><span class='number'>$value</span>";
		} else if(is_object($value)) {
			$array[$key] = "<span class='key'>$key</span><span class='object'>Object ".get_class($value)."</span>";
		}
	}
	return $array;
}

/**
 * Stylize a stack trace array
 * @author Nate Ferrero
 */
function stylize_stack_trace($trace) {
	$out .= '<h4>Stack Trace</h4><div class="trace">';
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
	return $out;
}

/**
 * Output global button CSS3
 * @author Nate Ferrero
 */
function button_style($sel, $size = 11) {
	return <<<EOF
		
$sel {
	font-size: {$size}px;
	font-weight: normal;
	border-radius: 4px;
	background: #ddd;
	color: #333;
	padding: 0.4em 1em;
	margin: 0;
	position: relative;
	top: -0.2em;
	cursor: pointer;
	user-select: none;
	-webkit-user-select: none;
	text-decoration: none;
	background: -moz-linear-gradient(
		top,
		#ffffff 0%,
		#b5b5b5);
    background: -webkit-gradient(
		linear, left top, left bottom, 
		from(#ffffff),
		to(#b5b5b5));
	-moz-box-shadow:
		0px 1px 3px rgba(107,107,107,0.5),
		inset 0px 0px 3px rgba(255,255,255,1);
    -webkit-box-shadow:
		0px 1px 3px rgba(107,107,107,0.5),
		inset 0px 0px 3px rgba(255,255,255,1);
    text-shadow:
		0px -1px 0px rgba(097,090,097,0.2),
		0px 1px 0px rgba(255,255,255,1);
	border: 1px solid #949494;
}
$sel:hover {
	background: -moz-linear-gradient(
		top,
		#ffffff 0%,
		#dddddd);
	background: -webkit-gradient(
		linear, left top, left bottom, 
		from(#ffffff),
		to(#dddddd));
}
$sel:active {
	background: #dddddd;
	-moz-box-shadow:
		inset 0px 1px 3px rgba(107,107,107,0.5),
		0px 0px 3px rgba(255,255,255,1);
	-webkit-box-shadow:
		inset 0px 1px 3px rgba(107,107,107,0.5),
		0px 0px 3px rgba(255,255,255,1);
}
$sel.disabled {
	background: #dddddd;
	-moz-box-shadow:none;
	-webkit-box-shadow:none;
}

EOF;
}

/**
 * Simple timer
 * @author Kelly Becker
 */
function timer($return = false) {
	static $start = 0;
	
	if(!$return && !($start > 0)) $start = microtime();
	else if(!($start > 0)) return false;
	
	else return microtime() - $start;
}

/**
 * Merge arrays recursively
 * @author Kelly Becker
 */
function array_merge_recursive_simple() {
    if (func_num_args() < 2) {
        trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
        return;
    }
    $arrays = func_get_args();
    $merged = array();
    while ($arrays) {
        $array = array_shift($arrays);
        if (!is_array($array)) {
            trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
            return;
        }
        if (!$array)
            continue;
        foreach ($array as $key => $value)
            if (is_string($key))
                if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
                    $merged[$key] = call_user_func(__FUNCTION__, $merged[$key], $value);
                else
                    $merged[$key] = $value;
            else
                $merged[] = $value;
    }
    return $merged;
}

/**
 * Fix backslashes in paths on Windows
 */
function convert_backslashes($str) {
	return str_replace(
		'\n', '/n', str_replace(
		'\r', '/r', str_replace(
		'\t', '/t', str_replace(
		'\\', '/', $str))));
}

/**
 * Error handler
 * @author Nate Ferrero
 */
function error_handler($no, $msg, $file, $line) {
	
	// Ignore warnings and notices unless in strict mode 
	if(!isset($_GET['--strict'])) {
		switch ($no) {
			case E_WARNING:
			case E_NOTICE:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			case E_STRICT:
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
			return true;
		}
	}
	throw new \ErrorException($msg, 0, $no, $file, $line);
}

/**
 * Show exceptions
 */
function handle($exception) {	
	try {
		/**
		 * Trace the exception
		 */
		trace_exception($exception);
	
		/**
		 * If Evolution framework is loaded, send out an exception event
		 */
		if(class_exists('Stack', false) && Stack::$loaded) {
			try {
				e::$events->exception($exception);
			} catch(Exception $exception) {}
		}
		require_once(root.bundles.'/debug/message.php');
	}
	catch(\Exception $e) {
		echo "<div class='section'><h1>".get_class($e)." in Exception Handler</h1>";
		echo $e->getMessage()." <br />";
		echo "<p>Error happened on <span class='line'>line " . $e->getLine() .
			'</span> of <code class="file">' . $e->getFile() . '</code></p><br />';
		echo "<br />";
		echo "<div class='section'><h1>Original ".get_class($exception)."</h1>";
		echo $exception->getMessage()." <br />";
		echo "<p>Error happened on <span class='line'>line " . $exception->getLine() .
			'</span> of <code class="file">' . $exception->getFile() . '</code></p></div></div>';
	}
}