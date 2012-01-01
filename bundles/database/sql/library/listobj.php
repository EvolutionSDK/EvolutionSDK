<?php

namespace bundles\SQL;
use bundles\SQL;
use e;

/**
 * @todo Make all paging functions part of the list method
 */

class ListObj implements \Iterator, \Countable {
	
	/**
	 * DB Connection
	 */
	public $_connection = 'default';
	
	/**
	 * Tables
	 */
	public $_table;
	
	/**
	 * Results
	 */
	protected $_result_array;
	protected $_result_model;
	protected $_results;
	protected $position = 0;
	protected $_has_query = false;
	protected $_raw;
	
	/**
	 * Query Conditions
	 */
	protected $_fields_select = '*';
	protected $_tables_select;
	protected $_join = false;
	protected $_query_cond = array();
	protected $_order_cond = array();
	protected $_group_cond = array();
	protected $_distinct_cond = false;
	protected $_custom_query;
	
	/**
	 * Limit Conditions
	 */
	protected $_limit = false;
	protected $_limit_size = false;
	protected $_page_length = 5;
	protected $_on_page = 1;
	
	/**
	 * Count of all items int the result
	 */
	protected $_count = 0;
	protected $_sum = array();
	
	protected $_tb_singular;
	protected $_tb_plural;
	
	/**
	 * List constructor
	 *
	 * @param string $table 
	 * @author Kelly Lauren Summer Becker
	 */
	public function __construct($table = false, $connection = false) {
		if($table) $this->_table = $table;
		if($connection) $this->_connection = $connection;
		
		$get_table = Bundle::$db_structure[$this->_table];
		$this->_tb_singular = $get_table['singular'];
		$this->_tb_plural = $get_table['plural'];
		
		/**
		 * Add default table to tables select
		 */
		$this->_tables_select = "`$this->_table`";
		
		/**
		 * Run any initialization functions
		 */
		$this->initialize();
	}
	
	/**
	 * Placeholder for extendable list object
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	protected function initialize() {
		
	}
	
	/**
	 * Debug the query
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function debug() {
		$query = $this->_query_cond;
		$table = $this->_table;
		
		
		
		// $query $table
		eval(d);
	}
	
	/**
	 * Add a condition to your list query
	 *
	 * @param string $field 
	 * @param string $value 
	 * @param string $table 
	 * @param string $verify 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function condition($field, $value, $table = false, $verify = false) {
		/**
		 * Prepare condition values
		 */
		$signal	= strpos($field, ' ') ? substr($field, strpos($field, ' ') + 1) : '=';
		$field 	= strpos($field, ' ') ? substr($field, 0, strpos($field, ' ')) 	: $field;
		$value 	= strpos($value, ':') === 0 && ctype_alpha(substr($value, 1) == true) ? '`'.substr($value, 1).'`' : $value;
		$value 	= is_null($value) || is_numeric($value) || strpos($value, '`') === 0 ? $value : "'$value'";
		
		/**
		 * If is null make sure we are checking NULL not 'NULL' or '' or 0
		 */
		if(is_null($value)) $value = 'NULL';
		
		/**
		 * Make sure that if we join tables this condition stays on this (or the provided) table
		 */
		if(!$table) $table = $this->_table;
		$field	= strpos($field, '`') === 0 ? $field : "`$table`.`$field`";

		if($verify) return "$field $signal $value";
		else $this->_query_cond[] = "$field $signal $value";
		
		return $this;
	}
	
	/**
	 * Add a Left/Right Join
	 *
	 * @param string $type 
	 * @param string $use 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function join($type = 'LEFT', $use, $cond) {
		$this->_join = " $type JOIN `$use` ON $cond";
		
		return $this;
	}
	
	/**
	 * Many to many Left Join
	 *
	 * @param string $use 
	 * @param string $join 
	 * @param string $id 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function m2m($use, $join, $id) {
		
		if(is_numeric($join)) $cond = "`$this->_table`.`id` = `$use`.`\$id_b`";
		else $cond = "`$this->_table`.`id` = `$use`.`\$".$this->_table."_id`";
		
		$this->join('LEFT', $use, $cond);
		if(is_numeric($join)) $this->condition("`$use`.`\$id_a` =", $id);
		else $this->condition("`$use`.`\$".$join."_id` =", $id);
		
		return $this;
	}
	
	/**
	 * Process Multiple Field Conditions
	 * Use: Comparing multiple fields to a single condition
	 *
	 * @param string $condition 
	 * @param string $fields 
	 * @param string $verify 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function multiple_field_condition($condition, $fields, $verify = false) {
		if(!is_array($fields)) $fields = explode(' ', $fields);
		if(count($fields) == 0) return $this;
		
		$query = '';
		foreach($fields as $field) {
			if(strtoupper($field) == 'OR') $query .= ' OR ';
			else if(strtoupper($field) == 'AND') $query .= ' AND ';
			else $query .= "`$field` $condition";
		}
		
		if($verify) return "($query)";
		else $this->_query_cond[] = "($query)";
		
		return $this;
	}
	
	/**
	 * Add a manually formatted condition to your list query
	 *
	 * @param string $condition 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function manual_condition($condition) {
		$this->_query_cond[] = $condition;
		return $this;
	}
	
	/**
	 * Process an array of conditions
	 *
	 * @param string $array 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function condition_array($array) {
		if(!is_array($array)) return $this;
		
		foreach($array as $col=>$val) {
			$this->condition($col, $val);
		}
		
		return $this;
	}
	
	/**
	 * Create an isolated condition and add it to your list query
	 *
	 * @param string $condition 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function isolated_condition($condition) {
		$this->_query_cond[] = "($condition)";
		return $this;
	}
	
	/**
	 * Searching fields for a specific thing
	 *
	 * @param string $term 
	 * @param string $fields 
	 * @param string $verify 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function multiple_field_search($term, $fields, $verify = false) {
		$term = mysql_real_escape_string($term);
		if(strlen($term) == 0) return $verify ? '' : $this;
		
		$like 	= '`'.implode('` LIKE "%'.$term.'%" OR `', explode(' ', $fields)). '` LIKE "%'.$term.'%"';
		$fields = '`'.implode('`,`',explode(' ', $fields)). '`';
		
		if($verify) return "($like OR MATCH($fields) AGAINST('$term'))";
		else $this->_query_cond[] = "($like OR MATCH($fields) AGAINST('$term'))";
		
		return $this;
	}
	
	/**
	 * Clear the condition array
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function clear_query() {
		$this->_query_cond = array();
		return $this;
	}
	
	/**
	 * Add field to the selection
	 *
	 * @param string $field 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function add_select_field($field) {
		$this->_fields_select .= ", $field";
	}
	
	/**
	 * Add item to group by
	 *
	 * @param string $field 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function group_by($field) {
		$this->_group_cond[] = $field;
		return $this;
 	}
	
	public function distinct($field) {
 		$this->_distinct_cond = "`$field`";
 		return $this;
 	}
	
	/**
	 * Order SQL Results
	 *
	 * @param string $field 
	 * @param string $dir 
	 * @param string $reset 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function order($field, $dir = 'ASC', $reset = false) {
		if($reset) $this->_order_cond = array();
		$field = ctype_alnum($field) ? "`$field`" : $field;
		if(!$field) return $this;
		$dir = ctype_alnum($dir) ? strtoupper($dir) : 'ASC';
		$this->_order_cond[] = "$field $dir";
		
		return $this;
	}
	
	/**
	 * Limit the SQL Results
	 *
	 * @param string $start 
	 * @param string $limit 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function limit($start, $limit = false) {
		if(!is_numeric($start) || !(is_numeric($limit) || $limit == false)) return $this;
		$this->_limit_size = $limit == false ? $start : $limit;
		$this->_limit = $limit == false ? "0, $start" : "$start, $limit";
		return $this;
	}
	
	/**
	 * Clear the limit and show all results
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function clear_limit() {
		$this->_limit_size = false;
		$this->_limit = false;
		$this->_has_query = false;
		return $this;
	}
	
	/**
	 * Count Results
	 *
	 * @param string $all 
	 * @param string $fresh 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function count($all = false, $fresh = false) {
		if($all == false && $this->_has_query != false) return count($this->_results);
		else if($all == false) {
			if(!$this->_count) $this->_run_query('count');
			if($this->_limit_size !== false)
				$c = $this->_count > $this->_limit_size ? $this->_limit_size : $this->_count;
			else $c = $this->_count;
			
			return $c;
		}
		else {
			if(!$this->_count || $fresh) $this->_run_query('count');
			return $this->_count;
		}
	}
	
	/**
	 * Show specific page of results
	 *
	 * @param string $page 
	 * @param string $length 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function page($page = 1, $length = false) {
		if($length) $this->_page_length = $length;
		$page = $page < 1 ? 1 : $page;
		
		$this->_on_page = $page; $page --;
		$this->limit($page * $this->_page_length, $this->_page_length);
		
		return $this;
	}
	
	/**
	 * Return Paging Info
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function paging() {
		$pages = ceil($this->count('all') / $this->_page_length);
		return (object) array(
			'pages' => $page,
			'page' => $this->_on_page,
			'length' => $this->_page_length,
			'items' => $this->count('all')
		);
	}
	
	/**
	 * Get the sum of a specific column
	 *
	 * @param string $column 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function sum($column) {
		if($this->_sum[$column]) $this->_run_query('sum', $column);
		return $this->_sum[$column];
	}
	
	/**
	 * Get the current total page count
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function current_page_count() {
		$paging = $this->paging();
		return max(0, min($paging->length, $paging->items - ($paging->page - 1) * $paging->length));
	}
	
	public function _run_query($count = false, $extra = false) {
		if($count === 'debug') {
			$count = false;
			$debug = true;
		}
		
		/**
		 * Create a blank condition statement
		 */
		$cond = ' ';
		
		/**
		 * Process query conditions
		 */
		if(count($this->_query_cond) > 0) {
			$cond .= 'WHERE ';
			foreach($this->_query_cond as $key => $condi) {
				if(count($this->_query_cond) > 1 && $key != 0) $cond .= '&& ';
				$cond .= $condi.' ';
			}
		}
		
		/**
		 * Process Group By Conditions
		 */
		if(count($this->_group_cond) > 0) {
			foreach($this->_group_cond as $key => $condi) {
				$gc[] = $count == 'sum' ? "`_group`" : "`$condi`";
			}
			$gc = implode(', ', $gc);
			$cond .= 'GROUP BY '.$gc;
		}
		
		/**
		 * Process Order Conditions
		 */
		if((!$count || $count == 'sum') && count($this->_order_cond) > 0) {
			$cond .= 'ORDER BY ';
			foreach($this->_order_cond as $key => $condi) {
				if(count($this->_order_cond) > 1 && $key != 0) $cond .= ', ';
				$cond .= $condi.' ';
			}
		}
		
		/**
		 * Set Result Limit
		 */
		if(!$count && $this->_limit) $cond .= 'LIMIT '.$this->_limit.' ';
		
		/**
		 * Grab the fields to select and add join if one exists
		 */
		$fields_select = $this->_fields_select;
		
		/**
		 * Set us to grab the row count
		 */
		if($count && $count != 'sum') 
			$fields_select = $this->_distinct_cond ? "COUNT(DISTINCT $this->_distinct_cond) AS `ct`" : "COUNT(*) as `ct`";
		
		/**
		 * Get the sum of a row
		 */
		else if($count == 'sum') {
			if(count($this->_group_cond) == 0) $fields_select = "SUM(`$extra`) AS `ct`";
			else if(count($this->_group_cond) == 1) $fields_select = "SUM(`$extra`) as `ct`, ".$this->_group_cond[0]." as `_group`";
		}
		
		/**
		 * Grab the distinct query item if one exists
		 */
		$distinct = $this->_distinct_cond ? "DISTINCT $this->_distinct_cond, " : '';
		
		/**
		 * Prepare the query to run
		 */
		$query = $this->_custom_query ? ($count ? $this->_custom_count_query : $this->_custom_query) : "SELECT $fields_select FROM $this->_tables_select".($this->_join ? $this->_join : '')." $cond";
		
		/**
		 * Return the query that will be run for debug purposes
		 */
		if(isset($debug) && $debug) return $query;
		
		/**
		 * Run query
		 */
		$results = e::sql($this->_connection)->query($query);
		
		/**
		 * Return the count total count of the rows
		 */
		if($count && $count != 'sum') {
			$cr = $results->row();
			$this->_count = $cr['ct'];
		}
		
		/**
		 * Return the sum of the row
		 */
		else if($count == 'sum') {
			if(count($this->_group_cond) == 0) {
				$cr = $results->row();
				$this->_sum[$extra] = $ct['ct'];
			}
			
			else if(count($this->_group_cond) == 1) {
				while($row = $results->row()) $this->_sum[$extra][$row['_group_cond']] = $row['ct'];
			}
			
			return true;
		}
		
		/**
		 * Return the raw results
		 */
		if($this->_raw) {
			$this->_results = $results->all();
			if($count === false)
				$this->_has_query = true;
			return;
		}
		
		$pp = array();
		list($bundle, $model) = explode('.', $this->_table);
		$model = "get".ucwords($this->_tb_singular);
		while($row = $results->row()) $pp[] = $this->_custom_query ? $row : e::$$bundle->$model($row['id']);
		$this->_results = $pp;
		if($count === false)
			$this->_has_query = true;
	}
	
	/**
	 * Return Output
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
	public function all() {
		if($this->_has_query == false) $this->_run_query();
		return $this->_results;
	}
	
	/**
	 * BEGIN ITERATOR METHODS ----------------------------------------------------------------
	 */
	
	public function rewind() {
		if($this->_has_query == false) $this->_run_query();
		$this->position = 0;
	}
	public function keys() {
		if($this->_has_query == false) $this->_run_query();
		return array_keys($this->_results[$this->position]);
	}

	public function current() {
		return $this->_results[$this->position];
	}

	public function key() {
		return $this->_results[$this->position]->id;
	}

	public function next() {
		++$this->position;
	}

	public function valid() {
		return isset($this->_results[$this->position]);
	}

	/**
	 * END ITERATOR METHODS ----------------------------------------------------------------
	 */
	
	/**
	 * Standard query access
	 */
	public function auto() {
		$fields = Bundle::$db_structure[$this->_table]['fields'];
		foreach($_REQUEST as $key => $value) {
			if(empty($value)) continue;
			$value = preg_replace('[^a-zA-Z0-9_.-]', '', $value);
			if($key === 'search') {
				$cond = array();
				$search = func_get_args();
				foreach($search as $field) {
					if(isset($fields[$field]))
						$cond[] = "`$field` LIKE '%$value%'";
				}
				$cond = implode(' OR ', $cond);
				$this->manual_condition($cond);
			} else if(isset($fields[$key])) {
				$this->condition("$key LIKE", "%$value$%");
			}
		}
		return $this;
	}
}