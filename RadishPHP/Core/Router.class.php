<?php
/**
 * URL address of the routing.
 *
 * @author Lei Lee
 */
class Router {
	/**
	 * Analysis of the request address.
	 *
	 * @param RadishPHP $core
	 * @param string $sPath
	 * @return array
	 */
	static function analyze($core, $sPath = NULL) {
		if (!is_null($sPath)) {
			$sr = $sPath;
		} else {
			$route_var_name = $core->getRouteVar();
			$sr = isset($_GET[$route_var_name]) ? trim($_GET[$route_var_name]) : '';
		}
		$ar = array();
		$size = 0;
		if (0 < strlen($sr)) {
			$ar   = explode('.', $sr);
			$size = count($ar);
		}
		
		if (0 > $size || $size > 3) 
			throw new InvalidPathException('Using an invalid URL path.', -1);
		
		$d = array(
			'Module' => '', 'Controller' => 'IndexController', 'Action' => 'index'
		);
		
		switch ($size) {
			case 1:
				$d['Controller'] = self::toPascal($ar[0]) . 'Controller';
				break;
			case 2:
				$ctrl_dir = $core->getControllerDir() . DIRECTORY_SEPARATOR . self::toPascal($ar[0]);
				if (file_exists($ctrl_dir) && is_dir($ctrl_dir)) {
					// If the controller directory exists, in accordance with sub-module handles ...
					$d['Controller'] = self::toPascal($ar[0]) . '_' . self::toPascal($ar[1]) . 'Controller';
					$d['Module']     = self::toPascal($ar[0]);
				} else {
					// The controller handling the module by default ...
					$d['Controller'] = self::toPascal($ar[0]) . 'Controller';
					$d['Action'] = self::toCamel($ar[1]);
				}
				break;
			case 3:
				$d['Controller'] = self::toPascal($ar[0]) . '_' . self::toPascal($ar[1]) . 'Controller';
				$d['Module']     = self::toPascal($ar[0]);
				$d['Action']     = self::toCamel($ar[2]);
				break;
		}
		
		unset($size, $ar, $sr, $route_var_name);
		
		return $d;
	}
	
	/**
	 * Convert a string to Pascal naming specification.
	 *
	 * @param string $str
	 * @return string
	 */
	static function toPascal($str) {
		$str = str_replace(array('-', ' '), array('', ''), $str);
		$ss  = explode('_', $str);
		
		$str = '';
		if (is_array($ss)) {
			foreach ($ss as $v) {
				$str .= strtoupper($v[0]) . substr($v, 1);
			}
			return $str;
		}
	}
	
	/**
	 * To convert a string naming the Camel.
	 *
	 * @param string $str
	 * @return string
	 */
	static function toCamel($str) {
		if (false === function_exists('lcfirst')) {
			/**
		     * Make a string's first character lowercase
		     *
		     * @param string $str
		     * @return string the resulting string.
		     */
		    function lcfirst($s) {
		        $s[0] = strtolower($s[0]);
		        return ( string ) $s;
		    }
		}
		
		$str = str_replace(array('-', ' '), array('', ''), $str);
		
		if (strpos($str, '_') !== false) {
			$a = explode('_', $str);
			$i = 0; $b = array();
			foreach ($a as $v) {
				if ($i == 0) $b[] = lcfirst($v);
				else $b[] = ucwords($v);
				$i++;
			}
			
			return implode('', $b);
		} else {
			return lcfirst($str);
		}
	}
	
	/**
	 * Standardized absolute path.
	 *
	 * @param string $absPath
	 * @return string
	 */
	static function standardize($absPath) {
		return preg_replace('/[\/\\\]+/', DIRECTORY_SEPARATOR, $absPath);
	}
}