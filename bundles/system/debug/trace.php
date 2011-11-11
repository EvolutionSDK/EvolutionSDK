<script>
	setTimeout(function() {
		window.document.body.onkeydown = function(e) {
			if(e.keyCode == 192 && (e.altKey || e.ctrlKey || e.metaKey))
				__trace('toggle');
		};
	}, 100);
	
	function __trace(x) {
		var w = document.getElementById('-e-debug-panel');
		if(x == 'toggle')
			x = w.style.display == 'block' ? 'none' : 'block';
		w.style.display = x;
	}
	
	function _e_show(x) {
		var w = document.getElementById('-e-debug-panel');
		var imp = '-e-show-important';
		var all = '-e-show-all';
		w.className = (x == 1 ? all : imp);
		document.getElementById(imp).style.display = (x == 1 ? 'inline' : 'none');
		document.getElementById(all).style.display = (x == 1 ? 'none' : 'inline');
		return false;
	}
</script>
<style>
	#-e-debug-panel {
		font-family: Sans, Lucida Grande, Tahoma, Verdana, Helvetica, sans-serif;
		font-size: 14px;
		position: fixed; left: 50px; right: 50px; bottom: 50px; top: 50px;
		overflow: auto; padding: 30px;
		background: #eee; box-shadow: 0 0 8px black, 0 0 20px 20px #fff;
		border-radius: 20px;
		z-index: 10000000;
		display: none;
	}
	#-e-debug-panel a.link {
		margin-left: 10px;
	}
	#-e-debug-panel h1 {
		margin: 0 0 30px;
		font-size: 120%;
	}
	#-e-debug-panel .args {
		font-family: Consolas, monospace;
		font-size: 11px;
		margin-top: 8px;
	}
	#-e-debug-panel .message {
		font-size: 11px;
		margin-top: 8px;
	}
	#-e-debug-panel .step .name {
		font-size: 11px;
		font-weight: bold;
		color: #888;
	}
	#-e-debug-panel .step .name b {
		color: #444;
	}
	#-e-debug-panel .branch {
		padding-left: 20px;
		border-left: 1px solid #aaa;
		border-bottom: 1px solid #aaa;
		margin-bottom: -1px;
	}
	#-e-debug-panel .step {
		padding: 10px;
		background: #f8f8f8;
		border: 1px solid #aaa;
		margin-bottom: -1px;
		font-size: 12px;
		overflow: hidden;
	}
	#-e-debug-panel .step.hilite {
		background: #fff;
	}
	#-e-debug-panel.-e-show-important .low {
		display: none;
	}
	#-e-debug-panel.-e-show-all .step.high {
		background: #ffb;
	}
	#-e-debug-panel.-e-show-all .step.high.hilite {
		background: #ffa;
	}
	#-e-debug-panel .line, #-e-debug-panel .func, #-e-debug-panel .parens {
		font-weight: bold;
	}
	#-e-debug-panel .func, 
	#-e-debug-panel .function 	{color: darkblue;	}
	#-e-debug-panel .line 		{color: purple;		}
	#-e-debug-panel .array 		{color: orange;		}
	#-e-debug-panel .string 	{color: green;		}
	#-e-debug-panel .boolean  	{color: orange;		}
	#-e-debug-panel .number 	{color: red;		}
	#-e-debug-panel .object 	{color: darkred;	}
	#-e-debug-panel .class 		{color: darkred;	}
	#-e-debug-panel .exception 	{color: #b00;		}
	
	#-e-debug-panel code {
		background: #fe8;
		text-shadow: 1px 1px 1px #ffa;
		font-family: Consolas, monospace;
		padding: 2px 4px;
		margin: 0 2px;
		border-radius: 4px;
	}
	#-e-debug-panel code.alt {
		background:#237;	
		text-shadow: 1px 1px 1px #124;
		color: #fff;
	}
	#-e-debug-panel code.alt2 {
		background:#732;	
		text-shadow: 1px 1px 1px #421;
		color: #fff;
	}
	<?php echo e\button_style('#-e-debug-panel .link'); ?>
</style>
<div id="-e-debug-panel" class="-e-show-important">
<a class='link' onclick="return __trace('none')" style="float:right;">Hide</a>
<a class='link' href="/@manage" style="float:right;">Manage System</a>
<h1>Execution Trace
	<span class='link' id='-e-show-all' onclick='return _e_show(1)'>Show All</span>
	<span class='link' id='-e-show-important' onclick='return _e_show(0)' style='display:none;'>Show Important</span>
</h1>

<?php

/**
 * Output a pretty trace
 */
use e\TraceVars as trace;

function highestPriority($id) {
	if(!isset(trace::$arr[$id]))
		return 0;
	$idepth = trace::$arr[$id]['depth'];
	$highest = trace::$arr[$id]['priority'];
	while(true) {
		$id++;
		if(!isset(trace::$arr[$id]))
			break;
		if(trace::$arr[$id]['depth'] <= $idepth)
			break;
		$p = trace::$arr[$id]['priority'];
		if($p < $highest)
			$highest = $p;
	}
	return $highest;
}

$lastDepth = 0;
$i = 0;
foreach(trace::$arr as $id => $trace) {
	$depth = $trace['depth'];
	if($depth > $lastDepth)
		echo "<div class=\"branch\">";
	else if($depth < $lastDepth)
		echo str_repeat("</div>", $lastDepth - $depth);
	$lastDepth = $depth;
	if(highestPriority($id) > 2)
		$priority = 'low';
	else
		$priority = 'high';
	$step = "<div class=\"name\">$trace[title]</div>";
	if(strlen(trim($trace['message'])) > 0)
		$step .= '<div class="message">'.preg_replace('/`([^`]*)`/x', '<code>$1</code>', trim($trace['message'])).'</div>';
	if(count($trace['args']) > 0) {
		$step .= '<div class="args">'.implode(', ', e\stylize_array($trace['args'], 1)).'</div>';
	}
	$sc = $i++ % 2 ? '' : ' hilite';
	echo "<div class=\"step$sc $priority\">$step</div>";
}

// Close any open trees
echo str_repeat('</div>', count(trace::$stack) + 1);