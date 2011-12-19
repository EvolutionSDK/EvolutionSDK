<?php

namespace Bundles\Jolt;
use stdClass as Object;
use Exception;
use stack;
use e;

/**
 * Jolt Bundle
 * 
 * Simple PHP variable-based template engine
 *
 * @author Nate Ferrero
 */
class Bundle {
	
	private $templates = array();
	
	public function _on_portal_route($path, $dir) {
		$this->route(implode('/', $path), $dir);
	}
	
	public function _on_router_route($path) {
		$this->route(implode('/', $path), stack::$site);
	}
	
	public function route($path, $dir) {
		if($path === '')
			$path = 'index';
		foreach(array("$dir/jolt/$path.php", "$dir/jolt/$path/index.php") as $file) {
			if(is_file($file)) {
				echo $this->render("$dir/jolt", $file, '/'.$path);
				e\complete();
			}
		}
	}
		
	public function resource() {
		static $__resources = array();
	
		$args = func_get_args();
		$map = array_shift($args);
	
		if(!is_string($map) || strlen($map) < 1)
			throw new Exception("Invalid resource map");
	
		if(isset($__resources[$map]))
			return $__resources[$map];
	
		$__file = str_replace('.', '/', $map);
		$__file = stack::$site . "/--resources/$__file.php";
	
		if(!is_file($__file))
			throw new Exception("Resource `$map` file `$__file` does not exist");
	
		require_once($__file);
	
		if(!isset($resource))
			throw new Exception("Resource at `$__file` does not define `\$resource`");
		
		$__resources[$map] = $resource;
		return $__resources[$map];
	}
	
	public function template($template) {
		return $this->render($template);
	}
	
	/**
	 * Render a jolt page
	 */
	public function render($root, $file, $url) {
		e\trace('Jolt', "Rendering template `$file`");
	
		/**
		 * Specify system defaults
		 */
		$jolt = new Object;
		$jolt->template = 'html';
		$jolt->template_group = 'default';
		$jolt->url = $url;
		$jolt->root = $root;
		$jolt->file = $file;
		unset($root);
		unset($file);
		unset($url);
	
		/**
		 * Load user defaults
		 */
		$jolt->prefix = $jolt->root . '/--templates/' . $jolt->template_group . '/--prefix.php';
		if(is_file($jolt->prefix))
			require($jolt->prefix);
	
		/**
		 * Check for valid template file and correct if needed
		 */
	
		$jolt->stack = array($jolt->file);
		while(count($jolt->stack) > 0) {
			$jolt->file = array_shift($jolt->stack);
			if(!is_file($jolt->file))
				throw new Exception("Required template `$jolt->file` not found while rendering `$jolt->url`");
			
			/**
			 * Capture template output
			 */
			ob_start();
			require $jolt->file;
			$jolt->buffer = ob_get_clean();
			
			/**
			 * Loop through defined vars
			 */
			foreach(array_keys(get_defined_vars()) as $_____variable_____) {
				$_____variable_____ = strtolower($_____variable_____);
				if(strpos($jolt->buffer, '{'.$_____variable_____) === false)
					continue;
				$jolt->regex = "/\{$_____variable_____([a-z0-9_.,\\s$-]*)\}/";
				preg_match_all($jolt->regex, $jolt->buffer, $jolt->matches);
				foreach(array_unique($jolt->matches[1]) as $jolt->map) {
					/**
					 * Reset for each use
					 */
					$jolt->insert = null;
			
					/**
					 * Use the variable
					 */
					$jolt->value = $$_____variable_____;
					if($jolt->map === '')
						$jolt->insert =& $jolt->value;
				
					/**
					 * Dive into the variable
					 */
					else if(substr($jolt->map, 0, 1) === '.') {
						$jolt->insert = &$jolt->value;
						/**
						 * Null values will not be inserted
						 */
						if(is_null($jolt->insert))
							break;
				
						/**
						 * Get segments of accessor map
						 */
						$jolt->segments = explode('.', $jolt->map);
						array_shift($jolt->segments);
						foreach($jolt->segments as $jolt->segment) {
					
							/**
							 * Handle dynamic segments
							 */
							if(substr($jolt->segment, 0, 1) === '$') {
								$jolt->segment = substr($jolt->segment, 1);
								$jolt->segment = $$jolt->segment;
							}
					
							/**
							 * Handle String access
							 */
							if(is_string($jolt->insert)) {
								$jolt->subs = explode(',', $jolt->segment);
								$jolt->temp = '';
								foreach($jolt->subs as $jolt->sub) {
									$jolt->sub = explode('-', $jolt->sub);
									if(count($jolt->sub) == 1)
										$jolt->sub[] = $jolt->sub[0];
									$jolt->temp .= substr($jolt->insert, $jolt->sub[0], $jolt->sub[1] - $jolt->sub[0] + 1);
								}
								$jolt->insert = $jolt->temp;
							}
					
							/**
							 * Handle Array access
							 */
							else if(is_array($jolt->insert)) {
								if(isset($jolt->insert[$jolt->segment]))
									$jolt->insert = $jolt->insert[$jolt->segment];
								else
									$jolt->insert = null;
							}
					
							/**
							 * Handle Object access
							 */
							else if(is_object($jolt->insert)) {
								if(isset($jolt->insert->{$jolt->segment}))
									$jolt->insert = $jolt->insert->{$jolt->segment};
								else
									$jolt->insert = null;
							}
						}
					}
				
					/**
					 * Null values are blank
					 */
					if(is_null($jolt->insert))
						$jolt->insert = '';
				
					/**
					 * Convert objects and arrays to string
					 */
					if(!is_string($jolt->insert)) {
						$this->renderVar($jolt->insert);
					}
			
					/**
					 * Replace all instances of this accessor
					 */
					$jolt->replace = '{'.$_____variable_____.$jolt->map.'}';
					$jolt->buffer = str_replace($jolt->replace, $jolt->insert, $jolt->buffer);
				}
			}
		
			/**
			 * Clear the only newly-created variable
			 */
			unset($_____variable_____);
			
			/**
			 * Clear the last iteration of jolt vars
			 */
			unset($jolt->map);
			unset($jolt->value);
			unset($jolt->replace);
			unset($jolt->regex);
			unset($jolt->matches);
			unset($jolt->insert);
					
			/**
			 * Set the current buffer contents as the contents of the parent template and continue
			 */
			$jolt->content = $jolt->buffer;
			unset($jolt->buffer);
		
			/**
			 * Cascade up the template chain if one is set, remember all sub modules are already rendered at this point
			 * Templates can include additional modules easily by specifying the module in the PHP code
			 */
			if(isset($jolt->template)) {
				if(!isset($jolt->template_group))
					throw new Exception("Template set to `$jolt->template`, but `\$jolt->template_group` not defined");
				$jolt->stack[] = $jolt->root . "/--templates/$jolt->template_group/$jolt->template.php";
				unset($jolt->template);
			}

		}
		
		return $jolt->content;
	}
	
	public function renderVar(&$var) {
		if($var instanceof \Traversable || is_array($var)) {
			$out = '';
			foreach($var as $item) {
				$this->renderVar($item);
				$out .= $item;
			}
			$var = $out;
		}
	
		else if(is_object($var)) {
			if(method_exists($var, 'render')) {
				$var = $var->render();
				$this->renderVar($var);
			}
			else
				throw new Exception(get_class($var) . " does not have a `render` method");
		}
	}
}