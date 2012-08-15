<?php

namespace Bundles\Markdown;
use Exception;
use e;

/**
 * Markdown Bundle
 * @author Nate Ferrero
 */
class Bundle {

	/**
	 * Render a file to markdown
	 * @author Nate Ferrero
	 */
	public function file($file) {

		/**
		 * Check that file is valid
		 */
		if(!is_file($file))
			throw new Exception("Cannot open markdown file `$file`");

		/**
		 * Include the slightly modified markdown class
		 */
		require_once(__DIR__ . '/markdown.php');

		// Get the file contents
		$string = file_get_contents($file);

		// Trace it
		e\trace_enter("Parse Markdown File", htmlentities(substr($string, 0, 250)."..."));

		/**
		 * Transform the text
		 */
		$return = Markdown($string);

		// Trace it
		e\trace("Markdown Parsed", htmlentities($return));

		// Step down a trace
		e\trace_exit();

		return $return;
	}

	/**
	 * Parse a string
	 * @author Kelly Becker
	 */
	public function string($string) {

		/**
		 * Include the slightly modified markdown class
		 */
		require_once(__DIR__ . '/markdown.php');

		// Trace it
		e\trace_enter("Parse Markdown String", htmlentities(substr($string, 0, 250)."..."));

		/**
		 * Transform the text
		 */
		$return = Markdown($string);

		// Trace it
		e\trace("Markdown Parsed", htmlentities($return));

		// Step down a trace
		e\trace_exit();

		return $return;
	}

	/**
	 * Auto load file or string
	 * @author Kelly Becker
	 */
	public function __callBundle($string_file) {
		if(strlen($string_file) < 255 && is_file($string_file))
			return $this->file($string_file);
		else return $this->string($string_file);
	}

	public function __initBundle() {
		ini_set('pcre.backtrack_limit', 9999999999);
		ini_set('pcre.recursion_limit', 9999999999);
	}

}