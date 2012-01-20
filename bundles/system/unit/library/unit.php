<?php

namespace Bundles\Unit;
use Exception;
use e;

/**
 * Evolution Unit Test Class - Unit Tests Sample
 * @author Nate Ferrero
 */
class Unit {
	
	public function tests() {
		
		e::$unit
			->test('addition')
			->description('Sample test: 4 plus 6 should be 10')
			->strictEquals(10);
		
		e::$unit
			->test('subtraction')
			->description('Sample test: 3 minus 7 should be -4')
			->strictEquals(-4);
		
	}
	
	public function addition() {
		return 4 + 6;
	}
	
	public function subtraction() {
		return 3 - 7;
	}
	
}