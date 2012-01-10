<?php

/**
 * Language Bundle
 * @author Nate Ferrero
 */

namespace Bundles\Language;
use Exception;
use e;

class Bundle {
	
	public function _on_framework_loaded() {
		
		// Add manager
		e::configure('manage')->activeAddKey('bundle', __NAMESPACE__, 'language');
	}

}