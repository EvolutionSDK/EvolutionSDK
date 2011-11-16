<?php

namespace bundles\less;
use Exception;
use e;

/**
 * Less compile bundle
 */

class Bundle {
	public function __invoke_bundle($file) {
		require_once(__DIR__ . '/library/lessc.inc.php');
		return e::cache()->path() . '/less/' . md5($file) . '.css';
	}
}