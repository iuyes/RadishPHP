<?php
/**
 * Log management class.
 *
 * @author Lei Lee
 * @version 1.0
 */
class Logger {
	/**
	 * Instance of the RadishPHP object references.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;
	
	/**
	 * The log file storage directory.
	 *
	 * @var string
	 */
	private $_log_dir = NULL;
	
	/**
	 * A single log file size limit.
	 *
	 * @var int
	 */
	private $_log_size_limit = 512000;
	
	/**
	 * Log type.
	 *
	 * @var array
	 */
	private $_type_names = array(
		0 => 'Debug', 1 => 'Info', 2 => 'Warning', 3 => 'Error'
	);
	
	/**
	 * Constructor.
	 *
	 * @param RadishPHP $scope
	 */
	function __construct(&$scope) {
		$this->scope = &$scope;
	}
	
	/**
	 * Set the log file storage directory.
	 *
	 * @param string $log_dir
	 * @return Logger
	 */
	function setDir($log_dir) {
		$this->_log_dir = $log_dir;
		
		return $this;
	}
	
	/**
	 * Set the size limit of a single log file.
	 *
	 * @param int $size
	 * @return Logger
	 */
	function setMaxFileSize($size) {
		$this->_log_size_limit = $size;
		
		return $this;
	}
	
	/**
	 * Written to the log information.
	 *
	 * @param string|array $data
	 */
	function debug($data) {
		$this->write(0, $data);
	}
	
	/**
	 * Written to the log information.
	 *
	 * @param string|array $data
	 */
	function info($data) {
		$this->write(1, $data);
	}
	
	/**
	 * Written to the log information.
	 *
	 * @param string|array $data
	 */
	function warning($data) {
		$this->write(2, $data);
	}
	
	/**
	 * Written to the log information.
	 *
	 * @param string|array $data
	 */
	function error($data) {
		$this->write(3, $data);
	}
	
	/**
	 * Written to the log information.
	 *
	 * @param int $type
	 * @param string|array $data
	 */
	function write($type, $data) {
		if (empty($this->_log_dir)) 
			throw new RuntimeException('Log storage directory has not been set.', -1);
		
		$template = "%s\t[%s] %s\r\n";
		
		$file = $this->_log_dir . DIRECTORY_SEPARATOR . date('Ymd') . '.log';
		$dirn = dirname($file);
		
		if (!is_dir($dirn))
			mkdir($dirn, 755, true);
		
		if (is_file($file)) {
			if ($this->_log_size_limit <= filesize($file)) 
				$file = $this->_log_dir . DIRECTORY_SEPARATOR . date('Ymd') . '-01.log';
		}
		
		$fp = fopen($file, 'a');
		if ($fp) {
			if (is_string($data))
				fwrite($fp, sprintf($template, date('Y-m-d H:i:s'), $this->_type_names[$type], $data));
			elseif (is_array($data)) {
				fwrite($fp, sprintf($template, date('Y-m-d H:i:s'), $this->_type_names[$type], print_r($data, true)));
			}
			
			fclose($fp);
		}
	}
}