<script>
	function _attach_event(target, eventName, handlerName) {
		if ( target.addEventListener )
			target.addEventListener(eventName, handlerName, false);
		else if ( target.attachEvent )
			target.attachEvent("on" + eventName, handlerName);
		else
			target["on" + eventName] = handlerName;
	}
	function _e_keypress(e) {
		if((e.keyCode == 192 || e.keyCode == 96) && (e.altKey || e.ctrlKey))
			_e_debug_toggle();
	}
	setTimeout(function() {
		document.body.tabIndex = 1;
		_attach_event(document.body, 'keydown', _e_keypress);
	}, 100);
	
	function _e_debug_toggle() {
		var classOld = 'closed';
		var classNew = 'open';
		var w = document.getElementById('-e-debug-panel-wrap');
		
		if(classOld && w.className.indexOf(classOld) > -1)
			w.className = w.className.replace(classOld, classNew);
		else
			w.className = w.className.replace(classNew, classOld);
	}
	
	function _e_show(x) {
		var w = _e_debug_getpanel('show-important', 'show-all');
		document.getElementById('-e-show-important').style.display = (x == 1 ? 'inline' : 'none');
		document.getElementById('-e-show-all').style.display = (x == 1 ? 'none' : 'inline');
		if(typeof $ === 'function') {
			if(x == 1)
				$('._pushl').addClass('_pushl_wide').removeClass('_pushl');
			else
				$('._pushl_wide').addClass('_pushl').removeClass('_pushl_wide');
		}

		return false;
	}

	function _e_filter() {
		if(window._e_filtered) {
			window._e_filtered = false;
			$('.trace-step').each(function(i,step){$(step).show();});
		}
		else {
			var str = prompt('What would you like to isolate?');
			if(str.length) {
				window._e_filtered = true;
				_e_show(1);
				$('.trace-step').each(
					function(i,step){
						$s=$(step);
						$s.find('.name').text().indexOf(str) !== -1 ? $s.show() : $s.hide();
					}
				);
			}
		}

		$('#-e-filter-toggle').text(window._e_filtered ? 'Stop Filtering' : 'Filter Results');
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

	if(typeof $ === 'function') {
		$(function() { $('#-e-filter-toggle').show(); });
	}
</script>
<style>
	#-e-debug-panel-wrap {
		-webkit-transition: all 125ms ease;
		-moz-transition: all 125ms ease;
		-o-transition: all 125ms ease;
		transition: all 125ms ease;
	}
	#-e-debug-panel, #-e-debug-panel .trace-step .reveal {
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
	#-e-debug-panel.fullscreen .body {
		right: 405px;
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
	#-e-debug-panel.windowed .-e-stack {
		display: none;
	}
	#-e-debug-panel.fullscreen .-e-stack {
		display: block;
		position: fixed;
		top: 56px;
		right: 0;
		bottom: 0px;
		width: 400px;
		padding: 20px;
		border-left: 1px solid #aaa;
		background-color: white;
		overflow: auto;
		font-size: 10px;
	}
	#-e-debug-panel.fullscreen .-e-stack h4 {
		margin-top: 0;
		margin-bottom: 1em;
	}
	#-e-debug-panel.fullscreen .-e-stack .step {
		margin-bottom: 1em;
	}
	#-e-debug-panel.fullscreen .-e-stack span.file {
		xfont-size: 8px;
	}

	#-e-debug-panel.fullscreen .trace-step:hover .-e-stack {
		z-index: 1000;
	}

	#-e-debug-panel .title-bar .window-icon {
		height: 13px;
		width: 13px;
		position: absolute;
		top: 5px;
		left: 8px;
		background: url(data:image/png;base64,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);
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
	#-e-debug-panel h5 {
		margin: 0 0 30px;
		font-size: 120%;
	}
	#-e-debug-panel .args {
		font-family: Consolas, monospace;
		font-size: 11px;
		margin-top: 8px;
	}
	#-e-debug-panel .trace-message {
		font-size: 11px;
		margin-top: 8px;
	}
	#-e-debug-panel .trace-step .name {
		font-size: 11px;
		font-weight: bold;
		color: #888;
	}
	#-e-debug-panel .trace-step .name b {
		color: #444;
	}
	#-e-debug-panel .branch {
		padding-left: 20px;
		border-left: 1px solid #aaa;
		border-bottom: 1px solid #aaa;
		margin-bottom: -1px;
	}
	#-e-debug-panel .trace-step {
		padding: 10px 10px 10px 110px;
		background: #f8f8f8;
		border: 1px solid #aaa;
		margin-bottom: -1px;
		font-size: 12px;
		overflow: hidden;
		position: relative;
	}

	#-e-debug-panel.fullscreen .trace-step:hover {
		background: #e0f8ff;
	}
	
	#-e-debug-panel .trace-step .reveal {
		height: 1em;
		overflow: hidden;
	}
	
	#-e-debug-panel .trace-step:hover .reveal {
		height: auto;
		overflow: auto;
		
	}
	
	#-e-debug-panel .trace-step .time {
		border-right: 1px solid #888;
		position: absolute;
		left: 0;
		top: 0;
		bottom: 0;
		width: 80px;
		padding: 10px;
		text-align: center;
		text-shadow: -1px -1px 1px #fff;
		font-weight: bold;
		font-size: 10px;
	}
	#-e-debug-panel .trace-step.hilite {
		background: #fff;
	}
	#-e-debug-panel.show-important .low {
		display: none;
	}
	#-e-debug-panel.show-all .trace-step.high {
		background: #ffb;
	}
	#-e-debug-panel.show-all .trace-step.high.hilite {
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
	#-e-debug-panel .key	 	{color: #000; background: #e0e0e0;
		border-radius: 3px; font-size: 9.5px; padding: 0px 4px 1px; margin: 0 4px 0 0;}
	
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
	#-e-debug-panel div.trace-elapsed-time {
		width: 200px;
		padding: 20px 0;
		text-align: left;
		text-shadow: -1px -1px 1px white;
		font-weight: bold;
		font-size: 9px;
		position: relative;
		color: #a0a;
	}
	#-e-debug-panel div.trace-elapsed-time span.num {
		padding: 8px 8px 9px 0;
		margin: -5px 20px;
		background-color: #fff;
		position: relative;
		font-weight: normal;
	}
	#-e-debug-panel div.trace-elapsed-time span.ellipsis {
		position: absolute;
		overflow: hidden;
		top: 0;
		bottom: 0;
		left: 50px;
		border-left: 2px dotted #a0a;
	}
	<?php echo e\button_style('#-e-debug-panel .link'); ?>
	#-e-debug-panel span.link {
		position: absolute;
		top: 20px; right: 6px;
	}
	#-e-debug-panel span.link._pushl {
		right: 81px;
	}
	#-e-debug-panel span.link._pushl_wide {
		right: 120px;
	}
</style>
<div id="-e-debug-panel-wrap" class="<?php echo class_exists('Bundles\Portal\Bundle') ? (Bundles\Portal\Bundle::$currentException instanceof Exception ? 'open' : 'closed') : 'closed'; ?>">
	<div id="-e-debug-panel" class="windowed show-important">
		<div class="title-bar">
			<div class="window-icon close-icon" onclick="_e_debug_close()"></div>
			<div class="window-icon hide-icon" onclick="_e_debug_hide()"></div>
			<div class="window-icon zoom-icon" onclick="_e_debug_zoom()"></div>
			<span>Evolution SDK&trade; &mdash; Page Event Log</span>
			<span class="link _pushl" id="-e-filter-toggle" style="display: none" onclick="_e_filter()">Filter Results</span>
			<span class='link' style="" id='-e-show-all' onclick='return _e_show(1)'>Show All</span>
			<span class='link' style="display:none;" id='-e-show-important' onclick='return _e_show(0)'>Show Important</span>
		</div>

		<div class="body">
			
			<?php if(class_exists('Bundles\Portal\Bundle') && Bundles\Portal\Bundle::$currentException instanceof Exception) {
				echo '<style>'; include(e\root.e\bundles.'/debug/theme.css'); echo '</style>'; ?>
				<div class="panel-page panel-exception _e_dump"><?php echo e\render_exception(Bundles\Portal\Bundle::$currentException); ?></div>
			<?php } ?>

			<div class="panel-page panel-trace">
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

	if($highest === '@exit')
		$highest = 5;

	while(true) {
		$id++;
		if(!isset(trace::$arr[$id]))
			break;
		if(trace::$arr[$id]['depth'] <= $idepth)
			break;
		$p = trace::$arr[$id]['priority'];

		if($p === '@exit')
			$p = 5;

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
	$millisecs = 1000 * insideTime($id);
	$time = number_format($millisecs, 2);
	
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
	 * Deal with trace exits
	 * @author Nate Ferrero
	 */
	if($trace['priority'] === '@exit') {
		if($millisecs > 10)
			$priority = 'high';
		if($millisecs > 1)
			echo "<div class='trace-elapsed-time $priority'><span class='ellipsis'></span><span class='num'>$time ms elapsed</span></div>";
		continue;
	}

	/**
	 * Display the trace
	 */
	$step = "<div class=\"time\">$time ms</div><div class=\"name\">$trace[title]</div>";
	if(strlen(trim($trace['message'])) > 0)
		$step .= '<div class="trace-message">'.preg_replace('/`([^`]*)`/x', '<code>$1</code>', trim($trace['message'])).'</div>';
	if(count($trace['args']) > 0) {
		$step .= '<div class="args">'.implode(', ', e\stylize_array($trace['args'], $trace['argdepth'])).'</div>';
	}
	if(isset($trace['stack'])) {
		while(substr($trace['stack'][0]['function'], 0, 7) === 'e\\trace')
			array_shift($trace['stack']);
		$stack = e\stylize_stack_trace($trace['stack']);
	} else
		$stack = '<h4>No Stack Trace Available</h4>';
	$sc = $i++ % 2 ? '' : ' hilite';
	echo "<div class=\"trace-step$sc $priority\">$step<div class=\"-e-stack\">$stack</div></div>";
}

// Close any open trees
echo str_repeat('</div>', count(trace::$stack) + 1);

?>
		</div>
	</div>
</div>