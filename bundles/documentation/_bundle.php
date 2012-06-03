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
				 * Check for alternate file
				 * @author Nate Ferrero
				 */
				$alt = "$dir/" . ucfirst($page) . ".md";
				if(is_file($alt)) {
					$file = $alt;
					break;
				}

				/**
				 * Get the first file
				 * Switched to DirectorIterator for speed
				 * @author Kelly Becker
				 */
				$done = true;
				foreach(new \DirectoryIterator($dir) as $file) {
					if($file->isDot() || strlen($file) < 1 || pathinfo($file, PATHINFO_EXTENSION) !== 'md')
						continue;

					$file = $file->getPathinfo();

					$page = pathinfo($file, PATHINFO_FILENAME);
					continue;
				}
			}

			/**
			 * Set default page title
			 * @author Nate Ferrero
			 */
			$bundleName = $this->files['bundles'][$bundle]['name'];
			$defaultTitle = ucwords($page);
			$titleSuffix = " &bull; $bundleName";
		}

		/**
		 * Load Documentation Books
		 * @author Nate Ferrero
		 */
		if(!empty($book)) {
			$dir = $this->files['books'][$book];

			/**
			 * Index if exists
			 */
			if($page == 'index')
				$page = file_exists($dir . '/index.md') ? md5('index') : null;

			/**
			 * Switch to DirectoryIterator over glob for speed
			 * @author Kelly Becker
			 */
			foreach(new \DirectoryIterator($dir) as $current) {
				if($current->isDot() || strlen($current) < 1 || pathinfo($current, PATHINFO_EXTENSION) !== 'md')
					continue;

				/**
				 * Get full path for file
				 */
				$current = $current->getPathinfo();

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

			$defaultTitle = ucwords($name);
			$bookName = $this->files['bookNames'][$book];
			$titleSuffix = " &bull; $bookName";
		}

		if(!file_exists($file))
			$file = __DIR__ . '/documentation/-not-found.md';

		$this->documentationHTML = e::$markdown->file($file);

		/**
		 * Get title
		 */
		$tagname = 'h1';
  		preg_match("/<$tagname ?.*>(.*)<\/$tagname>/", $this->documentationHTML, $matches);
  		$this->title = isset($matches[1]) ? $matches[1] : $defaultTitle;

		/**
		 * Render various sections of the document
		 */
		$this->renderNavFirst($output, $bundle, $book, $page);
		$this->renderNavSecond($output, $bundle, $book, $page);
		$this->renderNavThird($output, $bundle, $book, $page);
		$this->render($output, 'title', $this->title . $titleSuffix);
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
			$name = ucfirst($bundle);

			/**
			 * Get custom bundle name if defined
			 * Switched to DirectoryIterator for Speed
			 * @author Kelly Becker
			 */
			foreach(new \DirectoryIterator($dir) as $file) {
				if($file->isDot() || strlen($file) < 1 || pathinfo($file, PATHINFO_EXTENSION) !== 'name')
					continue;

				$name = pathinfo($file->getPathname(), PATHINFO_FILENAME); break;
			}

			/**
			 * Switched to DirectoryIterator for Speed
			 * @author Kelly Becker
			 */
			if(is_dir($dir.'/documentation')) foreach(new \DirectoryIterator($dir.'/documentation') as $file) {
				if($file->isDot() || strlen($file) < 1 || pathinfo($file, PATHINFO_EXTENSION) !== 'md')
					continue;

				/**
				 * Set file to the whole path + file
				 */
				$file = $file->getPathname();

				if(!isset($this->files['bundles'][$bundle]))
					$this->files['bundles'][$bundle] = array('name' => $name, 'files' => array());
				$filename = pathinfo($file, PATHINFO_FILENAME);
				if($filename[0] !== '-')
					$this->files['bundles'][$bundle]['files'][$filename] = "$bundle/$filename";
			}
		}

		/**
		 * Record Books
		 */
		if(is_dir(e\site.'/documentation')) foreach(new \DirectoryIterator(e\site.'/documentation') as $section) {
			if($section->isDot() || strlen($section) < 1 || !$section->isDir())
				continue;

			/**
			 * Set file to the whole path + dir
			 */
			$section = $section->getPathname();

			$name = basename($section);
			$path = md5($name);
			$this->files['books'][$path] = $section;
			$this->files['bookNames'][$path] = $name;
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
			$html .= '<a class="'.(empty($bundle) ? '' : 'selected').'" href="'.$this->root.'">Bundles</a> ';
		foreach($this->files['books'] as $path => $section) {
			$name = basename($section);
			$html .= '<a class="'.($book == $path ? 'selected' : '').'" href="'.$this->root.$path.'">'.$name.'</a> ';
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
			foreach($this->files['bundles'] as $name => $data)
				$html .= '<a class="'.($bundle == $name ? 'selected' : '').'" href="'.$this->root.$name.'">'.$data['name'].'</a> ';
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
			foreach($this->files['bundles'][$bundle]['files'] as $name => $path)
				$html .= '<a class="'.($page == $name ? 'selected' : '').'" href="'.$this->root.$path.'">'.ucfirst($name).'</a> ';
		}
		if(!empty($book)) {
			$dir = $this->files['books'][$book];

			/**
			 * Switch to use DirectoryIterator for speed
			 * @author Kelly Becker
			 */
			foreach(new \DirectoryIterator($dir) as $name) {
				if($name->isDot() || strlen($name) < 1 || pathinfo($name, PATHINFO_EXTENSION) !== 'md')
					continue;

				/**
				 * Reset to be the full path + file
				 */
				$name = $name->getPathname();

				$name = pathinfo($name, PATHINFO_FILENAME);
				if($name[0] == '-')
					continue;
				$path = md5($name);
				$html .= '<a class="'.($page == $path ? 'selected' : '').'" href="'.$this->root.$book.'/'.$path.'">'.ucfirst($name).'</a> ';
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