<?php

namespace Bundles\Filters;
use Exception;
use e;

class Bundle {
	
	private $filters;
	
	public function __callBundle($filter, $source, $args = array()) {
		if(!is_object($this->filters)) $this->filters = new Filters;
		
		return call_user_func_array(array($this->filters, $filter), array($source, $args));
	}
	
	public function addFilterClass($class) {
		$class = '\\'.$class;
		
		$class = new $class;
		if($class instanceof Filters)
			Filters::$_alternate_filters[] = $class;
	}
	
}

class Filters {
	
	public static $_alternate_filters = array();
	
	/**
	 * Force Calling of Filters to be done Statically
	 *
	 * @param string $function 
	 * @param string $args 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function __call($function, $args) {
		if(method_exists($this, '_'.$function))
			return call_user_func_array(array($this, '_'.$function), $args);
			
		else if(__NAMESPACE__ == '\Bundles\Filters') foreach(self::$_alternate_filters as $class)
			return call_user_func_array(array($class, $function), $args);
	}

	private function _dump($source, $vars = array()) {
		echo "<div class='debug_dump' style='padding: 1em;clear:both;margin: 0;border-bottom: 1px solid #000; overflow:auto;max-height:150px; background: #ffe;'><b>Debug Dump".(isset($vars[0]) ? ' &mdash; '.$vars[0] : '')."</b><br/><pre>".var_export($source,true)."</pre></div>";
		return '';
	}
	
	private function _date($source, $vars = array()) {
		return date($vars[0], strtotime($source));
	}
	
	private function _money($source, $vars = array()) {
		$round = isset($vars[1]) ? $vars[1] : 0;
		switch($vars[0]) {
			case 'separate':
				$source = '<b>$</b>'.number_format((float)$source, (float)$round,'.',',');
				break;
			case 'USD':
			case 'US':
			default:
				if(is_numeric($source))
					$source = '$'.number_format($source, $round,'.',',');
				else if(is_object($source))
					$source = '[Object ' . get_class($source) . ']';
				else
					$source = '$' . $source;
			break;
		}
		if($source == '$0.00') $source = '-.-';
		return $source;
	}
	
	private function _count($source, $vars = array()) {
		return count($source);
	}
	
	private function _toDollars($source, $vars = array()) {
		return $source / 100;
	}
	
	private function _toCents($source, $vars = array()) {
		return $source * 100;
	}
	
	private function _TF($source, $vars = array()) {
		if($source) return 'True';
		else return 'False';
	}
	
}