<?php

namespace Bundles\LHTML;
use Exception;

class Scope {
	
	public $parent;
	
	private $source_as = false;
	private $source_data = false;
	private $source_pointer = false;
	private $source_count = false;
	
	public $timers = array();
	
	public static $hooks = array();
	
	public static function addHook($name, &$obj) {
		if(substr($name, 0, 1) !== ':') throw new Exception('You must prefix your LHTML hook with a colon! Error in hook $name');
		self::$hooks[$name] =& $obj;
	}
	
	public function __construct($parent = false) {
		$this->timers['scope->map'] = 0;
		$this->timers['scope->get'] = 0;
		
		/**
		 * Set the parent scope
		 */
		$this->parent = $parent;
		
		/**
		 * Set $_GET and $_POST to hooks
		 */
		self::$hooks[':get'] =& $_GET;
		self::$hooks[':post'] =& $_POST;
	}
	
	public function get($var_map) {
		$tt = microtime(true);
		
		if(is_string($var_map) AND strpos($var_map, '%') === 0) $var_map = substr($var_map, 1);
		
		$allmap = is_string($var_map) ? $this->parse($var_map) : $var_map;
		
		$filters = $allmap['filters'];
		$map = $allmap['vars'];
		
		$flag_first = false;
		
		if(substr($map[0], 0, 1) == ':') foreach(self::$hooks as $hmap=>$hobj) {
			if($map[0] !== $hmap) continue;
			$source = $hobj;
			$flag_first = 1;
		}
		
		if(!$flag_first) {
			if(is_string($map[0]) && strpos($map[0],"'") === 0) return trim($map[0],"'");
			else if(is_string($map[0]) && is_numeric($map[0])) return $map[0];
			
			/**
			 * Return Source Array
			 *
			 * else if(is_string($map[0]) && isset($this->source_data[$map[0]]) && !is_object($this->source_data[$map[0]])) 
			 *	return $this->data[$map[0]][$this->source_pointer]->{$map[1]};
			 */
			
			/**
			 * Return Object
			 */	
			else if(is_string($map[0]) && isset($this->source_data[$map[0]]) && is_object($this->source_data[$map[0]])) {
				$i=0; foreach($this->source_data[$map[0]] as $source) {
					if($i === $this->source_pointer) break;
					unset($source);
					$i++;
				}
				
				if(isset($source)) $flag_first = 1;
			}
			
			/**
			 * Return Array
			 */
			else if(is_string($map[0]) && isset($this->source_data[$map[0]]) && is_array($this->source_data[$map[0]])) {
				$i=0; foreach($this->source_data[$map[0]] as $source) {
					if($i === $this->source_pointer) break;
					unset($source);
					$i++;
				}
				
				if(isset($source)) $flag_first = 1;
			}
			
			else if(is_string($map[0]) && !isset($this->source_data[$map[0]]) && $this->parent) return $this->parent->get($var_map);
			
			else throw new \Exception("IXML Scope no function was called when calling {$var_map}");
		}
		
		foreach($map as $i=>$var) {
			if($map[0] == ':get' && $map[1] == 'test') echo(' | i:'.$i.' | flag: '.$flag_first);
			if($flag_first && $i < $flag_first) continue;
			if(!$source) return null;
			
			if(is_array($var) && is_object($source)) {
				if(method_exists($source, $var['func'])) $source = call_user_func_array(array($source, $var['func']), $var['args']);
				else if(method_exists($source, '__call')) $source = call_user_func_array(array($source, $var['func']), $var['args']);
 			} 

			else if(is_object($source)) {
				if(isset($source->$var)) $source = $source->$var;
				else if(!is_null($var) && method_exists($source, $var)) $source = $source->$var();
				else if(!is_null($var) && method_exists($source, '__call')) $source = $source->$var();
			}
			
			else if(is_array($source)) {
				if($this->pointer !== false && $map[0] == $this->iterator && !$iterated) {
					$iterated = true;
					$source = is_object($source[$this->iterator]) ? $source[$this->iterator]->_scope_by_pos($this->pointer) : $source[$this->iterator][$this->pointer];
				}
				else $source = @$source[$var];
			}
			else $source = false;
		}
		
		/**
		 * Perform Filters
		 */
		if(is_array($filters)) foreach($filters as $filter) {
			if(is_array($filter)); //
			else; //
		}
		
		$this->timers['scope->get'] += microtime(true) - $tt;
		
		return $source;
	}
	
	public function parse($var) {
		$tt = microtime(true);
		
		$extract_vars = $this->extract_vars($var);
		if(!empty($extract_vars)) foreach($extract_vars as $rv) {
			$val = (string) $this->get($rv);
			$var = str_replace('{'.$rv.'}', $val, $var);
		}
		
		$extract_subvars = $this->extract_subvars($var);
		if(!empty($extract_subvars)) foreach($extract_subvars as $rv) {
			$val = (string) $this->get($rv);
			$var = str_replace('['.$rv.']', $val, $var);
		}
		
		if(strpos($var, ' ? ') !== false) {
			list($cond, $result) = explode(' ? ', $var);
			$else = false;
			
			if(strpos($result, ' : ') !== false) list($result, $else) = explode(' : ', $result);
			
			if(strpos($cond, ' + ') !== false) {
				list($cond1, $cond2) = explode(' + ', $cond);
				$var = $cond1 + $cond2;
			}
			else if(strpos($cond, ' - ') !== false) {
				list($cond1, $cond2) = explode(' - ', $cond);
				$var = $cond1 - $cond2;
			}
			else if(strpos($cond, ' / ') !== false) {
				list($cond1, $cond2) = explode(' / ', $cond);
				$var = $cond1 / $cond2;
			}
			else if(strpos($cond, ' * ') !== false) {
				list($cond1, $cond2) = explode(' * ', $cond);
				$var = $cond1 * $cond2;
			}
			else if(strpos($cond, ' == ') !== false) {
				list($cond, $compare) = explode(' == ', $cond);
				$val = $this->get($cond);
				$cval = $this->get($compare);
				
				if($val == $cval) $var = $result;
				else $var = $else;
			}
			else if(strpos($cond, ' != ') !== false) {
				list($cond, $compare) = explode(' != ', $cond);
				$val = $this->get($cond);
				$cval = $this->get($cond);
				
				if($val != $cval) $var = $result;
				else $var = $else;
			}
			else if(strpos($cond, ' () ') !== false) {
				list($cond, $compare) = explode(' () ', $cond);
				$val = $this->get($cond);
				$cval = explode(',', $this->get($cond));
				$retval = false;
				foreach($cval as $tmp) if($val == trim($tmp)) $retval = true;
				if($retval) $var = $result;
				else $var = $else;
			}
			else if(strpos($cond, ' () ') !== false) {
				list($cond, $compare) = explode(' () ', $cond);
				$val = $this->get($cond);
				$cval = explode(',', $this->get($cond));
				$retval = true;
				foreach($cval as $tmp) if($val == trim($tmp)) $retval = false;
				if($retval) $var = $result;
				else $var = $else;
			}
			else {
				$val = $this->get($cond);
				$val = is_string($val) ? trim($val) : $val;
				if($val) $var = $result;
				else $var = $else;
			}
		}
		
		$ef = $this->extract_funcs($var);
		if(is_array($ef)) foreach($ef as $k=>$f) {
			$ef[$k]['key'] = '%F'.$k;
			$var = str_replace($f['string'], '%F'.$k, $var);
		}
		
		if(strpos($var, '|') !== false) {
			$a = explode('|', $var);
			$var = (strlen($a[0]) > 0 ? $a[0] :false);
			$filters = array_slice($a, 1);
		}
		else $filters = array();
		
		$vars = explode('.', $var);
		foreach($vars as &$v) {
			if(substr($v, 0, 2) == '%F') $v = $ef[substr($v, 2)];
		}
		
		if(is_array($filters)) foreach($filters as $filter) {
			if(substr($filter, 0, 2) == '%F') $filter = $ef[substr($filter, 2)];
		}
		
		$this->timers['scope->map'] += microtime(true) - $tt;
		
		return array('vars' => $vars, 'filters' => $filters);
	}
	
	/**
	 * Get parsed variable
	 */
	public function __get($v) {
		return $this->get($v);
	}
	
	/**
	 * Load a literal variable into the scope
	 */
	public function __set($var, $value) {
		$this->source_data[$var] = $value;
	}
	
	/**
	 * Load source into the scope
	 */
	public function source($source, $as = false) {
		if(count($source) < 1) throw new \Exception("You must provide a foreachable row or object with at least one key. When calling `\bundles\lhtml\scope::source()`");
		
		/**
		 * Set the source as
		 */
		if(!$as) $as = 'i';
		$this->source_as = $as;
				
		/**
		 * Load the source into the scope
		 */
		$this->source_data[$this->source_as] = $source;
		
		/**
		 * Set the count of the source
		 */
		if($source instanceof \Bundles\SQL\ListObj) $this->source_count = $source->count(true);
		else $this->source_count = count($source);
		
		/**
		 * Reset the pointer
		 */
		$this->source_pointer = 0;
	}
	
	/**
	 * Reset Iterations
	 */
	public function reset() {
		$this->source_pointer = 0;
		
		return $this;
	}
	
	/**
	 * Next Source
	 */
	public function next() {
		if($this->source_pointer < $this->source_count)
			$this->source_pointer++;
		
		return $this;
	}
	
	/**
	 * Is still in a safe zone
	 */
	public function iteratable() {
		if($this->source_pointer >= 0 && $this->source_pointer <= $this->source_count)
			return true;
		else
			return false;
	}
	
	/**
	 * Back One Source
	 */
	public function back() {
		if($this->source_pointer !== 0)
			$this->source_pointer--;
		
		return $this;
	}
	
	/**
	 * Extract all variables Below Here
	 */
	private function extract_vars($content) {
		
		if(strpos($content, '{') === false) return array();
		// parse out the variables
		preg_match_all(
			"/{([\w:|.\,\(\)\/\-\% \[\]\?'=]+?)}/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = $var[1];
		}
		
		return $vars;
		
	}
	
	private function extract_subvars($content) {
		
		if(strpos($content, '[') === false) return array();
		// parse out the variables
		preg_match_all(
			"/\[([\w:|.\,\(\)\/\-\% \[\]\?'=]+?)\]/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = $var[1];
		}
		
		return $vars;
		
	}
	
	private function extract_funcs($content) {
		if(strpos($content, '(') === false) return array();
		// parse out the variables
		preg_match_all(
			"/([\w]+?)\(([\w:|.\,=@\(\)\/\-\%& ]*?)\)/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = array('func' => $var[1], 'string' => $var[0], 'args' => explode(',', $var[2]));
		}
		
		return $vars;
	}
	
}