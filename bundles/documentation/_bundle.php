<?php

namespace Bundles\Documentation;
use Exception;
use stack;
use e;

/**
 * Documentation Bundle
 * Presents *.md files within other bundles as a consistent documentation center.
 * @author Nate Ferrero
 */
class Bundle {

	/**
	 * Data
	 */
	private $documentationHTML;
	private $title;
	private $files;
	private $root = '/@documentation/';

	/**
	 * Handle --docs GET variable
	 * @author Nate Ferrero
	 */
	public function _on_framework_loaded() {
		if(isset($_GET['--docs'])) {
			$this->root = '?--docs=';
			$this->route($_GET['--docs']);
		}
	}

	/**
	 * Show documentation page
	 * @author Nate Ferrero
	 */
	public function route($path) {
		if(!is_array($path))
			$path = explode('/', $path);
		$bundle = array_shift($path);
		$page = array_shift($path);
		if(empty($bundle))
			$bundle = 'documentation';
		if(empty($page))
			$page = 'index';
		
		$output = e::$lhtml->file(__DIR__ . '/template.lhtml')->parse()->build();

		/**
		 * Load the documentation page
		 * @author Nate Ferrero
		 */
		$file = stack::bundleLocations($bundle) . '/documentation/' . $page . '.md';

		if(!file_exists($file))
			$file = __DIR__ . '/documentation/-not-found.md';

		$this->documentationHTML = e::$markdown->file($file);

		/**
		 * Get title
		 */
		$tagname = 'h1';
  		preg_match("/<$tagname ?.*>(.*)<\/$tagname>/", $this->documentationHTML, $matches);
  		$this->title = isset($matches[1]) ? $matches[1] : ucwords("$page &bull; $bundle");

  		/**
  		 * Load all possible documentation files
  		 */
  		$this->getFiles();

		/**
		 * Render various sections of the document
		 */
		$this->renderNavFirst($output, $bundle, $page);
		$this->renderNavSecond($output, $bundle, $page);
		$this->renderNavThird($output, $bundle, $page);
		$this->render($output, 'title', $this->title);
		$this->render($output, 'documentation', $this->documentationHTML);

		echo $output;

		e\Complete();
	}

	/**
	 * Get all files
	 * @author Nate Ferrero
	 */
	private function getFiles() {
		$this->files = array(
			'bundles' => array(),
			'other' => array()
		);
		foreach(stack::bundleLocations() as $dir) {
			$bundle = basename($dir);
			foreach(glob($dir . '/documentation/*.md') as $file) {
				if(!isset($this->files['bundles'][$bundle]))
					$this->files['bundles'][$bundle] = array();
				$filename = pathinfo($file, PATHINFO_FILENAME);
				if($filename[0] !== '-')
					$this->files['bundles'][$bundle][$filename] = "$bundle/$filename";
			}
		}
	}

	/**
	 * Render arbitrary section
	 * @author Nate Ferrero
	 */
	private function render(&$output, $section, $content) {
		$output = str_replace("@--$section--@", $content, $output);
	}

	/**
	 * Render first-level navigation
	 * @author Nate Ferrero
	 */
	private function renderNavFirst(&$output, $bundle, $page) {
		$html = '';
		if(count($this->files['bundles']))
			$html .= '<a class="'.(empty($bundle) ? '' : 'selected').'" href="'.$this->root.'">Bundles</a>';
		$this->render($output, 'nav-first', $html);
	}

	/**
	 * Render second-level navigation
	 * @author Nate Ferrero
	 */
	private function renderNavSecond(&$output, $bundle, $page) {
		if(empty($bundle))
			return;
		$html = '';
		foreach($this->files['bundles'] as $name => $files)
			$html .= '<a class="'.($bundle == $name ? 'selected' : '').'" href="'.$this->root.$bundle.'">'.ucwords($name).'</a>';
		$this->render($output, 'nav-second', $html);
	}

	/**
	 * Render third-level navigation
	 * @author Nate Ferrero
	 */
	private function renderNavThird(&$output, $bundle, $page) {
		if(empty($bundle))
			return;
		if(empty($this->files['bundles'][$bundle]))
			return;
		$html = '';
		foreach($this->files['bundles'][$bundle] as $name => $path)
			$html .= '<a class="'.($page == $name ? 'selected' : '').'" href="'.$this->root.$path.'">'.ucwords($name).'</a>';
		$this->render($output, 'nav-third', $html);

	}
}