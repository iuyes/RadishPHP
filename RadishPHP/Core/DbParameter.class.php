<?php
/**
 * Database parameter object.
 *
 */
class DbParameter {
	/**
	 * Database type.
	 *
	 * @var string
	 */
	private $dbType = 'mysql';
	
	/**
	 * Database host address.
	 *
	 * @var string
	 */
	private $dbHost = 'localhost';
	
	/**
	 * Connection user name.
	 *
	 * @var string
	 */
	private $dbUsername = 'root';
	
	/**
	 * Login password.
	 *
	 * @var string
	 */
	private $dbPassword = '';
	
	/**
	 * Database service port.
	 *
	 * @var int
	 */
	private $dbPort = '';
	
	/**
	 * Database name.
	 *
	 * @var string
	 */
	private $dbName = '';
	
	/**
	 * Connection with the character set.
	 *
	 * @var string
	 */
	private $dbCharset = 'utf8';
	
	/**
	 * Constructor.
	 *
	 */
	public function __construct() {
	}
	
	/**
	 * Get host address.
	 * 
	 * @return string
	 */
	public function getDbHost() {
		return $this->dbHost;
	}
	
	/**
	 * Get the database name.
	 * 
	 * @return string
	 */
	public function getDbName() {
		return $this->dbName;
	}
	
	/**
	 * Get password.
	 * 
	 * @return string
	 */
	public function getDbPassword() {
		return $this->dbPassword;
	}
	
	/**
	 * Get database port.
	 * 
	 * @return int
	 */
	public function getDbPort() {
		return $this->dbPort;
	}
	
	/**
	 * Get the data source type.
	 * 
	 * @return string
	 */
	public function getDbType() {
		return $this->dbType;
	}
	
	/**
	 * Get login user name.
	 * 
	 * @return string
	 */
	public function getDbUsername() {
		return $this->dbUsername;
	}
	
	/**
	 * Get character set.
	 *
	 * @return string
	 */
	public function getDbCharset() {
		return $this->dbCharset;
	}
	
	/**
	 * Set the host address.
	 * 
	 * @param string $dbHost
	 * @return DbParameter
	 */
	public function setDbHost($dbHost) {
		$this->dbHost = $dbHost;
		return $this;
	}
	
	/**
	 * Set the database name.
	 * 
	 * @param string $dbName
	 * @return DbParameter
	 */
	public function setDbName($dbName) {
		$this->dbName = $dbName;
		return $this;
	}
	
	/**
	 * Set login password.
	 * 
	 * @param string $dbPassword
	 * @return DbParameter
	 */
	public function setDbPassword($dbPassword) {
		$this->dbPassword = $dbPassword;
		return $this;
	}
	
	/**
	 * Set connection port.
	 * 
	 * @param int $dbPort
	 * @return DbParameter
	 */
	public function setDbPort($dbPort) {
		$this->dbPort = $dbPort;
		return $this;
	}
	
	/**
	 * Set the data source type.
	 * 
	 * @param string $dbType
	 * @return DbParameter
	 */
	public function setDbType($dbType) {
		$this->dbType = $dbType;
		return $this;
	}
	
	/**
	 * Set the login user name.
	 * 
	 * @param string $dbUsername
	 * @return DbParameter
	 */
	public function setDbUsername($dbUsername) {
		$this->dbUsername = $dbUsername;
		return $this;
	}
	
	/**
	 * Set the character set.
	 *
	 * @param string $dbCharset
	 * @return DbParameter
	 */
	public function setDbCharset($dbCharset) {
		$this->dbCharset = $dbCharset;
		return $this;
	}
}