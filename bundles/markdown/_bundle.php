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

		/**
		 * Transform the text
		 */
		$text = file_get_contents($file);
		$result = Markdown($text);
		dump($result);
		return $result;
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

		/**
		 * Transform the text
		 */
		return Markdown($string);
	}

	/**
	 * Auto load file or string
	 * @author Kelly Becker
	 */
	public function __callBundle($string_file) {
		if(is_file($string_file))
			return $this->file($string_file);
		else return $this->string($string_file);
	}

}