<?php
/**
 * Data Cache Management.
 *
 * @author Lei Lee
 */
class Cache {
	/**
	 * Mode using the PHP object serialization.
	 *
	 */
	const CACHE_ADAPTER_SERIALIZER = 1;

	/**
	 * Mode using the DB object.
	 *
	 */
	const CACHE_ADAPTER_DB = 2;

	/**
	 * Set cache mode.
	 *
	 * @var int
	 */
	private $adapter_type = 2;

	/**
	 * RadishPHP instance.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;

	/**
	 * Constructor.
	 *
	 * @param RadishPHP $scope
	 */
	function __construct(&$scope) {
		$this->scope = &$scope;
	}

	/**
	 * Set the cache data.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire_seconds
	 * @param string $description
	 */
	function set($key, $data, $expire_seconds = 300, $description = NULL) {
		switch ($this->adapter_type) {
			case 1:
				throw new RuntimeException('The cache adapter function has not been achieved. CacheAdapterSerializer::set()', -1);
				break;
			case 2:
				include_once (RADISHPHP_ROOT_PATH . 'Adapters' . DS . 'DbCacheAdapter.class.php');
				DbCacheAdapter::getInstance($this->scope->db, $this->scope->getCacheOptions())->set($key, $data, $expire_seconds, $description);
				break;
			default:
				throw new RuntimeException('Using an unknown cache adapter.', -1);
				break;
		}
	}

	/**
	 * Get the cache data.
	 *
	 * @param string $key
	 * @return mixed
	 */
	function get($key) {
		switch ($this->adapter_type) {
			case 1:
				throw new RuntimeException('The cache adapter function has not been achieved. CacheAdapterSerializer::get()', -1);
				break;
			case 2:
				include_once (RADISHPHP_ROOT_PATH . 'Adapters' . DS . 'DbCacheAdapter.class.php');
				return DbCacheAdapter::getInstance($this->scope->db, $this->scope->getCacheOptions())->get($key);
				break;
			default:
				throw new RuntimeException('Using an unknown cache adapter.', -1);
				break;
		}
	}

	/**
	 * Delete the cache data.
	 *
	 * @param string $key
	 */
	function delete($key) {
		switch ($this->adapter_type) {
			case 1:
				throw new RuntimeException('The cache adapter function has not been achieved. CacheAdapterSerializer::delete()', -1);
				break;
			case 2:
				include_once (RADISHPHP_ROOT_PATH . 'Adapters' . DS . 'DbCacheAdapter.class.php');
				return DbCacheAdapter::getInstance($this->scope->db, $this->scope->getCacheOptions())->delete($key);
				break;
			default:
				throw new RuntimeException('Using an unknown cache adapter.', -1);
				break;
		}
	}
	
	/**
	 * Access to cache the last refresh time.
	 *
	 * @param string $key
	 */
	function getLastRefreshTime($key) {
		switch ($this->adapter_type) {
			case 1:
				throw new RuntimeException('The cache adapter function has not been achieved. CacheAdapterSerializer::getRefreshTime()', -1);
				break;
			case 2:
				include_once (RADISHPHP_ROOT_PATH . 'Adapters' . DS . 'DbCacheAdapter.class.php');
				return DbCacheAdapter::getInstance($this->scope->db, $this->scope->getCacheOptions())->getLastRefreshTime($key);
				break;
			default:
				throw new RuntimeException('Using an unknown cache adapter.', -1);
				break;
		}
	}

	/**
	 * Set cache mode.
	 *
	 * @param int $adapter_type
	 * @return Cache
	 */
	function setAdapterType($adapter_type) {
		$this->adapter_type = $adapter_type;
		return $this;
	}
}