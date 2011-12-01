<?php

namespace Bundles\Markdown;

require_once("library/markdown.php");

class Bundle {
	
	public function __invokeBundle($string = '') {
		return Markdown($string);
	}
	
}