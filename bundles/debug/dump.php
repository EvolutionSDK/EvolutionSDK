<?php

namespace e;
use e;

const Dump = true;

class Dump {
	public static $references = array();
}

/**
 * Output in plain text if desired
 * @author Nate Ferrero
 */
if(defined('DUMP_FORMAT_TEXT')) {

	/**
	 * Get source and loop vars
	 */
	foreach(dumpVarsText($___DUMP) as $___VAR) {
		$___DUMP .= dumpVarText($___VAR, isset(${$___VAR}) ? ${$___VAR} : null);
	}

	/**
	 * View dump
	 */
	echo "       /* * * * * * * * * * * * * *\n        * EvolutionSDK Debug Dump *\n        * * * * * * * * * * * * * */\n\n";
	echo $___DUMP;
	$stack = debug_backtrace();
	echo "Stack:\n\n";
	array_shift($stack);
	if(defined('DUMP_SINGLE_VAR'))
		array_shift($stack);
	echo dumpVarText(null, $stack);
	e\disable_trace();
	e\complete();
}

/**
 * Ensure proper content type
 * @author Kelly Becker
 */
header("Content-Type: text/html");
e\trace('Debug Dump');

/**
 * Helper functions
 */
function dumpVars(&$out) {
	list($void, $file, $line) = $out;
	if(defined('DUMP_SINGLE_VAR')) {
		
		$backtrace = debug_backtrace();
		$backtrace = $backtrace[3];
		extract($backtrace);
		
	}
	$code = file($file);
	$start = max(1, $line - 4);
	$vrx = '/(\\$)([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
	$out = '<div class="section"><p><span class="line">Lines '.$start.' &ndash; '.$line.'</span>
		of <code>'.$file.'</code></p>';
	$out .= '<h4>Source</h4><div class="trace">';
	$vars = array();
	for($i = $start; $i <= $line; $i++) {
		$src = $code[$i - 1];
		preg_match_all($vrx, $src, $matches);
		foreach($matches[2] as $mvar)
			$vars[$mvar] = 1;
		$src = htmlspecialchars($src);
		$src = preg_replace($vrx, '<code>$0</code>', $src);
		$src = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $src);
		if(defined('DUMP_SINGLE_VAR')) {
			if($i == $line) {
				preg_match('/dump\\((.+?)\\$(.*)\\)/', $src, $var);
				/**
				 * @todo Fix highlighting in various cases
				 * e.g. dump($this->extract_vars($var)); one ( goes missing
				 * @author Nate Ferrero
				 */
				$src = preg_replace_callback('/dump\\(.*\\)/', function($x) {return '<code class="alt">'.strip_tags(array_shift($x)).'</code>';}, $src);
			}
		} else
			$src = preg_replace('/eval\\(d\\)/', '<code class="alt">$0</code>', $src);
		
		$out .= '<div class="step"><span class="line">'.$i.'</span>&nbsp;'.$src.'</div>';
	}
	$out .= '</div></div><div class="section">';
	if(defined('DUMP_SINGLE_VAR')) {
		$var = isset($var[2]) ? $var[2] : 'dump';
		define('DUMP_SINGLE_VARNAME', $var);
		return array('dump');
	}
	return array_keys($vars);
}

/**
 * Dump a variable
 * @author Nate Ferrero
 */
function dumpVar($var, $value, $depth = 0) {
	$out = "";
	if($depth === 0) {
		$xtra = '';
		if(is_array($value))
			$xtra = ' &nbsp; <span class="array">' . e\plural($value) . '</span>';
		if(defined('DUMP_SINGLE_VARNAME'))
			$var = DUMP_SINGLE_VARNAME;
		$out = "<h4><code>$$var</code>$xtra</h4><div class='trace'><div class='step'>";
	}

	/**
	 * Hack for now because get_object_id does not work on large LHTML nodes due to insane amounts of output buffering!
	 */
	$getOID = true;
	$extra = '';
	if($value instanceof \Bundles\LHTML\Node || $value instanceof \Bundles\LHTML\Scope) {
		$getOID = false;
		if($value instanceof \Bundle\LHTML\Node)
			$extra = ' &bull; <b>&lt;'.($value->fake_element).'&gt;</b>';
			
		if($depth > 4) {
			$out .= '<div class="object">' . get_class($value) . ' ' . $extra . '</div></div></div>';
			return $out;
		}
	}
	
	if(is_object($value)) {
		
		// Custom get object id
		if($getOID)
			$oid = e\get_object_id($value);

		// Check for a current reference
		if($getOID && isset(Dump::$references[$oid])) {
			$out .= '<div class="object">Duplicate Object #'.$oid.' <b>'.get_class($value).'</b></div>';
		} else {
			Dump::$references[$oid] = true;
			$out .= '<div class="object">Object '.($oid ? '#' . $oid : '').' <b>'.get_class($value).'</b>'.$extra.'</div>';
			$reflect = new \ReflectionClass($value);
			$methods   = $reflect->getMethods();
			$out .= '<div class="methods"><b>Methods:</b> ';
			foreach ($methods as $method) {
			    $out .= ' &nbsp; <span class="function">' . $method->getName() .
			    	'</span>()';
			}
			$out .= '</div>';
			
			// Properties
			$out .= '<div class="methods"><b>Properties:</b> ';
			
			$props   = $reflect->getProperties();
			if(method_exists($value, '__toArray')) {
				foreach($value->__toArray() as $ak => $av)
					$props[] = array($ak, $av);
			}
			$out .= '<div class="dump"><table class="dump">';
			$listed = array();
			foreach($props as $prop) {
				if(is_array($prop)) {
					$key = array_shift($prop);
					$sub = array_shift($prop);
				} else {
					$key = $prop->getName();
					$prop->setAccessible(true);
					$sub = $prop->getValue($value);
				}
				$listed[$key] = true;
				$c = count($sub);
				if(is_array($sub) && $c > 0)
					$type = 'array';
				else
					$type = 'value';
				$out .= '<tr><td align="right" width="1" class="dump-key"><span class="key">' . htmlspecialchars($key) . '</span></td>';
				$output = dumpVar(null, $sub, $depth + 1);
				if(is_array($sub) && $c > 5) {
					
					// Overflow
					$b1 = "<button onclick=\"this.style.display='none';this.nextSibling.style.display='block'\">Show Array with ".$c." Elements</button>";
					$b2 = "<button onclick=\"this.parentNode.style.display='none';this.parentNode.previousSibling.style.display='inline'\" style='margin-bottom: 8px;'>Hide</button>";
					$output = "$b1<div style='display:none;'>$b2$output</div>";
				}
				if(is_object($sub)) {

					// Overflow
					$class = get_class($sub);
					$b1 = "<button onclick=\"this.style.display='none';this.nextSibling.style.display='block'\">Show $class Object</button>";
					$b2 = "<button onclick=\"this.parentNode.style.display='none';this.parentNode.previousSibling.style.display='inline'\" style='margin-bottom: 8px;'>Hide</button>";
					$output = "$b1<div style='display:none;'>$b2$output</div>";
				}
				$out .= '<td class="dump-'.$type.'">' . $output . '</td></tr>';
			}

			if(!($value instanceof \Iterator)) {
				foreach($value as $key => $sub) {
					if(isset($listed[$key])) continue;
					
					if(is_array($sub) && count($sub) > 0)
						$type = 'array';
					else
						$type = 'value';
					$out .= '<tr><td align="right" width="1" class="dump-key"><span class="key">' . htmlspecialchars($key) . '</span></td>';
					$out .= '<td class="dump-'.$type.'">' . dumpVar(null, $sub, $depth + 1) . '</td></tr>';
					
				}
			}
			$out .= '</table></div>';
			
			// End object output
			$out .= '</div>';
		}
	}
	
	else if($value === array()) {
		$out .= '<span class="array">Empty Array</span>';
	}
	
	else if(is_array($value)) {
		$out .= '<div class="dump"><table class="dump">';
		foreach($value as $key => $sub) {
			$c = count($sub);
			if(is_array($sub) && $c > 0)
				$type = 'array';
			else
				$type = 'value';
			$out .= '<tr><td align="right" width="1" class="dump-key"><span class="key">' . htmlspecialchars($key) . '</span></td>';
			$output = dumpVar(null, $sub, $depth + 1);
			if(is_array($sub) && $c > 5) {
				
				// Overflow
				$b1 = "<button onclick=\"this.style.display='none';this.nextSibling.style.display='block'\">Show Array with ".$c." Elements</button>";
				$b2 = "<button onclick=\"this.parentNode.style.display='none';this.parentNode.previousSibling.style.display='inline'\" style='margin-bottom: 8px;'>Hide</button>";
				$output = "$b1<div style='display:none;'>$b2$output</div>";
			}
			if(is_object($sub)) {

				// Overflow
				$class = get_class($sub);
				$b1 = "<button onclick=\"this.style.display='none';this.nextSibling.style.display='block'\">Show $class Object</button>";
				$b2 = "<button onclick=\"this.parentNode.style.display='none';this.parentNode.previousSibling.style.display='inline'\" style='margin-bottom: 8px;'>Hide</button>";
				$output = "$b1<div style='display:none;'>$b2$output</div>";
			}
			$out .= '<td class="dump-'.$type.'">' . $output . '</td></tr>';
		}
		$out .= '</table></div>';
	}
	
	else if(is_string($value)) {
		stylize_string($value);
		$out .= '<span class="string">\'' . $value . '\'</span>';
	}
	
	else if($value === true) {
		$out .= '<span class="boolean">true</span>';
	}
	
	else if($value === false) {
		$out .= '<span class="boolean">false</span>';
	}
	
	else if($value === null) {
		$out .= '<span class="boolean">null</span>';
	}
	
	else if(is_numeric($value)) {
		$out .= '<span class="number">' . htmlspecialchars($value) . '</span>';
	}
	
	else {
		$out .= print_r($value, true);	
	}
	 
	if($depth === 0)
		$out .= '</div></div>';
	return $out;
}

/**
 * Helper functions
 */
function dumpVarsText(&$out) {
	list($void, $file, $line) = $out;
	if(defined('DUMP_SINGLE_VAR')) {
		
		$backtrace = debug_backtrace();
		$backtrace = $backtrace[3];
		extract($backtrace);
		
	}
	$code = file($file);
	$start = max(1, $line - 4);
	$vrx = '/(\\$)([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
	$out = ' File: '.$file."\n\n";
	$vars = array();
	for($i = $start; $i <= $line; $i++) {
		$src = $code[$i - 1];
		preg_match_all($vrx, $src, $matches);
		foreach($matches[2] as $mvar)
			$vars[$mvar] = 1;
		if(defined('DUMP_SINGLE_VAR')) {
			if($i == $line)
				preg_match('/dumpt\\(\s*\\$(.*)\\)/', $src, $var);
		}
		$out .= str_pad($i, 5, ' ', STR_PAD_LEFT).': '.$src;
	}
	$out .= "\n";
	if(defined('DUMP_SINGLE_VAR')) {
		$var = isset($var[1]) ? $var[1] : 'dump';
		define('DUMP_SINGLE_VARNAME', $var);
		return array('dump');
	}
	return array_keys($vars);
}

/**
 * Dump a variable
 * @author Nate Ferrero
 */
function dumpVarText($var, $value, $depth = 0) {
	$out = "";
	if($depth === 0 && !is_null($var)) {
		if(defined('DUMP_SINGLE_VARNAME'))
			$var = DUMP_SINGLE_VARNAME;
		$out = "  Var: $$var$xtra";
	}

	$out .= str_replace("\n", "\n       ", dumpVarValue($value));
	return $out;
}

/**
 * Dump a variable's value as text
 * @author Nate Ferrero
 */
function dumpVarValue($value, $depth) {
	$out = "\n";

	/**
	 * Hack for now because get_object_id does not work on large LHTML nodes due to insane amounts of output buffering!
	 */
	$getOID = true;
	$extra = '';
	if($value instanceof \Bundles\LHTML\Node || $value instanceof \Bundles\LHTML\Scope) {
		$getOID = false;
		if($value instanceof \Bundle\LHTML\Node)
			$extra = ' - <'.($value->fake_element).'>';
			
		if($depth > 4) {
			$out .= '{' . get_class($value) . '} ' . $extra . '';
			return $out;
		}
	}
	
	if(is_object($value)) {
		
		// Custom get object id
		if($getOID)
			$oid = e\get_object_id($value);

		// Check for a current reference
		if($getOID && isset(Dump::$references[$oid])) {
			$out .= '{Duplicate #'.$oid.' '.get_class($value).'}'.$extra;
		} else {
			Dump::$references[$oid] = true;
			$out .= '{'.($oid ? '#' . $oid . ' ' : '').get_class($value).'}'.$extra;
			$reflect = new \ReflectionClass($value);
			
			$props   = $reflect->getProperties();
			if(method_exists($value, '__toArray')) {
				foreach($value->__toArray() as $ak => $av)
					$props[] = array($ak, $av);
			}
			$listed = array();
			foreach($props as $prop) {
				if(is_array($prop)) {
					$key = array_shift($prop);
					$sub = array_shift($prop);
				} else {
					$key = $prop->getName();
					$prop->setAccessible(true);
					$sub = $prop->getValue($value);
				}
				$listed[$key] = true;
				$lsp = " ";
				$c = count($sub);
				if(is_array($sub))
					$key .= " [".($c ? e\plural($c) : '')."]";
				$out .= "\n$lsp" . '# ' . $key . "";
				$out .= str_replace("\n", "\n$lsp|   ", rtrim(dumpVarValue($sub, $depth + 1))) . "\n";
			}
			foreach($value as $key => $sub) {
				if(isset($listed[$key])) continue;
				$out .= "\n$lsp" . '# ' . $key . "";
				$out .= str_replace("\n", "\n$lsp|   ", rtrim(dumpVarValue($sub, $depth + 1))) . "\n";
				
			}
		}
	}
	
	else if(is_array($value)) {
		$lsp = " ";
		foreach($value as $key => $sub) {
			$out .= "\n$lsp" . '# ' . $key . "";
			$out .= str_replace("\n", "\n$lsp|   ", rtrim(dumpVarValue($sub, $depth + 1))) . "\n";
		}
	}
	
	else if(is_string($value)) {
		$out .= '\'' . $value . '\'';
	}
	
	else if($value === true) {
		$out .= 'true';
	}
	
	else if($value === false) {
		$out .= 'false';
	}
	
	else if($value === null) {
		$out .= 'null';
	}
	
	else if(is_numeric($value)) {
		$out .= $value;
	}
	
	else {
		$out .= print_r($value, true);
	}

	return $out . "\n";
}

/**
 * Get source and loop vars
 */
foreach(dumpVars($___DUMP) as $___VAR) {
	$___DUMP .= dumpVar($___VAR, isset(${$___VAR}) ? ${$___VAR} : null);
}

/**
 * View dump
 */
$title = "EvolutionSDK&trade; &bull; Debug Dump";
$css = file_get_contents(__DIR__.'/theme.css');
echo "<!doctype html><html><head><title>$title</title><style>$css</style></head><body class='_e_dump'><h1>$title</h1>";
echo $___DUMP;
$stack = debug_backtrace();
array_shift($stack);
if(defined('DUMP_SINGLE_VAR'))
	array_shift($stack);
echo stylize_stack_trace($stack);
echo "</body></html>";
e\complete();