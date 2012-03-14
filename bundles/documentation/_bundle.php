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

		/**
		 * Allow multiple routing methods
		 */
		if(!is_array($path))
			$path = explode('/', $path);
		$bundle = array_shift($path);

  		/**
  		 * Load all possible documentation files
  		 */
  		$this->getFiles();

		/**
		 * Handle static routing
		 */
		$static = false;
		if($bundle == '~static') {
			$bundle = array_shift($path);
			$static = true;
		}

		/**
		 * Default bundle and page
		 */
		if(empty($bundle))
			$bundle = 'documentation';

		/**
		 * Check for books
		 */
		$book = strlen($bundle) == 32 ? $bundle : null;
		$bundle = is_null($book) ? $bundle : null;

		/**
		 * Handle static
		 */
		if($static)
			return $this->staticResource($bundle, $book, $path);
		
		/**
		 * Load proper page
		 */
		$page = array_shift($path);
		if(empty($page))
			$page = 'index';

		$output = e::$lhtml->file(__DIR__ . '/template.lhtml')->parse()->build();

		/**
		 * Load the documentation page
		 * @author Nate Ferrero
		 */
		if(!empty($bundle)) {
			$dir = stack::bundleLocations($bundle) . '/documentation';
			$done = false;
			while(true) {
				$file = "$dir/$page.md";
				if($done || is_file($file))
					break;

				/**
				 * Get the first file
				 */
				$done = true;
				foreach(glob("$dir/*.md") as $file) {
					$page = pathinfo($file, PATHINFO_FILENAME);
					continue;
				}
			}
		}
		if(!empty($book)) {
			$dir = $this->files['books'][$book];

			/**
			 * Index if exists
			 */
			if($page == 'index')
				$page = file_exists($dir . '/index.md') ? md5('index') : null;

			foreach(glob($dir . '/*.md') as $current) {
				$name = pathinfo($current, PATHINFO_FILENAME);
				if($name[0] == '-')
					continue;
				$pagePath = md5($name);

				if(empty($page)) {
					$file = $current;
					$page = $pagePath;
					break;
				}
				if($pagePath == $page)
					$file = $current;
			}
		}

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
		 * Render various sections of the document
		 */
		$this->renderNavFirst($output, $bundle, $book, $page);
		$this->renderNavSecond($output, $bundle, $book, $page);
		$this->renderNavThird($output, $bundle, $book, $page);
		$this->render($output, 'title', $this->title);
		$this->render($output, 'class', str_replace(' ', '-', strtolower($this->title)));
		$this->render($output, 'documentation', $this->documentationHTML);
		$this->render($output, 'bundle', $bundle);
		$this->render($output, 'book', $book);
		$this->render($output, 'page', $page);

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
			'books' => array()
		);

		/**
		 * Record Bundles
		 */
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

		/**
		 * Record Books
		 */
		foreach(glob(e\site . '/documentation/*', GLOB_ONLYDIR) as $section) {
			$name = basename($section);
			$path = md5($name);
			$this->files['books'][$path] = $section;
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
	private function renderNavFirst(&$output, $bundle, $book, $page) {
		$html = '';
		if(count($this->files['bundles']))
			$html .= '<a class="'.(empty($bundle) ? '' : 'selected').'" href="'.$this->root.'">Bundles</a>';
		foreach($this->files['books'] as $path => $section) {
			$name = basename($section);
			$html .= '<a class="'.($book == $path ? 'selected' : '').'" href="'.$this->root.$path.'">'.$name.'</a>';
		}
		$this->render($output, 'nav-first', $html);
	}

	/**
	 * Render second-level navigation
	 * @author Nate Ferrero
	 */
	private function renderNavSecond(&$output, $bundle, $book, $page) {
		$html = '';
		if(!empty($bundle)) {
			foreach($this->files['bundles'] as $name => $files)
				$html .= '<a class="'.($bundle == $name ? 'selected' : '').'" href="'.$this->root.$name.'">'.ucfirst($name).'</a>';
		}
		if(!empty($book)) {
			$dir = $this->files['books'][$book];
			$file = $dir . '/-description.md';
			if(is_file($file))
				$html = e::$markdown->file($file);
		}
		$this->render($output, 'nav-second', $html);
	}

	/**
	 * Render third-level navigation
	 * @author Nate Ferrero
	 */
	private function renderNavThird(&$output, $bundle, $book, $page) {
		$html = '';
		if(!empty($bundle)) {
			foreach($this->files['bundles'][$bundle] as $name => $path)
				$html .= '<a class="'.($page == $name ? 'selected' : '').'" href="'.$this->root.$path.'">'.ucfirst($name).'</a>';
		}
		if(!empty($book)) {
			$dir = $this->files['books'][$book];
			foreach(glob($dir . '/*.md') as $name) {
				$name = pathinfo($name, PATHINFO_FILENAME);
				if($name[0] == '-')
					continue;
				$path = md5($name);
				$html .= '<a class="'.($page == $path ? 'selected' : '').'" href="'.$this->root.$book.'/'.$path.'">'.ucfirst($name).'</a>';
			}
		}
		$this->render($output, 'nav-third', $html);

	}

	/**
	 * Handle Static Resources
	 * @author Nate Ferrero
	 */
	private function staticResource($bundle, $book, $path) {
		$path = implode('/', $path);
		if(!empty($bundle))
			$file = stack::bundleLocations($bundle) . '/documentation/static/' . $path;
		if(!empty($book))
			$file = $this->files['books'][$book] . '/static/' . $path;

		if(!is_file($file))
			throw new Exception("Static documentation file `$file` does not exist");
		
		$mime = 'application/octet-stream';
		switch(pathinfo($file, PATHINFO_EXTENSION)) {
			case 'png':
				$mime = 'image/png';
				break;
			case 'jpeg': case 'jpg':
				$mime = 'image/jpeg';
				break;
		}

		e\disable_trace();
		header('Content-Type: ' . $mime);
		header('Content-Length: ' . filesize($file));
		readfile($file);
		e\complete();
	}
}