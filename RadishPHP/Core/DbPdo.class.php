<?php
include (RADISHPHP_ROOT_PATH . 'Core/DbParameter.class.php');
include (RADISHPHP_ROOT_PATH . 'Adapters/PageAdapter.class.php');

/**
 * PDO database class object.
 *
 * @author Lei Lee
 * @version 1.0
 */
class DbPdo {
	/**
	 * Return single records.
	 *
	 */
	const PDO_SQL_QUERY_FETCH_ROW = 1;

	/**
	 * Return the entire result set.
	 *
	 */
	const PDO_SQL_QUERY_FETCH_ALL = 2;

	/**
	 * Returns a single row single column result.
	 *
	 */
	const PDO_SQL_QUERY_FETCH_COLUMN = 3;

	/**
	 * Execute INSERT statement.
	 *
	 */
	const PDO_SQL_EXECUTE_INSERT = 4;

	/**
	 * Perform UPDATE / DELETE statements.
	 *
	 */
	const PDO_SQL_EXECUTE_UPDATE_OR_DELETE = 5;

	/**
	 * SQL - INSERT type.
	 *
	 */
	const SQL_TYPE_INSERT = 1;

	/**
	 * SQL - UPDATE type.
	 *
	 */
	const SQL_TYPE_UPDATE = 2;

	/**
	 * SQL - DELETE type.
	 *
	 */
	const SQL_TYPE_DELETE = 3;

	/**
	 * One-time implementation of a number of non-SELECT statement.
	 *
	 */
	const SQL_TYPE_MULTI_QUERY = 4;

	/**
	 * Database connection parameters.
	 *
	 * @var array
	 */
	private $dbParams = array();

	/**
	 * Identity connected?
	 *
	 * @var boolean
	 */
	private $isActived = false;

	/**
	 * PDO Object instance.
	 *
	 * @var PDO
	 */
	private $db = NULL;

	/**
	 * The default connection data source name.
	 *
	 * @var string
	 */
	private $db_default_key = 'default';

	/**
	 * Ignore SQL errors.
	 *
	 * @var boolean
	 */
	private $silent = false;

	/**
	 * RadishPHP object instance.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;

	/**
	 * Query takes seconds.
	 *
	 * @var int
	 */
	static $_execute_seconds = 0;

	/**
	 * Constructor.
	 *
	 * @param RadishPHP $scope
	 * @param string $db_default_key
	 */
	function __construct(&$scope, $db_default_key = NULL) {
		$this->scope = &$scope;

		if (!empty($db_default_key))
			$this->db_default_key = $db_default_key;
	}

	/**
	 * Add a data source.
	 *
	 * @param string $key
	 * @param DbParameter $database
	 * @param boolean $default
	 */
	function addDatabase($key, $database, $default = false) {
		$this->dbParams[$key] = $database;
		if ($default)
			$this->db_default_key = $key;
	}

	/**
	 * Get the database parameter object.
	 *
	 * @param string $key
	 * @return DbParameter
	 */
	function getDatabase($key) {
		return (!empty($key) && isset($this->dbParams[$key])) ? $this->dbParams[$key] : false;
	}

	/**
	 * PDO connection object destruction.
	 *
	 */
	function close() {
		$this->db = NULL;
	}

	/**
	 * Switch the database connection.
	 *
	 * @param string $key
	 */
	function change($key) {
		$this->isActived = false;
		$this->db_default_key = $key;
	}

	/**
	 * Change the current database. (Only for MySQL database connections)
	 *
	 * @param string $dbname The database name.
	 */
	function useDb($dbname) {
		$this->dbParams[$this->db_default_key]->setDbName($dbname);
	}

	/**
	 * DbParameter Parameter parser.
	 * Parameter object passed in here is a two-dimensional array.
	 * The value must include the database type, name, login password and other information.
	 *
	 * @param array $dbParams
	 */
	function addDbParams(&$dbParams) {
		$keys = array_keys($dbParams);
		if (!in_array('default', $keys))
			throw new RuntimeException('The database parameters array must contain a "default" as the key name of the item.', -1);

		foreach ($dbParams as $key => $value) {
			$cfg = new DbParameter();
			$cfg->setDbType($value['type'])
			    ->setDbHost($value['host'])
			    ->setDbName($value['name'])
			    ->setDbPort($value['port'])
			    ->setDbUsername($value['user'])
			    ->setDbPassword($value['pass'])
			    ->setDbCharset($value['charset']);

			$this->dbParams[$key] = $cfg;
		}
	}

	/**
	 * Based on the type of object passed in, execute different SQL queries.
	 * It depends on what type the incoming object.
	 *
	 * Note: Currently only supports PageAdapter object specified number of data reads the result set.
	 *
	 * @param IDbAdapter $adapter
	 */
	function fetchAdapter(&$adapter, $dataParams = array()) {
		if (!is_a($adapter, 'IDbAdapter')) 
			throw new RuntimeException('`$adapter` adapter must be realization of `IDbAdapter` interface.', -1);
			
		if ($adapter->getPageSize() <= 0)
			throw new PDOQueryParameterException('The number of records per page can not be less than or equal to zero.', -1);

		if (preg_match('/ LIMIT [0-9]+(,[0-9]+)?/i', $adapter->getQueryResult()))
			throw new PDOQueryParameterException('Included in the query without LIMIT keyword.', -1);

		$count = $this->_execute($adapter->getQueryCount(), $dataParams, self::PDO_SQL_QUERY_FETCH_COLUMN, 0, 0);
		$adapter->setRecordCount($count);
		$adapter->setPageCount(ceil(( int ) $adapter->getRecordCount() / $adapter->getPageSize()));
		$adapter->setStartIndex(($adapter->getCurrentPageIndex() - 1) * $adapter->getPageSize());
		$adapter->setBegin($adapter->getStartIndex() + 1);
		$adapter->setEnd($adapter->getStartIndex() + $adapter->getPageSize() - 1);

		$dataParams[] = array($adapter->getStartIndex(), PDO::PARAM_INT);
		$dataParams[] = array($adapter->getPageSize(),   PDO::PARAM_INT);

		$d = $this->_execute($adapter->getQueryResult() . ' LIMIT ?,?', $dataParams, self::PDO_SQL_QUERY_FETCH_ALL, PDO::FETCH_ASSOC);
		$adapter->setResult($d);
	}

	/**
	 * Executive SELECT query and returns all the records.
	 *
	 * @param string $sql
	 * @param array $data
	 * @param integer $fetch_style
	 * @param string $field_name_as_key
	 * @return array
	 */
	function fetchs($sql, $data = NULL, $fetch_style = PDO::FETCH_ASSOC, $field_name_as_key = NULL) {
		$d = $this->_execute($sql, $data, self::PDO_SQL_QUERY_FETCH_ALL, $fetch_style);

		if (!is_null($field_name_as_key) && $fetch_style == PDO::FETCH_ASSOC)
			$d = $this->_convent_field_as_result_key($d, $field_name_as_key);

		return $d;
	}

	/**
	 * Executive SELECT query and returns a single line records.
	 *
	 * @param string $sql
	 * @param array $data
	 * @param integer $fetch_style
	 * @param string $field_name_as_key
	 * @return array
	 */
	function fetch($sql, $data = NULL, $fetch_style = PDO::FETCH_ASSOC, $field_name_as_key = NULL) {
		$d = $this->_execute($sql, $data, self::PDO_SQL_QUERY_FETCH_ROW, $fetch_style);

		if (!is_null($field_name_as_key) && $fetch_style == PDO::FETCH_ASSOC)
			$d = $this->_convent_field_as_result_key($d, $field_name_as_key);

		return $d;
	}

	/**
	 * Specified in the query returns a single row result column values​�??.
	 *
	 * @param string $sql
	 * @param array $data
	 * @param integer $column_index
	 * @return mixed
	 */
	function scalar($sql, $data = NULL, $column_index = 0) {
		$d = $this->_execute($sql, $data, self::PDO_SQL_QUERY_FETCH_COLUMN, 0, $column_index);
		return $d;
	}

	/**
	 * Executive INSERT/UPDATE/DELETE query such updates.
	 * If the INSERT query is executed, it returns the last inserted primary key column values.
	 *
	 * @param string $sql
	 * @param array $data
	 * @param int $sql_type
	 * @return integer
	 */
	function execute($sql, $data = NULL, $sql_type = 2, $silent = false) {
		if ($sql_type == self::SQL_TYPE_INSERT) {
			$d = $this->_execute($sql, $data, self::PDO_SQL_EXECUTE_INSERT);
		} elseif ($sql_type == self::SQL_TYPE_UPDATE || $sql_type == self::SQL_TYPE_DELETE) {
			$d = $this->_execute($sql, $data, self::PDO_SQL_EXECUTE_UPDATE_OR_DELETE);
		} elseif ($sql_type == self::SQL_TYPE_MULTI_QUERY) {
			if ($silent)
				$this->isSilent(true);

			$sqls = explode(';', $sql);
			foreach ($sqls as $sql)
				$this->_execute($sql, $data, self::PDO_SQL_EXECUTE_UPDATE_OR_DELETE);

			if ($silent)
				$this->isSilent(false);
		}
		return $d;
	}

	/**
	 * Identity connected?
	 *
	 * @return boolean
	 */
	function isActived() {
		return $this->isActived;
	}

	/**
	 * Set SQL query silent mode.
	 *
	 * @param boolean $is_silent
	 */
	function isSilent($is_silent = true) {
		$this->silent = $is_silent;
	}

	/**
	 * Get query timeout.(milliseconds)
	 *
	 * @return float
	 */
	function consuming() {
		return number_format(self::$_execute_seconds * 1000, 3);
	}

	/**
	 * Executive SQL queries.
	 *
	 * @param string $sql
	 * 				  SQL statement to be executed (to support pre-compiled grammar).
	 * @param array $data
	 * 				  Array of parameters passed.
	 * @param integer $exec_type
	 * 				  Type definitions are available. (INSERT/UPDATE/DELETE/SELECT)�?
	 * @param integer $fetch_style
	 * 				  The results of the structure of the specified form of the output array.
	 * @param integer $column_index
	 * 				  Executive single column query, the value of the specified column index return.
	 * @access private
	 * @return mixed
	 * 				  If returns FALSE, the query operation failed.
	 */
	private function _execute($sql, $data = NULL, $exec_type = self::PDO_SQL_QUERY_FETCH_ALL, $fetch_style = PDO::FETCH_ASSOC, $column_index = 0) {
		$d = false;

		$start_time = $this->_microtimeFloat();

		if (false == $this->isActived) {
			$this->connect();
		}

		try {
			if ($this->silent)
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

			$sth = $this->db->prepare($sql);

			if (false == (is_null($data) || (is_array($data) && count($data) == 0))) {
				foreach ($data as $key => $value) {
					if (is_array($value))
						$sth->bindValue(1 + $key, $value[0], $value[1]);
					else
						$sth->bindValue(1 + $key, $value);
				}
			}

			$sth->execute();
		} catch ( PDOException $ex ) {
			throw new PDOException($ex->getMessage(), 1002);
		}

		switch ($exec_type) {
			case self::PDO_SQL_EXECUTE_INSERT :
				$d = $this->db->lastInsertId();
				break;
			case self::PDO_SQL_EXECUTE_UPDATE_OR_DELETE :
				$d = $sth->rowCount();
				break;
			case self::PDO_SQL_QUERY_FETCH_ROW :
				$d = $sth->fetch($fetch_style);
				break;
			case self::PDO_SQL_QUERY_FETCH_ALL :
				$d = $sth->fetchAll($fetch_style);
				break;
			case self::PDO_SQL_QUERY_FETCH_COLUMN :
				$d = $sth->fetchColumn($column_index);
				break;
		}

		$sth->closeCursor();
		$sth = null;

		$end_time = $this->_microtimeFloat();

		self::$_execute_seconds += ($end_time - $start_time);

		return $d;
	}

	/**
	 * The value of the designated column in the array as an array key name.
	 *
	 * @param array &$data
	 * @param string $field_name
	 * @return array
	 */
	private function _convent_field_as_result_key(&$data, $field_name) {
		if (is_array($data)) {
			$d = array();
			foreach ($data as $value)
				$d[$value[$field_name]] = $value;

			$data = NULL;

			return $d;
		}

		return false;
	}

	/**
	 * Open the database connection.
	 *
	 */
	private function connect() {
		if (empty($this->db_default_key) || false == $this->getDatabase($this->db_default_key)) {
			throw new PDODbParameterException('Invalid database identifier key.', 1005);
		}

		try {
			$dbp = $this->getDatabase($this->db_default_key);
			if (0 == strcasecmp($dbp->getDbType(), 'mysql')) {
				$dsn = 'mysql:host=' . $dbp->getDbHost() . ';dbname=' . $dbp->getDbName() . ';port=' . $dbp->getDbPort();
				$this->db = new PDO($dsn, $dbp->getDbUsername(), $dbp->getDbPassword());
			} elseif (0 == strcasecmp($dbp->getDbType(), 'sqlite')) {
				$dsn = 'sqlite:' . $dbp->getDbName();
				$this->db = new PDO($dsn);
			}

			if ($this->db) {
				if (0 == strcasecmp($dbp->getDbType(), 'mysql')) {
					$this->db->exec("SET NAMES `" . $dbp->getDbCharset() . "`");
				}
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
				$this->db->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
				$this->isActived = true;
			}
		} catch (PDOException $ex) {
			throw new PDOException('Connection failed: ' . $ex->getMessage(), 1001);
		}
	}

	private function _microtimeFloat() {
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}
}

/**
 * The definition of PDO query execution exception.
 *
 */
class PDOQueryParameterException extends Exception {}
class PDODbParameterException extends Exception {}