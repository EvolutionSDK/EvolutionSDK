<?php

namespace Bundles\Unit;
use e;

/**
 * Evolution Unit Test Class - Unit Tests Sample
 * @author Nate Ferrero
 */
class Unit {
	
	public function tests() {
		
		e::$unit
			->test('addition')
			->description('4 plus 6 should be 10')
			->strictEquals(10);
		
		e::$unit
			->test('subtraction')
			->description('3 minus 7 should be -4')
			->strictEquals(-4);
		
		e::$unit
			->test('multiplication')
			->description('2 times 8 should be 16')
			->strictEquals(16);
		
		e::$unit
			->test('division')
			->description('8 divided by 2 should be 4')
			->strictEquals(4)
			->between(3, 5);
		
		e::$unit
			->test('division_by_zero')
			->description('120 divided by 0 should give an exception')
			->throws('ErrorException');
		
	}
	
	public function addition() {
		return 4 + 6;
	}
	
	public function subtraction() {
		return 3 - 7;
	}
	
	public function multiplication() {
		return 2 * 8;
	}
	
	public function division() {
		return 8 / 2;
	}
	
	public function division_by_zero() {
		return 120 / 0;
	}
}