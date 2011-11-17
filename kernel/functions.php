<?php

namespace e;
use e;

/**
 * Support eval(d)
 */
define('d', 'preg_match("/^(.*)\\((\\d+)\\)\\s\\:\\seval\\(\\)\\\'d code/", __FILE__, $___DUMP);
	if(defined("e\dump"))throw new Exception("Evolution dump already loaded");
	require_once("'.root.bundles.'/system/debug/dump.php");');

/**
 * Get global object ID
 * From: http://stackoverflow.com/questions/2872366/get-instance-id-of-an-object-in-php
 * By: Alix Axel, non-greedy regex fix & xdebug compat by Nate Ferrero
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
 * Plural count function
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
 * Time since filter
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
* Decode a JSON file
*/
function decode_file($file) {
	if(!is_file($file))
		return null;
	return json_decode(file_get_contents($file), true);
}

/**
 * Encode a JSON file
 */
function encode_file($file, $arr) {
	return file_put_contents($file, json_encode($arr));
}

/**
* Decode a base64-encoded JSON file
*/
function decode64_file($file) {
	if(!is_file($file))
		return null;
	return json_decode(base64_decode(file_get_contents($file)), true);
}

/**
 * Encode a JSON file with base64
 */
function encode64_file($file, $arr) {
	return file_put_contents($file, base64_encode(json_encode($arr)));
}

/**
 * Trace class to store variables
 */
class TraceVars {
	public static $arr = array();
	public static $enabled = true;
	public static $stack = array();
	public static $disabled_at = null;
}

/**
 * Trace disable
 */
function disable_trace() {
	TraceVars::$disabled_at = debug_backtrace();
	TraceVars::$enabled = false;
}

/**
 * Trace display
 */
function display_trace() {
	if(TraceVars::$enabled)
		require_once(root.bundles.'/system/debug/trace.php');
	else if(isset($_GET['--debug'])) {
		echo '<style>';
		include(root.bundles.'/system/debug/theme.css');
		echo '</style>';
		echo '<div style="clear:both"></div><div class="section"><h1>Trace Disabled</h1>';
		echo stylize_stack_trace(TraceVars::$disabled_at);
		echo '</div>';
	}
}

/**
 * Trace execution
 */
function trace($title, $message = '', $args = array(), $priority = 0) {
	TraceVars::$arr[] = array('title' => $title, 'message' => $message, 'args' => $args, 
		'priority' => $priority, 'depth' => count(TraceVars::$stack), 'time' => microtime(1));
}

/**
 * Trace exception
 */
function trace_exception($ex) {
	trace('<span class="exception">' . get_class($ex) . '</span>', $ex->getMessage());
}

/**
* Enter an execution block
*/
function trace_enter($title, $message = '', $args = array(), $priority = 0) {
	trace($title, $message, $args, $priority);
	array_push(TraceVars::$stack, $title);
}

/**
 * Exit an execution block
 */
function trace_exit() {
	array_pop(TraceVars::$stack);
}

/**
 * Complete page output
 */
function complete($exception = false) {
	
	/**
	 * If Evolution framework is loaded, send out an complete event
	 */
	if(!$exception && e::$loaded)
		e::events()->complete();
	
	if(e::environment()->active) {
		$dev = 'yes';
	} else
		$dev = e::environment()->requireVar('developmentMode', 'yes | no');
	if($dev == 'yes' || $dev == true) {
		trace('Completed with <code class="alt2">e\\complete()</code>');
		if(!defined('E_COMPLETE_RAN')) {
			define('E_COMPLETE_RAN', true);
			display_trace();
		}
	}
	
	/**
	 * This should be the only time exit or die is used in all code
	 */
	exit;
}

function redirect($url) {
	if(e::$loaded) {
		if(e::environment()->active)
			$dev = 'yes';
		else
			$dev = e::environment()->requireVar('developmentMode', 'yes | no');
	}
	else
		$dev = 'no';
	if($dev == 'yes') {
		trace('Redirected with <code class="alt2">e\\redirect()</code>');
		echo "<div style='font-family: sans, sans-serif; font-size: 12px; padding: 3em'>
			<h1><a href='$url' id='redirect'>Continue...</a></h1><p>Redirecting to <code>$url</code></p></div>";
		echo "<script>var x = confirm('Redirect now?');
			if(x) window.location.replace(document.getElementById('redirect').href);</script>";
	}
	else {
		if(!headers_sent())
			header("Location: $url");
		echo "<meta http-equiv=\"refresh\" content=\"0;url=$url\">";
	}
	if(e::$loaded)
		complete();
	exit;
}

/**
 * Stylize newlines in variables for HTML output
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
 */
function stylize_array($array, $depth = 0) {
	foreach($array as $key => $value) {
		if(is_array($value)) {
			if($depth > 0)
				$array[$key] = "[".implode(', ', stylize_array($value, $depth - 1))."]";
			else
				$array[$key] = "<span class='array'>Array [".count($value)."]</span>";
		} else if(is_string($value)) {
			stylize_string($value);
			$array[$key] = "<span class='string'>&apos;$value&apos;</span>";
		} else if($value === null) {
			$array[$key] = "<span class='boolean'>null</span>";
		} else if($value === false) {
			$array[$key] = "<span class='boolean'>false</span>";
		} else if($value === true) {
			$array[$key] = "<span class='boolean'>true</span>";
		} else if(is_numeric($value)) {
			$array[$key] = "<span class='number'>$value</span>";
		} else if(is_object($value)) {
			$array[$key] = "<span class='object'>Object ".get_class($value)."</span>";
		}
	}
	return $array;
}

/**
 * Stylize a stack trace array
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