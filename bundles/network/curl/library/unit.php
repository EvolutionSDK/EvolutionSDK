<?php

namespace Bundles\Curl;
use Exception;
use e;

/**
 * Curl Unit Tests
 * @author Nate Ferrero
 */
class Unit {
	
	public function tests() {
		
		e::$unit
			->test('google')
			->description('Load http://www.google.com')
			->stringContains('About Google');
		
		e::$unit
			->test('google_https')
			->description('Load https://www.google.com to check HTTPS access')
			->stringContains('About Google');
	}
	
	public function google() {
		return e::$curl->get('http://www.google.com')->body;
	}
	
	public function google_https() {
		return e::$curl->get('https://www.google.com')->body;
	}

}