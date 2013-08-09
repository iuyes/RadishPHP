<?php
/**
 * Standard database cache.
 *
 * @author Lei Lee
 * @version 1.0
 */
class DbCacheAdapter implements ICacheAdapter {
	/**
	 * Set the DbPdo instance.
	 *
	 * @var DbPdo
	 */
	private $db = NULL;
	
	/**
	 * Set the Cache configurations.
	 *
	 * @var array
	 */
	private $cfgs = array();
	
	/**
	 * Constructor.
	 *
	 * @param DbPdo $db
	 * @param array $cfgs
	 */
	function __construct(&$db, &$cfgs) {
		$this->db = &$db;
		$this->cfgs = &$cfgs;
	}
	
	/**
	 * Set the cache key / value.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire_seconds
	 * @param string $description
	 */
	function set($key, $value, $expire_seconds = 0, $description = NULL) {
		$this->check();
		
		$cache_key = md5($key);
		$expire_time = 0;
		if ($expire_seconds > 0)
			$expire_time = time() + $expire_seconds;
		
		$count = ( int ) $this->db->scalar("SELECT COUNT(1) FROM `" . $this->cfgs['db']['table'] . "` WHERE `cache_key` = ?", array(
			$cache_key
		));
		if ($count > 0) {
			$this->db->execute("UPDATE `" . $this->cfgs['db']['table'] . "` SET `data` = ?, `cache_seconds` = ?, `expire_time` = ?, `refresh_time` = ?, `description` = ? WHERE `cache_key` = ?", array(
				serialize($value), 
				$expire_seconds, 
				$expire_time, 
				time(), 
				$description, 
				$cache_key
			), DbPdo::SQL_TYPE_UPDATE);
		} else {
			$this->db->execute("INSERT INTO `" . $this->cfgs['db']['table'] . "` (`cache_key`, `keyname`, `data`, `cache_seconds`, `expire_time`, `refresh_time`, `description`) VALUES(?, ?, ?, ?, ?, ?, ?)", array(
				$cache_key, 
				$key, 
				serialize($value), 
				$expire_seconds, 
				$expire_time, 
				time(), 
				$description
			), DbPdo::SQL_TYPE_INSERT);
		}
	}
	
	/**
	 * Get the cache value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	function get($key) {
		$this->check();
		
		$cache_key = md5($key);
		
		$d = $this->db->fetch("SELECT `data`, `expire_time` FROM `" . $this->cfgs['db']['table'] . "` WHERE `cache_key` = ?", array(
			$cache_key
		));
		if ($d) {
			if (0 < ( int ) $d['expire_time'] && ( int ) $d['expire_time'] < time()) {
				$this->db->execute("DELETE FROM `" . $this->cfgs['db']['table'] . "` WHERE `cache_key` = ?", array(
					$cache_key
				), DbPdo::SQL_TYPE_DELETE);
			}
			
			$data = unserialize($d['data']);
			if (!empty($data))
				return $data;
		}
		return false;
	}
	
	/**
	 * Remove a cache record.
	 *
	 * @param string $key
	 */
	function delete($key) {
		$this->check();
		
		$cache_key = md5($key);
		
		$this->db->execute("DELETE FROM `" . $this->cfgs['db']['table'] . "` WHERE `cache_key` = ?", array(
			$cache_key
		), DbPdo::SQL_TYPE_DELETE);
	}
	
	/**
	 * Access to cache the last refresh time.
	 *
	 * @param string $key
	 * @return int
	 */
	function getLastRefreshTime($key) {
		$this->check();
		
		$cache_key = md5($key);
		
		$data = $this->db->fetch("SELECT `refresh_time` FROM `" . $this->cfgs['db']['table'] . "` WHERE `cache_key` = ?", array(
			$cache_key
		));
		if ($data) {
			return $data['refresh_time'];
		}
		return false;
	}
	
	/**
	 * Check the cache configuration parameters are valid?
	 *
	 */
	function check() {
		if (empty($this->cfgs['db']))
			throw new RuntimeException('Not detected cache configuration parameters.', -1);
		if (empty($this->cfgs['db']['table']))
			throw new RuntimeException('Cache data table is not set.', -1);
	}
	
	/**
	 * Returns a static instance of the object.
	 *
	 * @param DbPdo $db
	 * @param array $cfgs
	 * @return DbCacheAdapter
	 */
	static function getInstance(&$db, &$cfgs) {
		return new DbCacheAdapter($db, $cfgs);
	}
}