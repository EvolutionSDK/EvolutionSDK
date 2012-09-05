<?php

namespace Bundles\PHP;
use Exception;
use e;

/**
 * PHP Bundle
 * Tests for proper PHP configuration and version
 * @author Nate Ferrero
 */
class Bundle {
	
	public function _on_framework_loaded() {
		
		// Add manager
		e::configure('manage')->activeAddKey('bundle', __NAMESPACE__, 'php');
	}
	
}