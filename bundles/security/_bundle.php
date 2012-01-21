<?php

namespace Bundles\Security;
use Exception;
use e;

/**
 * Security Bundle
 * Manage super admin access and authorization logs
 * @author Nate Ferrero
 */
class Bundle {
	
	public function _on_framework_loaded() {
		
		// Add manager
		e::configure('manage')->activeAddKey('bundle', __NAMESPACE__, 'security');
	}
	
}