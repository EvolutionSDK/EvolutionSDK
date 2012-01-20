<?php

namespace Bundles\PHP;
use Exception;
use e;

/**
 * PHP Unit Test Class - Checks Version / Modules
 * @author Nate Ferrero
 */
class Unit {
	
	public function tests() {
		
		e::$unit
			->test('php_version')
			->description('PHP Version is 5.3.1 or later')
			->greaterThanOrEqual(50301);
		
		e::$unit
			->test('mcrypt')
			->description('Mcrypt Extension')
			->strictEquals(true);
		
	}
	
	public function php_version() {
    	$version = explode('.', \PHP_VERSION);
	    return $version[0] * 10000 + $version[1] * 100 + $version[2];
	}
	
	public function mcrypt() {
		return function_exists('mcrypt_module_open');
	}
	
}