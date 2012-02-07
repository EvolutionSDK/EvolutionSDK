Events Bundle
=============
The events bundle is probabbly the most important bundle in Evolution. With an event based framework you can trigger any event to happen on any bundle from any file. 

Usage
=====
There are no defined functions in events rather it uses a catch all. For example running.

	e::$events->whatIsYourName(...args...);

Would start by looking in every bundle for `e::$bundle->_on_whatIsYourName(...args...);` it will run every function that matches this criteria on every bundle, and return the results in an array formatted like this (Regardless of the amount of events that have returned).

	array(
		"Bundles\SQL\Bundle" => 'SQL Bundle',
		"Bundles\Members\Bundle" => 'Members Bundle',
		"Bundles\Cache\Bundle" => 'Caching Bundle',
		"Bundles\Security\Bundle" => 'Security Bundle',
		"Bundles\Unit\Bundle" => 'Unit Testing Bundle'
	);

The Master-Events File
======================
Events can be manually enabled or disabled from the `master-events.yaml` file located in your sites `./configure` directory. There are two ways of disabling events.

## Block events by their handlers

You can block bundles form handling any and all events using the `handlers:` key.

	handlers: 
		Bundles\Members\Bundle: enabled
		Bundles\Session\Bundle: disabled

In the above example any events could be caught by the Members Bundle, but not the Session Bundle.

## Block specific events entirely

You can also block certain events from running entirely by using the `events:` key.

	events: 
		framework_loaded: enabled
		after_framework_loaded: disabled

In the above example the `framework_loaded` event will still operate normally. However any events that match `after_framework_loaded` will not be run.