<?php

namespace bundles\SQL;

/**
 * @todo Make all paging functions part of the list method
 */

class OldList {
	
	/**
	 * The current iterator position.
	 *
	 * @var integer
	 */
	private $position = 0;

	/**
	 * The table this list is for.
	 *
	 * @var string
	 */
	protected $_table = false;

	/**
	 * Whether to return raw assoc array
	 *
	 * @var bool
	 */
	protected $_raw = false;

	/**
	 * The array of results after the query has been executed.
	 *
	 * @var array
	 */
	protected $_results = array();

	/**
	 * The query conditions.
	 *
	 * @var array
	 */
	protected $_query_cond = array();

	/**
	 * Sorting
	 *
	 * @var array
	 */
	protected $_order_cond = array();

	/**
	 * Grouping
	 *
	 * @var string
	 */
	protected $_group_cond = false;

	/**
	 * Distinct Column
	 *
	 * @var string
	 */
	protected $_distinct_cond = false;

	/**
	 * Limiting
	 *
	 * @var string
	 */
	protected $_limit = false;
	protected $_limit_size = false;

	/**
	 * The cached value for the count of items in the resultset.
	 *
	 * @var string
	 */
	protected $_count = 0;
	protected $_count_all = 0;
	protected $_sum = array();

	/**
	 * Default page length.
	 *
	 * @var string
	 */
	protected $_page_length = 5;

	/**
	 * The fields to select in this query.
	 *
	 * @var string
	 */
	protected $_fields_select = '*';

	/**
	 * The tables to select in this query.
	 *
	 * @var string
	 */
	public $_tables_select;

	protected $_custom_query;
	protected $_custom_count_query;

	/**
	 * What page of results are we currently on?
	 *
	 * @var string
	 */
	protected $_on_page = 1;

	/**
	 * Initialize
	 *
	 * @author David Boskovic
	 */
	public function __construct($table) {
		$this->_table = $table;
		
		$this->_tables_select = "`$this->_table`";
		$this->initialize();
	}

	/**
	 * Function for inheriting classes to use.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	protected function initialize() {

	}

    /**
     * Debug this instance
     * @author Nate Ferrero
     */
    public function debug() {
        $query = $this->_query_cond;




		// $query
		eval(d);
    }

	/**
	 * Add a condition to filter the result.
	 *
	 * @param string $field 
	 * @param string $value 
	 * @param bool $verify 
	 * @return $this
	 * @author David Boskovic
	 */
	public function condition($field, $value, $verify = false) {
		$signal = strpos($field, ' ') ? substr($field, strpos($field, ' ')+1) : '=';
		$field = strpos($field, ' ') ? substr($field, 0, strpos($field, ' ')) : $field;
		$value = (strpos($value, ':') === 0 && ctype_alpha(substr($value,1)) == true) ? '`'.substr($value, 1).'`' : $value;
		$value = is_null($value) || is_numeric($value) || strpos($value, '`') === 0 ? $value : "'$value'";
		if(is_null($value)) $value = 'NULL';
		$field = strpos($field,'`') === 0 ? $field : "`$this->_table`.`$field`";
		if($verify) return "$field $signal $value";
		$this->_query_cond[] = "$field $signal $value";
		return $this;
	}

	/**
	 * Clear query
	 */
	public function clear_query() {
		$this->_query_cond = array();
		return $this;
	}

	public function distinct($field) {
		$this->_distinct_cond = "`$field`";
		return $this;
	}

	public function condition_manual($condition) {
		$this->_query_cond[] = "$condition";
		return $this;
	}
	
	public function add_select_field($field) {
		$this->_fields_select .= ", $field";
	}

	/**
	 * Automatic multiple field condition.
	 *
	 * @param string $condition
	 * @param string $fields
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function multiple_field_condition($condition, $fields, $verify = false) {
		$fields = explode(' ', $fields);
		if(count($fields) == 0)
			return $this;

		$query = '';
		foreach($fields as $field) {
			if(strtoupper($field) == 'OR') {
				$query .= ' OR ';
			} else if(strtoupper($field) == 'AND') {
				$query .= ' AND ';
			} else {
				$query .= "`$field` $condition";
			}
		}
		if($verify) return "($query)";
		$this->_query_cond[] = "($query)";
		return $this;
	}

	/**
	 * Create an isolated condition
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
	 * Multiple field text search.
	 *
	 * @param string $term
	 * @param string $fields
	 * @param bool $verify 
	 * @return $this
	 * @author Nate S. Ferrero
	 */
	public function multiple_field_search($term, $fields, $verify = false) {
		$term = mysql_real_escape_string($term);
		if(strlen($term) == 0)
			return $verify ? '' : $this;

		$like = '`' . implode('` LIKE "%'.$term.'%" OR `', explode(' ', $fields)). '` LIKE "%'.$term.'%"';
		$fields = '`' . implode('`,`', explode(' ', $fields)). '`';

		if($verify) return "($like OR MATCH($fields) AGAINST('$term'))";
		$this->_query_cond[] = "($like OR MATCH($fields) AGAINST('$term'))";

		return $this;
	}

	/**
	 * Add a sorting condition.
	 *
	 * @param string $field 
	 * @param string $dir
	 * @return $this
	 * @author David Boskovic
	 */
	public function order($field, $dir = 'ASC', $reset = false) {
		if($reset == 'reset' || $reset)
			$this->_order_cond = array();

		$field = ctype_alnum($field) ? "`$field`" : $field;
		if(!$field) return $this;
		$dir = ctype_alnum($dir) ? strtoupper($dir) : 'ASC';
		$this->_order_cond[] = "$field $dir";
		return $this;

	}

	/**
	 * Add a grouping condition.
	 *
	 * @param string $field 
	 * @param string $dir
	 * @return $this
	 * @author David Boskovic
	 */
	public function group_by($field) {
		$this->_group_cond = "$field";
		return $this;

	}

	/**
	 * Limit the results
	 *
	 * @param integer $start 
	 * @param integer $limit 
	 * @return $this
	 * @author David Boskovic
	 */
	public function limit($start,$limit = false) {

		if(!is_numeric($start) || !(is_numeric($limit) || $limit == false)) return $this;
		$this->_limit_size = $limit == false ? $start : $limit;
		$this->_limit = $limit == false ? "0, $start" : "$start, $limit";
		return $this;

	}

	/**
	 * Clear the limit, show all results. This will require the query to run again.
	 *
	 * @return $this
	 * @author Nate Ferrero
	 */
	public function clear_limit() {
		$this->_limit_size = false;
		$this->_limit = false;
		$this->_has_query = false;
		return $this;
	}

	/**
	 * Choose a specific page of results.
	 *
	 * @param string $page 
	 * @param string $length 
	 * @return integer
	 * @author David Boskovic
	 */
	public function page($page = 1, $length = false) {
		if($length) $this->_page_length = $length;
		$page = $page < 1 ? 1 : $page;
		$this->_on_page = $page;
		--$page;
        $this->limit($page*$this->_page_length,$this->_page_length);
		return $this;
	}

	/**
	 * Set the page length
	 *
	 * @param string $length 
	 * @return integer
	 * @author David Boskovic
	 */
	public function page_length($length = false) {
		if($length) $this->_page_length = $length;
		return $this;
	}

	/**
	 * Get the information for the paging.
	 *
	 * @return array
	 * @author David Boskovic
	 */
	public function paging() {
		$pages = ceil($this->count('all') / $this->_page_length);
		$response = array('pages' => $pages, 'page' => $this->_on_page,'length' => $this->_page_length,'items' => $this->count('all'));
		return (object) $response;		
	}

	/**
	 * Get the paging HTML if a standard function is defined.
	 *
	 * @return html string
	 * @author Nate Ferrero
	 */
	public function paging_html($get_var = 'page', $size = 'default',$anchor = false,$top=false) {
		$paging = $this->paging();
		if($top) $anchor_a = "<a name=\"$anchor\"></a>";
		else $anchor_a = '';
		if(function_exists('draw_paginate'))
			return draw_paginate($paging->page,$paging->items,$paging->length, $get_var, $size,'normal',$anchor).$anchor_a."<div class=\"clear\"></div>";
		return 'Please define a function called <code>draw_paginate($page, $items, $page_length, $get_var)</code>.';
	}

	/**
	 * Get the total result count.
	 * This is also the implementation for count(List_MySQL) and subclasses.
	 *
	 * @return integer
	 * @author David Boskovic
	 */
	public function count($all = false, $fresh = false) {
		if($all == false && $this->_has_query != false) {
			return count($this->_results);
		} elseif($all == false) {
			if(!$this->_count) $this->_run_query('count');
			if($this->_limit_size !== false) {
				$c = $this->_count > $this->_limit_size ? $this->_limit_size : $this->_count;
			} else $c = $this->_count;
			return $c;
		} else {
			if(!$this->_count || $fresh)$this->_run_query('count');
			return $this->_count;
		}
	}

	/**
	 * Get the sum of a specific column.
	 * This is also the implementation for count(List_MySQL) and subclasses.
	 *
	 * @return integer
	 * @author David Boskovic
	 */
	public function sum($column) {
		if(!$this->_sum[$column]) $this->_run_query('sum', $column);
		return $this->_sum[$column];
	}

	/**
	 * Get the number of items on the current page.
	 *
	 * @return integer
	 * @author Nate S. Ferrero
	 */
	public function current_page_count() {
		$paging = $this->paging();
		return max(0, min($paging->length, $paging->items - ($paging->page - 1) * $paging->length));
	}

	/**
	 * Run the actual query.
	 *
	 * @param string $count 
	 * @return void
	 * @author David Boskovic
	 */
	public function _run_query($count = false, $extra = false) {
        // Debug mode
        if($count === 'debug') {
            $count = false;
            $debug = true;
        }

		$cond = ' ';
		$con = (count($this->_connection_conds) > 0);
		if($con) {
			$cond .= 'WHERE (';
			$i = 0;
			foreach($this->_connection_conds as $key => $condi) {
				$condi = $this->condition($key, $condi, true);
				if(count($this->_connection_conds) > 1 && $i != 0)
					$cond .= '&& ';
				$cond .= $condi.' ';
				++$i;
			}
			$cond .= ') ';
		}
		if(count($this->_query_cond) > 0) {
			$cond .= $con ? 'AND (' : 'WHERE ';
			foreach($this->_query_cond as $key => $condi) {
				if(count($this->_query_cond) > 1 && $key != 0)
					$cond .= '&& ';
				$cond .= $condi.' ';
			}
			$cond .= $con ? ') ' :'';
		}
		if($this->_group_cond) {
			$gc = $count == 'sum' ? '_group' : $this->_group_cond;
			$cond .= "GROUP BY `$gc`";
		}
		if((!$count || $count == 'sum') && count($this->_order_cond) > 0) {
			$cond .= 'ORDER BY ';
			foreach($this->_order_cond as $key => $condi) {
				if(count($this->_order_cond) > 1 && $key != 0)
					$cond .= ', ';
				$cond .= $condi.' ';
			}
		}
		if(!$count && $this->_limit) $cond .= 'LIMIT '.$this->_limit.' ';
		$fs = $this->_fields_select;
		if($count && $count != 'sum') $fs = $this->_distinct_cond ? "COUNT(DISTINCT $this->_distinct_cond) AS `ct`" : "COUNT(*) AS `ct`";
		elseif($count == 'sum') {
			if(!$this->_group_cond) {
				$fs = "SUM(`$extra`) AS `ct`";
			}
			else {
				$fs = "SUM(`$extra`) AS `ct`, $this->_group_cond AS `_group`";
			}
		}
		$ds = $this->_distinct_cond ? "DISTINCT $this->_distinct_cond," : '';
        $query = $this->_custom_query ? ($count ? $this->_custom_count_query : $this->_custom_query): "SELECT $fs FROM $this->_tables_select $cond";
        if(isset($debug) && $debug)
            return $query;
		$results = e::$db->query($query);
		if($count && $count != 'sum') {
			$cr = $results->row();
			$this->_count = $cr['ct'];
			return true;
		}
		elseif($count == 'sum') {
			if(!$this->_group_cond) {
				$cr = $results->row();
				$this->_sum[$extra] = $cr['ct'];
			}
			else {
				while($row = $results->row()) {
					$this->_sum[$extra][$row['_group_cond']] = $row['ct'];
				}
			}
			return true;			
		}

		if($this->_raw) {
			$this->_results = $results->all();
			$this->_has_query = true;
			return;
		}

		$pp = array();
		list($map_app, $map_module) = explode('.',$this->_map);
		while($row = $results->row())
			$pp[] = $this->_custom_query ? $row : $this->component->$map_module($row);
		$this->_results = $pp;
		$this->_has_query = true;
	}

	public function _scope_by_pos($pos) {
		return $this->_results[$pos];
	}

	public function _scope_rewind() {
		if($this->_has_query == false) $this->_run_query();
	}

	// Split results into two columns
	public function two_columns() {
		if($this->_has_query == false) $this->_run_query();
		$count = count($this->_results);
		$second = $this->_results;
		$first = array_splice($second, 0, ceil($count/2));
		return array($first, $second);
	}

	public function fetch() {
		if($this->_has_query == false) $this->_run_query();
		$this->position++;
		return element($this->_results, $this->position - 1);
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

	public function all() {
		if($this->_has_query == false) $this->_run_query();
		return $this->_results;
	}

	public function excel($filename=false){
		$this->clear_limit();
		if(!$filename)return;
		$filename="list-$filename-".date("Y-m-d").".xls";
		$fname = ROOT_LIBRARY."/admin/$filename";
		$excel=new ExcelWriter($fname);
		//die(var_dump($excel));
		if($excel==false){

		} else {
			$dump_head=true;
			foreach($this as $row){
				$tmp=$row->model->get_array();
				unset($tmp["logo"]);
				unset($tmp["photo"]);
				if($dump_head){
					$keys=array_keys($tmp);
					$excel->writeLine($keys);
					$dump_head=false;
				}
				$excel->writeLine($tmp);
			}
			$excel->close();	
			$status = @output_file($fname, $filename);
		}

	}

	public function csv($filename=false){
		$this->clear_limit();
		if(!$filename)return;
		$filename="list-$filename-".date("Y-m-d").".csv";
		$fname = ROOT_LIBRARY."/admin/$filename";
		$fileout=fopen($fname,'w');
		$dump_head=true;

		foreach($this as $row){
			$tmp=$row->model->get_array();
			unset($tmp["logo"]);
			unset($tmp["photo"]);
			if($dump_head){
				$keys=array_keys($tmp);
				fputcsv($fileout,$keys);
				$dump_head=false;
			}
			fputcsv($fileout,$tmp);
		}
		fclose($fileout);
		$status = @output_file($fname, $filename, 'text/csv');		
	}

	public function xml($filename=false){
		$this->clear_limit();
		if(!$filename)return;
		$filename="list-$filename-".date("Y-m-d").".xml";
		$fname = ROOT_LIBRARY."/admin/$filename";
		$fileout=fopen($fname,'w');
		fwrite($fileout,'<?xml version="1.0" encoding="UTF-8" ?>'."\n");
		foreach($this as $row){
			$tmp=$row->model->get_array();
			unset($tmp["logo"]);
			unset($tmp["photo"]);
			fwrite($fileout,"<row>\n");
			foreach($tmp as $key=>$value){
				$strout="   <$key>$value<$key>\n";
				fwrite($fileout,$strout);
			}
			fwrite($fileout,"</row>\n");
		}
		fclose($fileout);
		$status = @output_file($fname, $filename, 'text/xml');
	}
	
}