<?php

namespace MangaReader;

use PDO;
use PDOException;

/**
 * Database Class
 */
class Database
{
	private $msg;

	private $driver;
	private $dbname;
	
	public $pdo;
	
	private $settings = [
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		/* \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8", */
		\PDO::ATTR_EMULATE_PREPARES => false,
	];
	
	public function __construct()
	{
		//
	}

	//Query One Row
	public function queryOne($query, $params = array())
	{
		$result = $this->pdo->prepare($query);
		$result->execute($params);
		return $result->fetch();
	}	

	//Query Multiple Row
	public function queryAll($query, $params = array())
	{
		$result = $this->pdo->prepare($query);
		$result->execute($params);
		return $result->fetchAll();
	}

	//Query Single Value
	public function querySingle($query, $params = array())
	{
		$result = $this->queryOne($query, $params);
		if (!$result)
				return false;
		return $result[0];
	}

	//Querying the number of affected rows, like CRUD
	// Executes a query and returns the number of affected rows
	public function query($query, $params = array())
	{
		//var_dump($query); echo "<br>";
		$result = $this->pdo->prepare($query, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
		$result->execute($params);
		$this->add_message('Query completed');

		return $result;
	}
	
	//Querying the number of affected rows, like CRUD
	// Executes a query and returns the number of affected rows
	public function query_sql($query)
	{
		$result = $this->pdo->prepare($query);
		$result->execute();
		$this->add_message('Query completed');
		return $result->rowCount();
	}
	
	function connect($dbdriver, $dbname, $dbhost='localhost', $dbuser='', $dbpasswd='')
	{
		$this->driver = $dbdriver;
		$this->dbname = $dbname;
		
		if ($dbdriver==='mysql') {
			try {
			$this->pdo = new PDO(
				"$dbdriver=$host;dbname=$database;charset=utf8mb4",
				$dbuser,
				$dbpasswd,
				$this->settings);
				$this->add_message("Connect success to $dbdriver::$dbname");
			} catch (PDOException $e) {
				$this->add_message("Connect failed to $dbdriver::$dbname in $dbhost with '$dbuser' & '$dbpasswd' ");
			}
		} elseif ($dbdriver==='sqlite') {
			try {
			$this->pdo = new PDO(
				"$dbdriver:$dbname.sqlite3",
				null,
				null,
				$this->settings);
				$this->add_message("Connect success to $dbdriver::$dbname");
				} catch (PDOException $e) {
				$this->add_message("Connect failed to $dbdriver::$dbname");
			}
		} else {
			$this->add_message("Connect failed to $dbdriver::$dbname");
		}
	}
	
	function add_message($msg)
	{
		$this->msg .= $msg."\r\n<br>";
	}
	
	function get_message()
	{
		return $this->msg;
	}
	
	function quote($str) {
		return '`' . str_replace('.', '`.`', trim($str, '`')) . '`';
	}
		
	function lastInsertId() {
		return $this->pdo->lastInsertId();
	}
	
	function close()
	{
		return $this->pdo->close();
	}
	
	private function tableExists($name) {
		$result = $this->query('SELECT name FROM sqlite_master WHERE type="table" and name="'. $name.'"');
		return count($result->fetchAll()) == 1;
	}

	private function getColumns($table) {
		$result = $this->query('PRAGMA table_info(' . $table . ');')->fetchAll(\PDO::FETCH_OBJ);
		$return = [];
		foreach ($result as $row) {
			$return[] = $row->name;
		}
		return $return;
	}
	
	private function buildQuery($data, $prependField = false) {
		$sql = [];
		$args = [];
		foreach ($data as $field => $value) {
			/*
			//For dates with times set, search on time, if the time is not set, search on date only.
			//E.g. searching for all records posted on '2015-11-14' should return all records that day, not just the ones posted at 00:00:00 on that day
			if ($value instanceof \DateTimeInterface) {
				if ($value->format('H:i:s')	== '00:00:00') $value = $value->format('Y-m-d');
				else $value = $value->format('Y-m-d H:i:s');
			}
			*/
			if (is_object($value)) continue;
			if ($prependField){
				$sql[] = $this->quote($field) . ' = :' . $field;
			} else {
				$sql[] = ':' . $field;
			}
			$args[$field] = $value;
		}
		return ['sql' => $sql, 'args' => $args];
	}
	
	function create($table, array $data)
	{
		$query = $this->buildQuery($data);
		$sql = 'INSERT INTO ' . $this->quote($table) . ' (' .implode(', ', array_keys($query['args'])).') VALUES ( ' . implode(', ', $query['sql']). ' )';
		$row = $this->query($sql, $query['args']);
		
		return $row->rowCount();
	}
	
	function read($table, array $opts=[], string $fetch='all')
	{
		$what = isset($opts['what']) ? $opts['what'] : '*';
		$where = isset($opts['where']) ? ' WHERE ' . $opts['where'] : '';
		$limit = (isset($opts['limit'])) ? ' LIMIT ' . $opts['limit'] : '';

		if (isset($opts['offset'])) {
			$offset = ' OFFSET ' . $opts['offset'];
			if (!$limit) $limit = ' LIMIT	1000';
		}
		else $offset = '';

		$order = isset($opts['order']) ? ' ORDER BY ' . $opts['order'] : '';

		$sql = 'SELECT '.$what.' FROM ' . $table . ' ' . $where . $order . $limit . $offset;

		//$sql = "SELECT * FROM `$table`";
		if ($fetch === 'all') {
			$row = $this->query($sql)->fetchAll(\PDO::FETCH_OBJ);
			if($row) $this->add_message('Read completed : '.count($row).' row(s)');
		} elseif ($fetch === 'one') {
			$row = (object) $this->query($sql)->fetch(\PDO::FETCH_ASSOC);
			if($row) $this->add_message('Read completed : one row');
		} else {
			$row->error = 'Error : Fetch mode not correctly specified';
		}
		return $row;
	}
	
	function update($table, array $keys, array $data) {
		$query = $this->buildQuery($data, true);
		$where = [];
		foreach($keys as $field) $where[] = $this->quote($field) . ' = :' . $field;
		$sql = 'UPDATE ' . $this->quote($table) . ' SET ' . implode(', ', $query['sql']). ' WHERE '. implode(' AND ', $where);
		
		$row = $this->query($sql, $query['args']);
		
		return $row->rowCount();
	}
	
	function delete($table, array $opts)
	{
		# code...
	}
	
	function save($table, array $data, string $key=null)
	{
		if (isset($data[$key])) {
			return $this->update($table, [$key], $data);
		} else {
			return $this->create($table, $data);
		}
	}
	
	function valueExists($table, array $args)
	{
		# code...
	}
	
	function row_count($table, $id=null, $key='id')
	{
		if($id!==null){
			$row = $this->read($table, ['what' => 'COUNT('.$key.') AS count', 'where' => "`$key`='$id'"], 'one');
		}else{
			$row = $this->read($table, ['what' => 'COUNT('.$key.') AS count'], 'one');
		}
		
		if(isset($row->error)) return false;
		if(!$row) return false;
		
		return (int) $row->count;
	}
	
	// used to find max/last id of a table if auto_increment is not used
	// only for sqlite database
	function max_id($table, $key='id')
	{
		$row = $this->read($table, ['what' => 'MAX('.$key.') AS max_id'], 'one');
		if(isset($row->error)) return false;
		if(!$row) return false;
		
		return isset($row->max_id) ? (int) $row->max_id : 0;
	}
	// used to find next id of a table if auto_increment is used
	// only for sqlite database
	function next_id($table)
	{
		$row = $this->read('sqlite_sequence', ['where' => 'name="'.$table.'"'], 'one');
		if(isset($row->error)) return false;
		if(!$row) return false;
		
		return isset($row->seq) ? (int) $row->seq : 0;
	}
}