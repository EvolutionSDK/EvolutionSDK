<?php

namespace bundles\less;
use Exception;

/**
 * Less compile bundle
 */

class Bundle {
	public function __invoke_bundle($file) {
		require_once(__DIR__ . '/library/lessc.inc.php');
		$cfile = e::cache()->path() . '/less/' . md5($file) . '.css';
		dump($cfile);
		return ;
	}
}