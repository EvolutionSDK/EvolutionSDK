<script>
	setTimeout(function() {
		window.document.body.onkeydown = function(e) {
			if(e.keyCode == 192 && (e.altKey || e.ctrlKey))
				_e_debug_toggle();
		};
	}, 100);
	
	function _e_debug_toggle() {
		var classOld = 'closed';
		var classNew = 'open';
		var w = document.getElementById('-e-debug-panel-wrap');
		console.log(w);
		if(classOld && w.className.indexOf(classOld) > -1)
			w.className = w.className.replace(classOld, classNew);
		else
			w.className = w.className.replace(classNew, classOld);
	}
	
	function _e_show(x) {
		var w = _e_debug_getpanel('show-important', 'show-all');
		document.getElementById('-e-show-important').style.display = (x == 1 ? 'inline' : 'none');
		document.getElementById('-e-show-all').style.display = (x == 1 ? 'none' : 'inline');
		return false;
	}
	
	function _e_debug_getpanel(classOld, classNew) {
		var w = document.getElementById('-e-debug-panel');
		if(classOld && w.className.indexOf(classOld) > -1)
			w.className = w.className.replace(classOld, classNew);
		else
			w.className = w.className.replace(classNew, classOld);
		return w;
	}
	
	function _e_debug_zoom() {
		_e_debug_getpanel('windowed', 'fullscreen');
	}
	
	function _e_debug_hide() {
		_e_debug_toggle();
	}
	
	function _e_debug_close() {
		_e_debug_toggle();
	}
</script>
<style>
	#-e-debug-panel-wrap {
		-webkit-transition: all 125ms ease;
		-moz-transition: all 125ms ease;
		-o-transition: all 125ms ease;
		transition: all 125ms ease;
	}
	#-e-debug-panel {
		-webkit-transition: all 250ms ease;
		-moz-transition: all 250ms ease;
		-o-transition: all 250ms ease;
		transition: all 250ms ease;
	}
	#-e-debug-panel-wrap {
		position: fixed;
		left: 0;
		right: 0;
		top: 0;
		z-index: 9999999999;
		overflow: hidden;
	}
	#-e-debug-panel-wrap.closed {
		height: 0;
		opacity: 0;
	}
	#-e-debug-panel-wrap.open {
		opacity: 1;
		height: 100%;
	}
	#-e-debug-panel {
		font-family: Sans, "Lucida Grande", Tahoma, Verdana, Helvetica, sans-serif;
		font-size: 14px;
		overflow: hidden;
		background: #eee;
		z-index: 10000000;
	}
	#-e-debug-panel.windowed {
		margin: 50px auto;
		width: 600px;
		height: 400px;
		box-shadow: 0 20px 50px rgba(0,0,0,0.75);
		border: 1px solid #888;
		border-radius: 4px;
		border-radius: 4px 4px 0 0;
		position: relative;
	}
	#-e-debug-panel.fullscreen {
		position: absolute;
		top: 0;
		bottom: 0;
		left: 0;
		right: 0;
		width: 100%;
		height: 100%;
	}
	#-e-debug-panel .body {
		position: absolute;
		top: 56px;
		left: 0;
		right: 0;
		bottom: 0;
		padding: 20px;
		overflow: auto;
		background: #fff;
	}
	#-e-debug-panel .title-bar {
		cursor: default;
		font-size: 13px;
		height: 53px;
		padding-top: 2px;
		border-bottom: 1px solid #6B6B6B;
		position: relative;
		background-image: linear-gradient(bottom, rgb(169,169,170) 0%, rgb(225,225,225) 80%);
		background-image: -o-linear-gradient(bottom, rgb(169,169,170) 0%, rgb(225,225,225) 80%);
		background-image: -moz-linear-gradient(bottom, rgb(169,169,170) 0%, rgb(225,225,225) 80%);
		background-image: -webkit-linear-gradient(bottom, rgb(169,169,170) 0%, rgb(225,225,225) 80%);
		background-image: -ms-linear-gradient(bottom, rgb(169,169,170) 0%, rgb(225,225,225) 80%);

		background-image: -webkit-gradient(
			linear,
			left bottom,
			left top,
			color-stop(0, rgb(169,169,170)),
			color-stop(0.8, rgb(225,225,225))
		);
		text-align: center;
		color: #2F2F2F;
		text-shadow: 1px 1px 1px #DBD9D4;
	}
	#-e-debug-panel.windowed .title-bar {
		border-radius: 4px 4px 0 0;
	}
	#-e-debug-panel .title-bar .window-icon {
		height: 13px;
		width: 13px;
		position: absolute;
		top: 5px;
		left: 8px;
		background: url(http://i.imgur.com/Xz6DO.png);
	}
	/* CLOSE icon */
	#-e-debug-panel .title-bar .close-icon {
		background-position: 0 0;
	}
	#-e-debug-panel .title-bar .close-icon:hover {
		background-position: 0 -13px;
	}
	#-e-debug-panel .title-bar .close-icon:active {
		background-position: 0 -26px;
	}
	/* HIDE icon */
	#-e-debug-panel .title-bar .hide-icon {
		left: 27px;
		background-position: -19px 0;
	}
	#-e-debug-panel .title-bar .hide-icon:hover {
		background-position: -19px -13px;
	}
	#-e-debug-panel .title-bar .hide-icon:active {
		background-position: -19px -26px;
	}
	/* ZOOM icon */
	#-e-debug-panel .title-bar .zoom-icon {
		left: 47px;
		background-position: 13px 0;
	}
	#-e-debug-panel .title-bar .zoom-icon:hover {
		background-position: 13px -13px;
	}
	#-e-debug-panel .title-bar .zoom-icon:active {
		background-position: 13px -26px;
	}
	/* END ICONS */
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
		padding: 10px 10px 10px 90px;
		background: #f8f8f8;
		border: 1px solid #aaa;
		margin-bottom: -1px;
		font-size: 12px;
		overflow: hidden;
		position: relative;
	}
	#-e-debug-panel .step .time {
		border-right: 1px solid #888;
		position: absolute;
		left: 0;
		top: 0;
		bottom: 0;
		width: 60px;
		padding: 10px;
		text-align: center;
		text-shadow: -1px -1px 1px #fff;
		font-weight: bold;
		font-size: 10px;
	}
	#-e-debug-panel .step.hilite {
		background: #fff;
	}
	#-e-debug-panel.show-important .low {
		display: none;
	}
	#-e-debug-panel.show-all .step.high {
		background: #ffb;
	}
	#-e-debug-panel.show-all .step.high.hilite {
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
		display: inline;
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
<div id="-e-debug-panel-wrap" class="closed">
	<div id="-e-debug-panel" class="windowed show-important">
		<div class="title-bar">
			<div class="window-icon close-icon" onclick="_e_debug_close()"></div>
			<div class="window-icon hide-icon" onclick="_e_debug_hide()"></div>
			<div class="window-icon zoom-icon" onclick="_e_debug_zoom()"></div>
			<span>Evolution SDK&trade; &mdash; Page Event Log</span>
		</div>
		<div class="body">
			<span class='link' style="float:right;" id='-e-show-all' onclick='return _e_show(1)'>Show All</span>
			<span class='link' style="float:right;display:none;" id='-e-show-important' onclick='return _e_show(0)'>Show Important</span>
			<p style="margin-top: 0;">You are on <?php echo gethostname(); ?></p>

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

function insideTime($id) {
	if(!isset(trace::$arr[$id]))
		return 'n/a';
	$idepth = trace::$arr[$id]['depth'];
	$start = trace::$arr[$id]['time'];
	while(true) {
		$id++;
		if(!isset(trace::$arr[$id]))
			break;
		if(trace::$arr[$id]['depth'] <= $idepth)
			return trace::$arr[$id]['time'] - $start;
	}
	return trace_end_time - $start;
}

$lastDepth = 0;
$i = 0;
define('trace_end_time', microtime(true));

foreach(trace::$arr as $id => $trace) {
	if(!isset($start))
		$start = $trace['time'];
	
	/**
	 * Get trace time
	 */
	$time = number_format(1000 * insideTime($id), 2);
	
	/**
	 * Get trace depth
	 */
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
		
	/**
	 * Display the trace
	 */
	$step = "<div class=\"time\">$time ms</div><div class=\"name\">$trace[title]</div>";
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

?>
	</div>
</div>