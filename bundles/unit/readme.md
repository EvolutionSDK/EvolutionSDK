Unit Testing Bundle
===================
The unit testing bundle runs various function in hope of receiving an answer that meet specifications. If the specifications dont validate then it shows an error in the manager. Useful for real time site metrics, especially if your site relies on externel server resources such as an API, or a Static Server.

Running Tests
=============
Go to `yourapp/@manage` then select `Unit` from the tiles available. On the top right there will be a button labeled `Start` clicking this button will initialize the unit tests.

Creating your own tests
=======================
Unit tests are defined in your bundle's `./library` folder inside `unit.php`. An example `unit.php` file is shown here.

```php
namespace Bundles\MyBundle;
use Exception;
use e;

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
```

Your tests should go inside the `tests()` function. Tests are created in the format of.

	e::$unit
		->test('addition')
		->description('Sample test: 4 plus 6 should be 10')
		->strictEquals(10);

If you notice from out above whole file `addition()` runs `4 + 6` which equals `10` and since `strictEquals` expects `10` it returns true; satisfying the unit test and passing.

Variable requirements are also stackable, it is completely valid to run.

	e::$unit
		->test('addition')
		->description('Sample test: 4 plus 6 should be 10')
		->strictEquals(10);
		->lessThan(20);

Available validation functions are

	->equals($val);
	->strictEquals($val);
	->greaterThan($val);
	->lessThan($val);
	->greaterThanOrEqual($val);
	->lessThanOrEqual($val);
	->between($val, $args);
	->throws($val, $args, $ex);
	->stringContains($val);
	->instanceOf($val, $b);