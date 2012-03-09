<?php

namespace Bundles\Documentation;
use Exception;
use e;

/**
 * Documentation Bundle
 * Presents *.md files within other bundles as a consistent documentation center.
 * @author Nate Ferrero
 */
class Bundle {

	/**
	 * Handle --docs GET variable
	 * @author Nate Ferrero
	 */
	public function _on_framework_loaded() {
		if(isset($_GET['--docs']))
			$this->showDocs($_GET['--docs']);
	}

	/**
	 * Show documentation page
	 * @author Nate Ferrero
	 */
	public function showDocs($page) {
		dump($page);
	}
}