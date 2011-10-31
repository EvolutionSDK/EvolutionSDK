<?php

namespace bundles\SQL;

class Architect {
	
	/**
	 * Instance of Connection
	 */
	private $dbh;
	
	/**
	 * Table name
	 */
	public $table;
	
	/**
	 * Array of table field configuration
	 */
	public $fields;
	
	/**
	 * Types of DB columns
	 */
	public static $types = array(
		'string' => 'varchar(255)',
		'text' => 'text',
		'date' => 'datetime',
		'bool' => 'tinyint(1)',
		'number' => 'int(11)',
		'money' => 'decimal(10,2)',
		'decimal' => 'decimal(10,3)'
	);
	
	/**
	 * Default Values not to quote
	 */
	public static $defaults = array(
		'CURRENT_TIMESTAMP'
	);
	
	/**
	 * Changes to an existing table
	 */
	public $changes;
	public $current;
	
	public function __construct(Connection $dbh, $table, $config) {
		$this->dbh = $dbh;
		$this->table = $table;
		
		$fields = $config['fields'];
		
		$tmp = array();
						
		/**
		 * Generate Id Structure
		 */
		if(!isset($fields['id'])) {
			$tmp['id'] = array(
				'Type' => 'number',
				'Null' => 'NO',
				'Key' => 'PRI',
				'Default' => NULL,
				'Extra' => 'auto_increment'
			);
		}
		
		/**
		 * Generate Timestamp Structure
		 */
		if(!isset($fields['updated_timestamp'])) {
			$tmp['updated_timestamp'] = array(
				'Type' => 'timestamp',
				'Null' => 'YES',
				'Key' => '',
				'Default' => 'CURRENT_TIMESTAMP',
				'Extra' => 'on update CURRENT_TIMESTAMP'
			);
		}
		
		if(!isset($fields['created_timestamp'])) {
			$tmp['created_timestamp'] = array(
				'Type' => 'date',
				'Null' => 'YES',
				'Key' => '',
				'Default' => NULL,
				'Extra' => ''
			);
		}
		
		/**
		 * Create Many to Many connection table and columns
		 */
		if(isset($config['manyToMany'])) foreach($config['manyToMany'] as $key=>$table) {
			if(is_numeric($key)) $this->_connection_table($this->table, $table);
		}

		/**
		 * Merge the fields
		 */
		$fields = array_merge_recursive($tmp, $fields);
		
		/**
		 * Make sure all required fields are present
		 */
		foreach($fields as $field=>$val) {
			if($val == '_suppress') { unset($fields[$field]); continue; }
			
			if(!is_array($val)) {
				$val = array(
					'Type' => $val,
					'Null' => 'NO',
					'Key' => '',
					'Default' => NULL,
					'Extra' => ''
				);
			}
			else {
				if(!isset($val['Null'])) $val['Null'] = 'NO';
				if(!isset($val['Key'])) $val['Key'] = '';
				if(!isset($val['Default'])) $val['Default'] = NULL;
				if(!isset($val['Extra'])) $val['Extra'] = '';				
			}
			
			$fields[$field] = $val;
		}
		
		/**
		 * Save fields to the object
		 */
		$this->fields = $fields;
		
		/**
		 * Check to see if the table exists
		 */
		$exists = $this->_exists();
		
		/**
		 * Compare the current field configuration versus the new one
		 */
		if($exists) $this->_compare();
		
		/**
		 * Modify the table structure to match the changes
		 */
		if($exists) $this->_update();
		
		/**
		 * If no table exists create one
		 */
		if(!$exists) $this->_create();
	}
	
	protected function _exists($table = false) {
		$table = $table ? $table : $this->table;
		if(!$this->dbh->query("SHOW TABLES LIKE '$table'")->row()) return false;
		else return true;
	}
	
	protected function _compare() {
		$structure = $this->dbh->query("DESCRIBE `$this->table`")->all();
		
		/**
		 * Format the Table Structures
		 */
		$types = array_flip(self::$types);
		foreach($structure as $key => $field) {
			$struct[$field['Field']] = $field;
			$struct[$field['Field']]['Type'] = (isset($types[$field['Type']]) ? $types[$field['Type']] : $field['Type']);
			unset($struct[$field['Field']]['Field']);
		}
		$structure = $struct;
		
		$this->current = $structure;
		
		$discrepency = array();
		foreach($this->fields as $field=>$opts) {
			$match = false;
			
			if(isset($structure[$field]) && is_array($structure[$field])) $match = true;

			if($match) {
				$sopts = $structure[$field];

				foreach($opts as $var=>$val) if($val !== $sopts[$var]) $match = false;

				if(!$match) $discrepency[$field] = 'changed';
			}
			else {
				$discrepency[$field] = 'added';
			}
		}
		
		foreach($structure as $field=>$sopts) {
			$match = false;
			
			if(isset($this->fields[$field]) && is_array($this->fields[$field])) $match = true;

			if(!$match) $discrepency[$field] = 'removed';
			
		}
		
		//var_dump($discrepency); echo "<br /><br />";
		//var_dump($this->fields); echo "<br /><br />";
		//var_dump($structure); echo "<br /><br />";
		
		return $this->changes = $discrepency;
	}
	
	protected function _update() {
		$changes = $this->changes;
		
		foreach($changes as $field=>$change) {
			if($change !== 'removed') {
				$opts = (object) $this->fields[$field];

				$type = (isset(self::$types[$opts->Type]) ? self::$types[$opts->Type] : $opts->Type);

				if($opts->Null == 'NO') $null = 'NOT NULL'; else $null = 'NULL';

				switch($opts->Default) {
					case NULL:
						if($null == 'NULL') $default = "DEFAULT NULL";
						else $default = "DEFAULT ''";
					break;
					default:
						if(in_array($opts->Default, self::$defaults)) $default = "DEFAULT $opts->Default";
						else $default = "DEFAULT '$opts->Default'";
					break;
				}

				if($opts->Null == 'NO' && $opts->Default == NULL) $default = '';

				if($opts->Extra) $extra = strtoupper($opts->Extra); else $extra = '';
			}
			
			switch($change) {
				case 'added':
					
					if($opts->Key) $default = '';

					switch($opts->Key) {
						case 'PRI':
							$key = " KEY";
						break;
						default:
						break;
					}

					$sql = "ALTER TABLE `$this->table` ADD COLUMN `$field` $type $null $extra $default";
					if(isset($key)) $sql .= $key;

					$this->dbh->query($sql);				
				break;
				case 'changed':

					$sql = "ALTER TABLE `$this->table` CHANGE `$field` `$field` $type $null $extra $default";
					$this->dbh->query($sql);

					switch($opts->Key) {
						case 'PRI':
							$key = "ALTER TABLE `$this->table` ADD PRIMARY KEY ($field)";
						break;
						case 'UNI':
							$key = "ALTER TABLE `$this->table` ADD UNIQUE KEY `$field` (`$field`)";
						break;
						case 'MUL':
							$key = "ALTER TABLE `$this->table` ADD KEY `$field` ($field)";
						break;
						default:
							if($this->current[$field]['Key'] == 'PRI') 
								$key = "ALTER TABLE `$this->table` DROP PRIMARY KEY `$field`";
							else if($this->current[$field]['Key'] !== '') $key = "ALTER TABLE `$this->table` DROP INDEX `$field`";
						break;
					}
					
					if($this->current[$field]['Key'] == $this->fields[$field]['Key']) unset($key); 

					if(isset($key)) $this->dbh->query($key);
				break;
				case 'removed':
					$sql = "ALTER TABLE `$this->table` DROP `$field`";
					$this->dbh->query($sql);
				break;
			}
		}
		
		return true;
	}
	
	protected function _create($table = false, $fields = false) {
		$fields = $fields ? $fields : $this->fields;
		$table = $table ? $table : $this->table;
		
		$prikeys = array();
		$keys = array();
		$cols = array();
		$ai = false;
		
		foreach($fields as $field=>$opts) {
			$opts = (object) $opts;
			
			$type = (isset(self::$types[$opts->Type]) ? self::$types[$opts->Type] : $opts->Type);
			
			if($opts->Null == 'NO') $null = 'NOT NULL'; else $null = 'NULL';
			if($opts->Key == 'PRI') $prikeys[] = "`$field`";
			if($opts->Key == 'UNI') $keys[] = "UNIQUE KEY `$field` (`$field`)";
			if($opts->Key == 'MUL') $keys[] = "KEY `$field` (`$field`)";
			if($opts->Extra) $extra = strtoupper($opts->Extra); else $extra = '';
			
			if($extra == 'AUTO_INCREMENT') $ai = true;

			switch($opts->Default) {
				case NULL:
					if($null == 'NULL') $default = "DEFAULT NULL";
					else $default = "DEFAULT ''";
				break;
				default:
					if(in_array($opts->Default, self::$defaults)) $default = "DEFAULT $opts->Default";
					else $default = "DEFAULT '$opts->Default'";
				break;
			}
			
			if($opts->Null == 'NO' && $opts->Default == NULL) $default = '';
			
			$cols[] = "`$field` $type $null $extra $default";
			
		}
		
		if(!empty($prikeys)) $keys[] = 'PRIMARY KEY ('.implode(', ', $prikeys).')';
		
		$create[] = implode(', ', $cols);
		if(!empty($keys)) $create[] = implode(', ', $keys);
		$create = implode(', ', $create);
		
		if($ai) $ai = "AUTO_INCREMENT=1"; else $ai = '';
		
		$sql = "CREATE TABLE `$table` ($create) ENGINE=MyISAM $ai DEFAULT CHARSET=latin1";
		$this->dbh->query($sql);
	}
	
	public function _connection_table($table_a, $table_b) {
		/**
		 * If the two connections are going to be on the same table
		 */
		if($table_a == $table_b) {
			$fields['$id_a'] = array(
				'Type' => 'number',
				'Null' => 'NO',
				'Key' => 'PRI',
				'Default' => NULL,
				'Extra' => ''
			);

			$fields['$id_b'] = array(
				'Type' => 'number',
				'Null' => 'NO',
				'Key' => 'PRI',
				'Default' => NULL,
				'Extra' => ''
			);

			$fields['$updated_timestamp'] = array(
				'Type' => 'timestamp',
				'Null' => 'YES',
				'Key' => '',
				'Default' => NULL,
				'Extra' => 'on update CURRENT_TIMESTAMP'
			);
			
			$fields['$flags'] = array(
				'Type' => 'bigint(20)',
				'Null' => 'NO',
				'Key' => '',
				'Default' => NULL,
				'Extra' => ''
			);
			
			$table = "\$connect $table_a";
			
			if($this->_exists($table)) return false;
			
			$this->_create($table ,$fields);
			
			return;
		}
		
		/**
		 * Since we are obviously connecting two different tables go here
		 */
		$fields['$'.$table_a.'_id'] = array(
			'Type' => 'number',
			'Null' => 'NO',
			'Key' => 'PRI',
			'Default' => NULL,
			'Extra' => ''
		);
		
		$fields['$'.$table_b.'_id'] = array(
			'Type' => 'number',
			'Null' => 'NO',
			'Key' => 'PRI',
			'Default' => NULL,
			'Extra' => ''
		);
				
		$fields['$updated_timestamp'] = array(
			'Type' => 'timestamp',
			'Null' => 'YES',
			'Key' => '',
			'Default' => 'CURRENT_TIMESTAMP',
			'Extra' => 'on update CURRENT_TIMESTAMP'
		);
		
		$fields['$flags'] = array(
			'Type' => 'bigint(20)',
			'Null' => 'NO',
			'Key' => '',
			'Default' => NULL,
			'Extra' => ''
		);
		
		$table1 = "\$connect $table_a $table_b";
		$table2 = "\$connect $table_b $table_a";
		
		if($this->_exists($table1)) return false;
		if($this->_exists($table2)) return false;
		
		$this->_create($table1 ,$fields);
	}
	
}