<?php

namespace bundles\less;
use Exception;
use e;
use lessc;

/**
 * Less compile bundle
 */

class Bundle {
	public function __callBundle($file) {
		require_once(__DIR__ . '/library/lessc.inc.php');
		$cfile = e::$cache->path() . '/less/' . md5($file) . '.css';
		lessc::ccompile($file, $cfile);
		return $cfile;
	}
}