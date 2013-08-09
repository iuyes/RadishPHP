<?php
/**
 * Utils Class Object.
 *
 * @author Lei Lee
 * @version 1.0
 */
class Utils {
	/**
	 * The object instance of RadishPHP.
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
	 * Get the number of bytes localized string expression.
	 *
	 * @param int $size The number of bytes occupied by the file.
	 * @return string
	 */
	function size($size) {
		$s = "0 Bytes";

        if ($size > 0) {
            $size_names = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");

            // $i = ( int ) floor(log($size, 1024));
            $i = floor(log($size, 1024));
            $s = strval(round($size / pow(1024, $i), 2)) . $size_names[$i];
        }
        return $s;
	}
}