<?php
namespace bundles\SQL;
use PDOStatement;
use PDO;
use e;

class Result {

	/**
	 * Store a copy of the PDOInstance and Connection
	 */
	public $result;
	public $connection;
	
	/**
	 * Set the class variables
	 *
	 * @param string $result 
	 * @param string $query 
	 * @param string $table 
	 * @param string $dbh 
	 * @author Kelly Lauren Summer Becker
	 */
	public function __construct(Connection $connection, PDOStatement $result) {
		$this->result = $result;
		$this->connection = $connection;
	}
	
	/**
	 * If there is a DB result destroy the DB session so another call may be made
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function __destruct() {
		$this->destroy();
	}
	public function destroy() {
		if($this->result) $this->result->closeCursor();
	}
	
	/**
	 * Pull the last insert ID
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function insertId() {
		return $this->connection->lastInsertId();
	}
	
	/**
	 * Return an array of all the rows affected by the query
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function all($type = 'assoc') {
		if($type == 'assoc') $grab = PDO::FETCH_ASSOC;
		else if($type == 'num') $grab = PDO::FETCH_NUM;
		else if($type == 'object') $grab = PDO::FETCH_OBJ;
		else $grab = PDO::FETCH_ASSOC;

		return $this->result->fetchAll($grab);
	}
	
	/**
	 * Pull an array of a row affected by the query one by one
	 *
	 * @param string $type 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function row($type = 'assoc') {
		if($type == 'assoc') $grab = PDO::FETCH_ASSOC;
		else if($type == 'num') $grab = PDO::FETCH_NUM;
		else if($type == 'object') $grab = PDO::FETCH_OBJ;
		else $grab = PDO::FETCH_ASSOC;
		
		return $this->result->fetch($grab);
	}
	
	public function lists() {
		$query = $this->result->queryString;
		preg_match('/FROM `?([\w.$ ]+)`?/', $query, $tables);
		var_dump($tables);
		list($bundle, $model) = explode('.', $tables[1]);
		$list = $model.'_list';
		return e::$bundle()->$list();
	}
	
	public function model() {
		$row = $this->row();
		$query = $this->result->queryString;
		preg_match('/FROM `?([\w.]+)`?/', $query, $tables);
		list($bundle, $model) = explode('.', $tables[1]);
		return e::$bundle()->$model($row['id']);
	}
	
	/**
	 * Count all the affected rows
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function count() {
		return $this->result->rowCount();
	}
	
}