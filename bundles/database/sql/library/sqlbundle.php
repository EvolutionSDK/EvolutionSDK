<?php

namespace Bundles\SQL;
use Exception;
use e;

class SQLBundle {
	
	public $bundle;
	private $dir;
	public $database = 'default';
	private $initialized = false;
	
	private $local_structure = array();
	
	public function __construct($dir) {
		$this->dir = $dir;
		
		$this->bundle = basename($this->dir);
	}
	
	public function _on_framework_loaded() {
		$enabled = e::environment()->requireVar('SQL.Enabled', "yes | no");
		if($enabled === true || $enabled === 'yes')
			$this->_sql_initialize();
	}
	
	public function _sql_initialize() {
		$this->initialized = true;
		$file = $this->dir.'/configure/sql_structure.yaml';
		
		/**
		 * If File Has Changed
		 */
		if(e::yaml()->is_changed($file)) Bundle::$changed = true;
		
		try {
			$sql = e::yaml()->load($file, true);
		}
		catch(Exception $e) {
			throw new Exception("Error loading SQL configuration for bundle `$this->bundle` from file `$file`", 0, $e);
		}
		
		/**
		 * If a relation is on the same table prefix it with its bundle name
		 */
		foreach($sql as $table=>$relations) {
			if(!is_array($relations)) throw new \Exception("Invalid YAML Config Error-ing in table $table in file $dir/configure/_sql_structure.yaml");
			foreach($relations as $kind=>$values) {
				if($kind == 'fields' || $kind == 'singular' || $kind == 'plural') continue;
				
				foreach($values as $key=>$val) {
					if(strpos($val, '.')) continue;
					
					$values[$key] = $this->bundle.'.'.$val;
				}
				
				$relations[$kind] = $values;
			}
			$sql[$table] = $relations;
		}
				
		/**
		 * Save the DB structure
		 */
		foreach($sql as $table=>$val) Bundle::$db_structure[$this->bundle.'.'.$table] = $val;
		foreach($sql as $table=>$val) $this->local_structure[$table] = $val;
		
	}
	
	/**
	 * Return Models/List If no Extended model/list was declared
	 *
	 * @param string $func 
	 * @param string $args 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function __call($func, $args) {
		if(!$this->initialized)
			throw new Exception("SQL for `".__CLASS__."` was not initialized in system startup. Most likely, the environment variable `SQL.Enabled` is off.");
		
		$search = preg_split('/([A-Z])/', $func, 2, PREG_SPLIT_DELIM_CAPTURE);
		$method = array_shift($search);
		$search = strtolower(implode('', $search));
		
		if(empty($this->local_structure)) return false;
		
		foreach($this->local_structure as $table=>$relations) {
			if($search == $table) {
				$plural = false;
				break;
			}
			else if(isset($relations['singular']) && $relations['singular'] == $search) {
				$plural = false;
				break;
			}
			else if(isset($relations['plural']) && $relations['plural'] == $search) {
				$plural = true;
				break;
			}
			
			unset($relations, $table);
		}
		
		if(!isset($relations) && !isset($table)) throw new NoMatchException("There was no table match when calling `$func(...)` on the `e::$this->bundle()` bundle.");
		switch($method) {
			case 'get':
				if(!$plural) {
					if(isset($args[0])) {
						$class = "\\Bundles\\$this->bundle\\Models\\$table";
						try { $m = new $class($this->database, $relations['singular'], "$this->bundle.$table", $args[0]); }
						catch(e\AutoLoadException $e) {
							$m = new Model($this->database, $relations['singular'], "$this->bundle.$table", $args[0]);
						}
					}
					if(isset($m) && is_object($m) && isset($m->id)) return $m;
					else return false;
				}
				else if($plural) {
					$class = "\\bundles\\$this->bundle\\Lists\\$table";
					try { return new $class("$this->bundle.$table", $this->database); }
					catch(e\AutoLoadException $e) {
						 return new ListObj("$this->bundle.$table", $this->database);
					}
				}
			break;
			case 'new':
				$class = "\\bundles\\$this->bundle\\Models\\$table";
				try { return new $class($this->database, $relations['singular'], "$this->bundle.$table", false); }
				catch(e\AutoLoadException $e) {
					return new Model($this->database, $relations['singular'], "$this->bundle.$table", false);
				}
			default:
				throw new InvalidRequestException("`$method` is not a valid request as `$func(...)` on the `e::$this->bundle()` bundle. valid requests are `new` and `get`.");
			break;
		}
		
		throw new NoMatchException("No method was routed when calling `$func(...)` on the `e::$this->bundle()` bundle.");
		
	}
	
}