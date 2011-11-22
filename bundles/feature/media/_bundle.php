<?php

namespace Bundles\Media;
use ReflectionClass;
use Exception;
use e;

class Bundle extends \Bundles\SQL\SQLBundle {
	/**
	 * Array of stored files and commonly used mim types
	 */
	public static $files;
	public static $mimes;
	
	/**
	 * Directory in which to store files locally
	 */
	public static $file_dir;
	
	/**
	 * Routed Path
	 */
	public $path;
		
	/**
	 * Uploaded Files Errors
	 */
	public static $upload_error = array(
		1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
		2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
		3 => "The uploaded file was only partially uploaded.",
		4 => "No file was uploaded.",
		5 => "Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.",
		6 => "Failed to write file to disk. Introduced in PHP 5.1.0.",
		8 => "A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0."
	);
	
	public function _on_put_file($file, $filename) {
		file_put_contents(self::$file_dir.'/'.$filename, $file);
	}
	
	public function _on_get_file($filename) {
		$file = self::$file_dir.'/'.$filename;
		$ext = explode('.', $filename);
		$ext = array_pop($ext);
		return array(
			'time' => filemtime($file),
			'hash' => md5_file($file),
			'type' => self::$mimes[$ext],
			'size' => filesize($file),
			'url' => "/@media/$filename",
			'loc' => __CLASS__
		);
	}
	
	public function _on_first_use() {
		/**
		 * Find the media dir
		 */
		self::$file_dir = e::$site.'/media';
		
		/**
		 * Make sure the directory exists and is writable
		 */
		if(!is_dir(self::$file_dir)) mkdir(self::$file_dir);
		if(!is_writable(self::$file_dir)) chmod(self::$file_dir, 0777);
		
		/**
		 * Grab the Mime, and popular the Files array
		 */
		self::$files = glob(self::$file_dir.'/*');
		self::$mimes = e\decode_file(__DIR__.'/configure/mime_types.json');
	}
	
	/**
	 * Handle Viewing Images From Local Store
	 */
	public function route($path) {
		$this->path = $path;
		return true;
	}
	
	public function _on_after_framework_loaded() {
		if(is_null($this->path)) return;
		$path = $this->path;
		
		/**
		 * Disable the trace
		 */
		e\disable_trace();
		
		/**
		 * File Name
		 */
		$name = array_shift($path);
		
		/**
		 * Grab the file from the database based on filename
		 */
		$conds = array('filename' => $name);
		$array = e::sql()->select('media.file', $conds)->row();
		$file = $this->getFile($array);
		
		/**
		 * Get File Info
		 */
		$info = $file->get();
		
		/**
		 * If stored else where load it
		 */
		if(strpos($info['url'], '/@media/') !== false) {
			/**
			 * If is a photo output resize and output it
			 */
			if($file->filetype == 'photo'){
				/**
				 * Photo Sizes
				 */
				$x = array_shift($path);
				$y = array_shift($path);
				$file->photo('x'.$x.($y ? 'y'.$y : ''))->show();
			}

			/**
			 * If the file is any other kind of file output it
			 */
			else {
				$ext = explode('.', $file->filename);
				$ext = array_pop($ext);

				header("Content-Type: ".($file->filemime ? $file->filemime : self::$mimes[$ext]));
				readfile(self::$file_dir.'/'.$file->filename);
			}
		}
		
		/**
		 * Read Remote File
		 */
		else {
			if($file->filetype == 'photo') {
				/**
				 * Photo Sizes
				 */
				$x = array_shift($path);
				$y = array_shift($path);
				$this->photo('http://'.$info['url'], 'x'.$x.($y ? 'y'.$y : ''))->show();
			}
			
			$ext = explode('.', $info['url']);
			$ext = array_pop($ext);
			
			header("Content-Type: ".self::$mimes[$ext]);
			readfile($info['url']);
		}
		
		/**
		 * Complete
		 */
		e\Complete();
	}
	
	public function gd() {
		$args = func_get_args();
		$class = new ReflectionClass(__NAMESPACE__.'\\Photo');
		return $class->newInstanceArgs($args);
	}
	
	/**
	 * Resize/Crop Image
	 * 
	 * @param $dim
	 * @return $ptmp
	 * @author Kelly Lauren Summer Becker
	 */
	public function photo($file, $dim) {		
		/**
		 * Reset dimensions
		 */
		$ydim = FALSE;
		$xdim = FALSE;
				
		/**
		 * If both sets of dimensions are present then assign them
		 */
		if($ydim = strstr($dim, 'y')) {
			$xdim = strstr($dim, 'y', true);
			$ydim = preg_replace('/[a-z]/', '', $ydim);
			$xdim = preg_replace('/[a-z]/', '', $xdim);
		} 
		
		/**
		 * If only X is present handle it
		 */
		else $xdim = preg_replace('/[a-z]/', '', $dim);
				
		/**
		 * Create a temporary photo
		 */
		$ptmp = new Photo($file);
		
		/**
		 * Resize using X Dimension
		 */
		if(!$ydim) $ptmp->resize($xdim, 'x');
		
		/**
		 * Resize with both X and Y via crop
		 */
		else {
			/**
			 * Get current image size
			 */
			$current_size = $ptmp->getSize();

			/**
			 * Calculate which size is larger
			 */
			$x_calc = $xdim / $current_size['x'];
			$y_calc = $ydim / $current_size['y'];
			
			/**
			 * Resize the image based on the larger size
			 */
			if($x_calc > $y_calc) $ptmp->resize($xdim, 'x');
			else $ptmp->resize($ydim, 'y');

			/**
			 * Finally! Crop the image
			 */
			$ptmp->crop($xdim,$ydim);
		}
		
		/**
		 * If this was called by a static server
		 */
		return $ptmp;
	}
	
}