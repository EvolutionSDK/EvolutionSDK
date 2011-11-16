<?php

namespace Bundles\SQL;
use Exception;
use PDOException;
use PDO;
use e;

/**
 * Establish an SQL session with the database server.
 *
 * @package default
 * @author David Boskovic
 */
class Connection {
	
	/**
	 *  Connection Instance
	 */
	private $connection;
	
	/**
	 * e::sql() slug
	 */
	public $slug;
	
	/**
	 * History of queries for this session to add to log on system shutdown.
	 *
	 * @author David Boskovic
	 */
	public static $history;
	
	
	/**
	 * Time it takes to process the queries
	 * and Query history
	 */
	public static $time;
	
	/**
	 * Create the connection.
	 */
	public function __construct($url = false, $slug = false) {
		$this->slug = $slug;
		$access = $this->_parse_access_url($url, 'dsn');
		try {
			$this->connection = new PDO($access['dsn'], $access['user'], $access['password']);
			$this->query("SHOW TABLES");
		} catch(PDOException $e) {
			throw new ConnectionException("Could not connect to database `$slug`", 0, $e);
		}
	
	}
	
	/**
	 * Checks TimeSync and Potentially Fixes It
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function checkTimeSync() {
		static $checked = false;
		extract($this->query("SELECT NOW() as `mysqltime`;")->row());
		$phptime = date("Y-m-d g:i:s");
		$diff = strtotime($mysqltime) - strtotime($phptime);
		$diff_hours = $diff / 3600;
		if(abs($diff) > 10) {
			if(e::environment()->getVar('sql.set_timezone') && !$checked) $this->query("SET time_zone='+00:00';");
			else throw new Exception("<strong>MySQL Time</strong> is `$diff_hours hours` off from <strong>PHP Time</strong>.<br /><br /><strong>MySQL Time</strong> is `$mysqltime`. <strong>PHP Time</strong> is `$phptime`.<br /><br /><strong>Fix #1:</strong> Set `default-time-zone = '+00:00'` in `my.cnf` under `[mysqld]`<br /><br /><strong>Fix #2:</strong> Set `sql.set_timezone` in your environment file to `1`");
			
			$checked = true;
			$this->checkTimeSync();
		}
	}
	
	/**
	 * Architectual class
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function architect($table, $config) {
		return new Architect($this, $table, $config);
	}
	
	public function lastInsertId() {
		return $this->connection->lastInsertId();
	}
	
	/**
	 * Run a SQL query
	 */
	public function query($sql, $vsprintf = false) {
	
		/**
		 * Trace
		 */
		e\trace_enter('SQL Query', $sql, is_array($vsprintf) ? $vsprintf : array(), 7);
	
		/**
		 * Sprint the string with either a value or an array. if there is not an array of queries
		 */
		if(!is_array($sql)) {
			if(is_array($vsprintf)) $sql = vsprintf($sql, $vsprintf);
			else if($vsprintf !== false) $sql = vsprintf($sql, $vsprintf);
		}
		
		/**
		 * Start the timer
		 */
		$time = microtime(true);
				
		/**
		 * Run Query
		 */
		$result = $this->connection->prepare($sql);
		$result->execute();
				
		/**
		 * Throw PDOException if an error exists
		 */
		$errorInfo = $result->errorInfo();
		if($errorInfo[2] !== NULL) throw new \PDOException($errorInfo[2]);
		
		/**
		 * Stop the timer and return how long the query took
		 */
		$time = (microtime(true) - $time) * 1000;
		
		//e\trace('Query Time: ' . $time);
		
		/**
		 * Trace
		 */
		e\trace_exit();
		
		/**
		 * Record total query processing time and history
		 */
		self::$time += $time;
		self::$history[] = array('sql' => $sql, 'ms' => round($time,3), 'time' => date("m/d/Y h:i:s a"));
			
		/**
		 * Return the database result object
		 */
		return new Result($this, $result);
	}
	
	/**
	 * Parse a string that looks like mysql://username[:password]@hostname[:3306]/database
	 */
	private function _parse_access_url($url, $return_format = 'dsn') {
		$driver = substr($url, 0, strpos($url,'://')); $rest = substr($url, strlen($driver)+3);
		$database = substr($rest, strpos($rest,'/')+1); $rest = substr($rest, 0, -(strlen($database)+1));
		$rexp = explode('@', $rest);
		if(count($rexp) < 2) $rexp[] = '';
		list($unparsed_access, $unparsed_host) = $rexp;
		$access = explode(':', $unparsed_access);
		$user = array_shift($access);
		$password = array_shift($access);
		$hostconf = explode(':', $unparsed_host);
		$host = array_shift($hostconf);
		$port = array_shift($hostconf);
		
		// get the hso
		switch($return_format) {
			case 'dsn':
				if($driver == 'mysql') $dsn = "mysql:host=$host;dbname=$database;";
				if($driver == 'sqlite') $dsn = "sqlite:$host;";
				if(!isset($dsn))
					throw new Exception("Unknown database driver `$driver`");
				$dsn = array('dsn' => $dsn, 'user' => $user, 'password' => $password);
			break;
			default:
				$dsn = array('hostname' => $host, 'port' => $port, 'password' => $password, 'user' => $user);
			break;
		}
		if(!isset($dsn))
			throw new Exception("Invalid database access string format");
		return $dsn;
	}
	
	
	/**
	 * Insert a row into a database
	 *
	 * @param string $table 
	 * @param string $array 
	 * @param string $vsprintf 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function insert($table, $array, $vsprintf = false) {
		$update = $this->_fragment($array);
		return $this->query("INSERT INTO `$table` SET $update;", $vsprintf);
	}

	/**
	 * Select row(s) from a database
	 *
	 * @param string $table 
	 * @param string/array $conditions 
	 * @param string $vsprintf 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function select($table, $conditions = '', $vsprintf = false) {
		if(is_array($conditions)) $conditions = 'WHERE ' . $this->_fragment($conditions, ' AND ');
		return $this->query("SELECT * FROM `$table` $conditions;", $vsprintf);
	}

	/**
	 * Update row(s) in a database
	 *
	 * @param string $table 
	 * @param string $array 
	 * @param string $conditions 
	 * @param string $vsprintf 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function update($table, $array, $conditions, $vsprintf = false) {
		$update = $this->_fragment($array);
		return $this->query("UPDATE `$table` SET $update $conditions;", $vsprintf);
	}

	/**
	 * Delete row(s) in a database
	 *
	 * @param string $table 
	 * @param string $conditions 
	 * @param string $vsprintf 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function delete($table, $conditions, $vsprintf = false) {
		if(is_array($conditions)) $conditions = 'WHERE ' . $this->_fragment($conditions, ' AND ');
		return $this->query("DELETE FROM `$table` $conditions;", $vsprintf);
	}

	/**
	 * Select a row by ID from a database
	 *
	 * @param string $table 
	 * @param string $id 
	 * @param string $vsprintf 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function select_by_id($table, $id, $vsprintf = false) {
		return $this->select($table, "WHERE `id` = '$id'", $vsprintf);
	}

	/**
	 * Update a row by ID in a database
	 *
	 * @param string $table 
	 * @param string $array 
	 * @param string $id 
	 * @param string $vsprintf 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function update_by_id($table, $array, $id, $vsprintf = false) {
		return $this->update($table, $array, "WHERE `id` = '$id'", $vsprintf);
	}
	
	public function replace($table, $array) {
		$insertfragment = $this->_fragment($array);
		return $this->query("REPLACE INTO $table SET $insertfragment");
	}
	/**
	 * Delete a row by ID in a database
	 *
	 * @param string $table 
	 * @param string $id 
	 * @param string $vsprintf 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function delete_by_id($table, $id, $vsprintf = false) {
		return $this->delete($table, "WHERE `id` = '$id'", $vsprintf);
	}

	/**
	 * Get the columns for a table in a database
	 *
	 * @param string $table 
	 * @param string $as_keys 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function get_fields($table, $as_keys = false) {
		$cols = $this->query("SHOW COLUMNS FROM `$table`;");
		$fields = array();
		
		if($cols->count() > 0) {
			$array = $cols->all();
			foreach($array as $col) {
				if($as_keys) $fields[$col['Field']] = NULL;
				else $fields[$col['Field']] = $col;
   			}
			return $fields;
		}
		else return false;
	}
	
	/**
	 * Return a model of the database table
	 *
	 * @param string $table 
	 * @param string $id 
	 * @param string $set_id 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function model($table, $id = false, $set_id = false) {
		list($bundle, $table) = explode('.', $table);
		return e::$bundle()->$table($id);
	}
	
	/**
	 * Convert an array of values to SQL insert values
	 *
	 * @param string $array 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	private function _fragment($array, $implode = ', ') {
		$return = array();
		foreach($array as $key=>$val) {
			$val = $this->_string_escape($val);
			$return[] = "`$key` = '$val'";
		}
		return implode($implode, $return);	
	}
	
	/**
	 * Escape strings but only once
	 *
	 * @param string $str 
	 * @return void
	 */
	private function _string_escape($str) { 
		if(is_numeric($str)) return $str;
	   $len=strlen($str); 
	    $escapeCount=0; 
	    $targetString=''; 
	    for($offset=0;$offset<$len;$offset++) { 
	        switch($c=$str{$offset}) { 
	            case "'": 
	                    if($escapeCount % 2 == 0) $targetString.="\\"; 
	                    $escapeCount=0; 
	                    $targetString.=$c; 
	                    break; 
	            case '"': 
	                    if($escapeCount % 2 == 0) $targetString.="\\"; 
	                    $escapeCount=0; 
	                    $targetString.=$c; 
	                    break; 
	            case '\\': 
	                    $escapeCount++; 
	                    $targetString.=$c; 
	                    break; 
	            default: 
	                    $escapeCount=0; 
	                    $targetString.=$c; 
	        } 
	    } 
	    return $targetString; 
	}
	
	/**
	 * Runs a transaction
	 * Makes sure the queries will work if not it rollback any changes made within this transaction.
	 *
	 * @param string $sql 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function transaction($sql = array()) {
		try {
			$dbh->beginTransaction();
			foreach($sql as $query) {
				$dbh->exec($sql);
			}
			$dbh->commit();
		}
		catch(PDOException $e) {
			$dbh->rollBack();
		}
	}
	
}

// Connection Exception
class ConnectionException extends Exception {}
