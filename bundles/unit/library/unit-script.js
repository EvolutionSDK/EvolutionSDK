unit = {
	active: false,
	paused: false,
	index: -1,
	queue: [],
	superindex: -1,
	superqueue: [],
	
	reset: function() {
		unit.index = -1;
		unit.queue = [];
		unit.superindex = -1;
		unit.superqueue = [];
		unit.active = false;
		unit.paused = false;
		$('.led').removeClass('led-amber').removeClass('led-red').removeClass('led-green');
		$('.tests-bar').removeClass('bar-amber').removeClass('bar-red');
		$('.bar-green').css('width', '0%');
		$('.state-paused').hide();
		$('.state-running').hide();
		$('.state-complete').hide();
		$('.state-init').fadeIn();
		$('.passed').text('0');
	},
	
	pause: function() {
		unit.paused = true;
		$('.state-init').hide();
		$('.state-running').hide();
		$('.state-complete').hide();
		$('.state-paused').fadeIn();
	},
	
	resume: function() {
		unit.paused = false;
		$('.state-init').hide();
		$('.state-paused').hide();
		$('.state-complete').hide();
		$('.state-running').fadeIn();
		unit.next();
	},
	
	start: function() {
		if(unit.active)
			return;
		unit.reset();
		unit.superqueue = $('.tests-bar');
		unit.active = true;
		unit.resume();
	},
	
	complete: function() {
		$('.state-paused').hide();
		$('.state-running').hide();
		$('.state-init').hide();
		$('.state-complete').fadeIn();
	},
	
	next: function() {
		unit.index++;
		if(unit.index >= unit.queue.length) {
			return setTimeout(unit.supercomplete, 50);
		}
		
		/**
		 * Handle the current test
		 */
		$cur = $(unit.queue[unit.index]);
		
		$cur.addClass('led-amber');
		
		$.get('/@unit/api/v1/json/' + $cur.attr('title'), unit.responseFuncGen($cur), "json");
	},
	
	supernext: function() {
		unit.superindex++;
		if(unit.superindex >= unit.superqueue.length) {
			return unit.complete();
		}
		
		/**
		 * Handle the current section of tests
		 */
		$cur = $(unit.superqueue[unit.superindex]);
		unit.supercurrent = $cur;
		
		$cur.addClass('bar-amber');
		
		unit.index = -1;
		unit.queue = $cur.parents('li.category').find('.led-off');
		unit.next();
	},
	
	supercomplete: function() {
		if(unit.supercurrent)
			unit.supercurrent.removeClass('bar-amber').addClass('bar-red');
		unit.supercurrent = null;
		
		if(!unit.paused)
			setTimeout(unit.supernext, 50);
	},
	
	responseFuncGen: function($cur) {
		return unit.response;
	},
	
	response: function(data, status) {
		if(status !== 'success')
			return alert('There was an API failure, please reload the test page');
		
		$cur.addClass(data.result == 'pass' ? 'led-green' : 'led-red').removeClass('led-amber');
		
		if(data.result == 'pass') {
			var cat = $cur.parents('li.category');
			var passed = cat.find('span.passed');
			var total = cat.find('span.total');
			var passedBar = $cur.parents('li.category').find('.bar-green');
			var nowPassed = parseInt(passed.text()) + 1;
			passed.text(nowPassed);
			passedBar.css('width', Math.min(Math.round(100 * nowPassed / parseInt(total.text())), 100) + '%');
		}
		
		if(!unit.paused)
			setTimeout(unit.next, 100);
	}
};