<?php
/**
 * System data objects.
 *
 * @author Lei Lee
 */
class DataBridge {
	/**
	 * Store POST data collection.
	 *
	 * @var array
	 */
	private $_post = array();

	/**
	 * Store GET data collection.
	 *
	 * @var array
	 */
	private $_gets = array();

	/**
	 * Store $_SERVER data collection.
	 *
	 * @var array
	 */
	private $_envs = array();

	/**
	 * RadishPHP object instance.
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

		if (get_magic_quotes_runtime()) {
			$this->deslashes($_POST);
			$this->deslashes($_GET);
		}

		$this->_post = &$_POST;
		$this->_gets = &$_GET;
		$this->_envs = &$_SERVER;
	}

	/**
	 * Access GET parameters.
	 *
	 * @param string $key
	 * @param boolean $trim
	 * @param mixed $default_value
	 */
	function gets($key, $trim = false, $default_value = NULL) {
		if ($trim === true)
			$this->_gets[$key] = trim($this->_gets[$key]);

		if (!empty($this->_gets[$key]))
			return $this->_gets[$key];
		else
			return $default_value;
	}

	/**
	 * Access POST parameters.
	 *
	 * @param string $key
	 * @param boolean $trim
	 * @param mixed $default_value
	 */
	function form($key, $trim = false, $default_value = NULL) {
		if ($trim === true)
			$this->_post[$key] = trim($this->_post[$key]);

		if (!empty($this->_post[$key]))
			return $this->_post[$key];
		else
			return $default_value;
	}
	
	/**
	 * Verification form that the value is NULL or an empty string.
	 *
	 * @param string $key
	 * @return boolean
	 */
	function isNullOrEmpty($key) {
		if (!isset($this->_post[$key]) || 0 == strlen(trim($this->_post[$key]))) 
			return true;
		else 
			return false;
	}
	
	/**
	 * Build SQL field list string.
	 *
	 * @param array $data
	 * @return string
	 */
	function createFields(&$data) {
		$keys = array_keys($data);
		
		$d = array();
		foreach ($keys as $v) {
			$d[] = "`" . $v . "` = ?";
		}
		
		return implode(', ', $d);
	}

	/**
	 * Test whether a string is empty?
	 *
	 * @param string $field_name
	 * @return boolean
	 */
	function isZeroLen($field_name) {
		if (0 == strcmp($_SERVER['REQUEST_METHOD'], 'POST'))
			$v = trim($_POST[$field_name]);
		else
			$v = trim($_GET[$field_name]);

		if (0 == strlen($v))
			return true;
		else
			return false;
	}

	/**
	 * Access $_SERVER parameters.
	 *
	 * @param string $key
	 */
	function envs($key) {
		return $this->_envs[$key];
	}

	/**
	 * Remove the quotes escaped.
	 *
	 * @param array $data
	 */
	function deslashes(&$data) {
		if (is_array($data)) {
			foreach ( $data as $key => $value ) {
				if (is_array($value))
					$this->deslashes($data[$key]);
				elseif (is_string($value))
					$data[$key] = stripslashes($value);
			}
		} elseif (is_string($data)) {
			$data = stripslashes($data);
		}
	}

	/**
	 * Chinese and English mixed string interception.
	 *
	 * @param string $string
	 * @param int $length
	 * @param string $dot
	 * @param string $charset
	 * @return string
	 */
	function truncate($string, $length, $dot = '...', $charset = 'gbk') {
		if (strlen($string) <= $length) {
			return $string;
		}

		$string = str_replace(array(
			'〄1�7',
			'&nbsp;',
			'&amp;',
			'&quot;',
			'&lt;',
			'&gt;'
		), array(
			'',
			'',
			'&',
			'"',
			'<',
			'>'
		), $string);

		$strcut = '';
		if (strtolower($charset) == 'utf-8') {
			$n = $tn = $noc = 0;
			while ( $n < strlen($string) ) {
				$t = ord($string[$n]);
				if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1;
					$n++;
					$noc++;
				} elseif (194 <= $t && $t <= 223) {
					$tn = 2;
					$n += 2;
					$noc += 2;
				} elseif (224 <= $t && $t < 239) {
					$tn = 3;
					$n += 3;
					$noc += 2;
				} elseif (240 <= $t && $t <= 247) {
					$tn = 4;
					$n += 4;
					$noc += 2;
				} elseif (248 <= $t && $t <= 251) {
					$tn = 5;
					$n += 5;
					$noc += 2;
				} elseif ($t == 252 || $t == 253) {
					$tn = 6;
					$n += 6;
					$noc += 2;
				} else {
					$n++;
				}

				if ($noc >= $length) {
					break;
				}

			}
			if ($noc > $length) {
				$n -= $tn;
			}

			$strcut = substr($string, 0, $n);

		} else {
			for($i = 0; $i < $length; $i++) {
				$strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
			}
		}

		return $strcut . $dot;
	}

	/**
	 * Generate a unique identification string.
	 *
	 * @param string $prefix
	 * @return string
	 */
	function unique($prefix = '') {
		$s = uniqid($prefix, true);
		$s = str_replace('.', '_', $s);
		return $s;
	}
	
	/**
	 * Get a collection of query parameters.
	 *
	 * @return array
	 */
	function queries() {
		return $this->_gets;
	}
	
	/**
	 * Get POST parameters collection.
	 *
	 * @return array
	 */
	function forms() {
		return $this->_post;
	}

	/**
	 * Data character set encoding conversion.
	 *
	 * @param array|string $data
	 * @param string $from
	 * @param string $to
	 */
	function encoding(&$data, $from = 'GBK', $to = 'UTF-8') {
		if (is_string($data)) {
			$data = iconv($from, $to, $data);
		} elseif (is_array($data) && !empty($data)) {
			foreach ( $data as $key => $value ) {
				if (is_array($value)) {
					$this->encoding($data[$key], $from, $to);
				} elseif (is_string($value)) {
					$data[$key] = iconv($from, $to, $value);
				}
			}
		}
	}

	/**
	 * Encrypted data and return Base64-encoded string.
	 *
	 * @param string $s
	 * @param string $secure_key
	 * @return string
	 */
	function encrypt($s, $secure_key) {
		if (!extension_loaded('mcrypt'))
			throw new McryptNotInstalledException('Mcrypt extension not installed.');

		if (null == $s || !is_string($s))
			return false;

		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$td_size = mcrypt_enc_get_iv_size($td);
		$iv = mcrypt_create_iv($td_size, MCRYPT_RAND);
		$key = substr($secure_key, 0, $td_size);
		mcrypt_generic_init($td, $key, $iv);
		$ret = base64_encode(mcrypt_generic($td, $s));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return $ret;
	}

	/**
	 * Decrypt the data.
	 *
	 * @param string $s
	 * @param string $secure_key
	 * @return string
	 */
	function decrypt($s, $secure_key) {
		if (!extension_loaded('mcrypt'))
			throw new McryptNotInstalledException('Mcrypt extension not installed.');

		if (null == $s)
			return false;

		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$td_size = mcrypt_enc_get_iv_size($td);
		$iv = mcrypt_create_iv($td_size, MCRYPT_RAND);
		$key = substr($secure_key, 0, $td_size);
		mcrypt_generic_init($td, $key, $iv);
		$ret = trim(mdecrypt_generic($td, base64_decode($s)));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return $ret;
	}
	
	/**
	 * JavaScript escape function.
	 *
	 * @param string $str
	 * @return string
	 */
	function escape($str) {
		preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/", $str, $newstr);
		$ar = $newstr[0];
		foreach ($ar as $k => $v) {
			if (ord($ar[$k]) >= 127) {
				$tmpString = bin2hex(iconv("GBK", "ucs-2", $v));
				if (!eregi("WIN", PHP_OS)) {
					$tmpString = substr($tmpString, 2, 2) . substr($tmpString, 0, 2);
				}
				$reString .= "%u" . $tmpString;
			} else {
				$reString .= rawurlencode($v);
			}
		}
		return $reString;
	}
	
	/**
	 * JavaScript unescape function.
	 *
	 * @param string $str
	 * @return string
	 */
	function unescape($str) {
		$str = rawurldecode($str);
		preg_match_all("/%u.{4}|&#x.{4};|&#d+;|.+/U", $str, $r);
		$ar = $r[0];
		foreach ($ar as $k => $v) {
			if (substr($v, 0, 2) == "%u")
				$ar[$k] = iconv("UCS-2", "GBK", pack("H4", substr($v, -4)));
			elseif (substr($v, 0, 3) == "&#x")
				$ar[$k] = iconv("UCS-2", "GBK", pack("H4", substr($v, 3, -1)));
			elseif (substr($v, 0, 2) == "&#") {
				$ar[$k] = iconv("UCS-2", "GBK", pack("n", substr($v, 2, -1)));
			}
		}
		return join("", $ar);
	}
	
	/**
	 * MSGPACK 扩展压缩对象为二进制。
	 *
	 * @param mixed $data
	 * @param string $filename
	 * @return mixed
	 */
	function pack($data, $filename = NULL) {
		$bResult = false;
		
		if (function_exists('msgpack_pack')) {
			if (!is_null($filename)) {
				$fp = fopen($filename, 'wb');
				if ($fp) {
					fwrite($fp, msgpack_pack($data));
					fclose($fp);
					
					$bResult = true;
				}
			} else {
				return msgpack_pack($data);
			}
		} else {
			throw new RuntimeException('`MSGPACK` extension isn\'t installed.', -1);
		}
	}
	
	/**
	 * MSGPACK 扩展解压缩二进制数据。
	 *
	 * @param mixed $data
	 * @param string $filename
	 * @return mixed
	 */
	function unpack($data, $filename = NULL) {
		if (function_exists('msgpack_unpack')) {
			if (!is_null($filename)) {
				$fp = fopen($filename, 'rb');
				if ($fp) {
					$c = fread($fp, filesize($filename));
					fclose($fp);
					
					return msgpack_unpack($c);
				}
			} else {
				if ($data) {
					return msgpack_unpack($data);
				} else {
					return false;
				}
			}
		} else {
			throw new RuntimeException('`MSGPACK` extension isn\'t installed.', -1);
		}
	}
}