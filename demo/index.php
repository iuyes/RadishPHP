<?php
define('DS', DIRECTORY_SEPARATOR);
define('SITE_ROOT', dirname(__FILE__) . DS);

//error_reporting(E_ALL);

set_include_path(get_include_path() . PATH_SEPARATOR . SITE_ROOT . '../RadishPHP/');

include ('include/config.php');
include ('RadishPHP.php');

$disp = new RadishPHP();
$disp->setTimeZone('Asia/Chongqing')
	 ->setTemplateDir(SITE_ROOT . 'templates')
	 ->setCompileDir(SITE_ROOT . 'templates_c')
	 ->setControllerDir(SITE_ROOT . 'controller')
	 ->setCacheDir(SITE_ROOT . 'cache')
	 ->setBaseController(array('console.base', 'memberBase'))
	 ->setRouteVar('do')
//	 ->setErrorHandler('global_error_handler')
//	 ->setExceptionHandler('global_exception_handler')
	 ->setDbParams($cfgs['db'])
	 ->setCacheOptions($cfgs['cache'])
	 ->setTemplateScopeVar('site', $cfgs['site'])
	 ->dispatcher();

/**
 * Global error handling function.
 *
 * @param int $err_no
 * @param string $err_str
 * @param string $err_file
 * @param int $err_line
 * @param array $err_ctx
 */
function global_error_handler($err_no, $err_str, $err_file, $err_line, $err_ctx) {
	die('errors!');
}

/**
 * Global exception handler.
 *
 * @param Exception $exception
 */
function global_exception_handler($exception) {
	die('Exception!');
}