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
		return Markdown(file_get_contents($file));
	}

}