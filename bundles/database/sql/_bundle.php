<?php

namespace Bundles\SQL;
use Exception;
use PDOException;
use e;

/**
 * SQL Exceptions
 */
class NoMatchException extends Exception { }
class InvalidRequestException extends Exception { }

/**
 * SQL Bundle
 */
class Bundle {
	
	public static $db_structure = array();
	public static $changed = false;
	
	private $connections = array();
	
	public function __initBundle() {
		$enabled = e::$environment->requireVar('SQL.Enabled', "yes | no");
		
		/**
		 * Build Relationships
		 */
		if($enabled === true || $enabled === 'yes') $this->build_relationships();
		
		/**
		 * Build Architecture
		 */
		if($enabled === true || $enabled === 'yes')
			if(self::$changed == true || e::sql()->query("SHOW TABLES")->count() == 0 || isset($_GET['_build_sql'])) $this->build_architecture();
	}
	
	public function __getBundle($method = false) {
		//if(!isset($this->connections['default']))
			// if it doesnt have a connection add the default connection
		// return useConnection on the default bundle
		return $this->useConnection('default');
	}
	
	/**
	 * Run a query e::sql("query string");
	 *
	 * @param string $query 
	 * @return void
	 * @author David Boskovic
	 */
	public function __callBundle($connection = 'default') {
		
		return $this->useConnection($connection);
		
	}
	
	/**
	 * Get the database architect for a specific connection, leave false for current
	 *
	 * @param string $connection 
	 * @return void
	 * @author David Boskovic
	 */
	public function architect($connection = false) {
		
	}
	
	/**
	 * Return a query builder object on an established connection.
	 *
	 * @param string $slug 
	 * @return void
	 * @author David Boskovic
	 */
	public function useConnection($slug='default') {
		if(isset($this->connections[$slug]) && $this->connections[$slug] instanceof Connection)
			return $this->connections[$slug];
		
		// Check that slug is a string
		if(!is_string($slug))
			throw new Exception("Database connection slug must be a string when
				calling `e::sql(<i>slug</i>)` or `e::sql()->useConnection(<i>slug</i>)`");
		
		// Load up the database connection from environment
		$default = e::$environment->requireVar("sql.connection.$slug", 
			'service://username[:password]@hostname[:port]/database');
		
		// Try to make the connection
		try {
			$conn = $this->addConnection($default, $slug);
		} catch(Exception $e) {
			e::$environment->invalidVar("sql.connection.$slug", $e);
		}
		
		$conn->checkTimeSync();
		
		return $conn;
	}
	
	/**
	 * Create a new mysql server connection.
	 *
	 * @param string $slug 
	 * @param string $info 
	 * @return void
	 * @author David Boskovic
	 */
	public function addConnection($info, $slug = 'default') {
		$this->connections[$slug] = new Connection($info, $slug);
		return $this->connections[$slug];
	}
	
	/**
	 * Build hasOne and hasMany Relationships
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	private function build_relationships() {
		if(empty(self::$db_structure)) return false;
		
		foreach(self::$db_structure as $table=>$config) {
			/**
			 * Create Many to One connection table and columns
			 */
			if(isset($config['hasOne'])) foreach($config['hasOne'] as $tbl) {
				self::$db_structure[$table]['fields']['$'.$tbl.'_id'] = 'number';
			}

			/**
			 * Create Many to One connection table and columns
			 */
			if(isset($config['hasMany'])) foreach($config['hasMany'] as $tbl) {
				self::$db_structure[$tbl]['fields']['$'.$table.'_id'] = 'number';
				self::$db_structure[$tbl]['hasOne'][] = $table;
			}
			
			$config = array();
		}
	}
	
	/**
	 * Load the Conglomerate of DB Structure Info and Run it through architect
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	private function build_architecture() {
		if(empty(self::$db_structure)) return false;
				
		$tables = array();
		foreach(self::$db_structure as $table=>$struct) {
			e::sql()->architect($table, $struct);
			$tables[] = $table;
		}
		
		$exists = e::sql()->query("SHOW TABLES")->all();
		foreach($exists as $table) {
			$table = end($table);
			
			if(strpos($table, '$') !== false) continue;
			if(in_array($table, $tables)) continue;
			if(strpos($table, '.') === false) continue;
			
			e::sql()->query("DROP TABLE `$table`");
		}
	}

}
