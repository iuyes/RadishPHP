<?php
/**
 * System Information Class.
 *
 * @author Lei Lee
 * @version 1.0
 */
class SystemInformation {
	/**
	 * The version number.
	 *
	 * @var string
	 */
	private $_version = NULL;
	
	/**
	 * Page character encoding.
	 *
	 * @var string
	 */
	private $_charset = NULL;
	
	/**
	 * Set the version number.
	 *
	 * @param string $version
	 * @return SystemInformation
	 */
	function setVersion($version) {
		$this->_version = $version;
		return $this;
	}
	
	/**
	 * Get the version number.
	 *
	 * @return string
	 */
	function getVersion() {
		return $this->_version;
	}
	
	/**
	 * Set the page character encoding.
	 *
	 * @param string $charset
	 * @return SystemInformation
	 */
	function setCharset($charset) {
		$this->_charset = $charset;
		return $this;
	}
	
	/**
	 * Get the page character encoding.
	 *
	 * @return string
	 */
	function getCharset() {
		return $this->_charset;
	}
	
	/**
	 * Return an array of SystemInformation.
	 *
	 * @return array
	 */
	function toArray() {
		$dataResult = array(
			'version' => $this->getVersion(), 'charset' => $this->getCharset()
		);
		return $dataResult;
	}
}