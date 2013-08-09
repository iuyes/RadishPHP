<?php
if (!defined('RADISHPHP_ROOT_PATH'))
	define('RADISHPHP_ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
if (!defined('RADISHPHP_CORE_PATH'))
	define('RADISHPHP_CORE_PATH', RADISHPHP_ROOT_PATH . 'Core' . DIRECTORY_SEPARATOR);
if (!defined('RADISHPHP_EXTRA_PATH'))
	define('RADISHPHP_EXTRA_PATH', RADISHPHP_ROOT_PATH . 'Extra' . DS);

/**
 * RadishPHP framework launcher.
 *
 * @author Lei Lee
 * @version 1.0
 */
class RadishPHP {
	/**
	 * Set current version.
	 *
	 * @var string
	 */
	private $_version = '1.0';

	/**
	 * Set the character encoding.
	 *
	 * @var string
	 */
	private $_charset = 'UTF-8';

	/**
	 * Time zone.
	 *
	 * @var string
	 */
	private $_time_zone = NULL;

	/**
	 * User control directory.
	 *
	 * @var string
	 */
	private $_controller_dir = NULL;

	/**
	 * Template directory.
	 *
	 * @var string
	 */
	private $_template_dir = NULL;

	/**
	 * Data Module directory.
	 *
	 * @var unknown_type
	 */
	private $_data_module_dir = NULL;

	/**
	 * Cache options.
	 *
	 * @var array
	 */
	private $_cache_options = NULL;

	/**
	 * Cache directory.
	 *
	 * @var string
	 */
	private $_cache_dir = NULL;
	
	/**
	 * POST requests into doAction mode?
	 *
	 * @var boolean
	 */
	private $_do_action_mode = false;
	
	private $_is_json_result = false;
	
	/**
	 * Template compile directory.
	 *
	 * @var string
	 */
	private $_template_compile_dir = NULL;
	
	/**
	 * Does it enable the template to compile?
	 *
	 * @var boolean
	 */
	private $_template_compile_enabled = false;
	
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
	private $_log_max_size = 512000;

	/**
	 * Set the basic controllers.
	 *
	 * @var array
	 */
	private $_base_controllers = NULL;

	/**
	 * Set the routing variable name.
	 *
	 * @var string
	 */
	private $_route_var = 'r';

	/**
	 * Array of database connection parameters.
	 *
	 * @var array
	 */
	private $_db_params = NULL;

	/**
	 * BaseController object instance.
	 *
	 * @var BaseController
	 */
	private $_base_controller = NULL;

	/**
	 * Set the global variable name the template scope.
	 *
	 * @var string
	 */
	private $_global_tpl_vars_name = 'site';

	/**
	 * Set the template scope variables.
	 *
	 * @var array
	 */
	private $_global_tpl_vars = NULL;

	/**
	 * Global exception handler function name.
	 *
	 * @var string
	 */
	private $_exception_handler = NULL;

	/**
	 * Error handling function name.
	 *
	 * @var string
	 */
	private $_error_handler = NULL;

	/**
	 * Set Shutdown event callback function.
	 *
	 * @var string
	 */
	private $_shutdown_handler = NULL;

	/**
	 * DbPdo object instance.
	 *
	 * @var DbPdo
	 */
	public $db = NULL;

	/**
	 * FileSystem object instance.
	 *
	 * @var FileSystem
	 */
	public $fso = NULL;

	/**
	 * Cache object instance.
	 *
	 * @var Cache
	 */
	public $cache = NULL;

	/**
	 * WebClient object instance.
	 *
	 * @var WebClient
	 */
	public $http = NULL;

	/**
	 * DataBridge object instance.
	 *
	 * @var DataBridge
	 */
	public $dataBridge = NULL;

	/**
	 * DbTree object instance.
	 *
	 * @var DbTree
	 */
	public $dbTree = NULL;

	/**
	 * ImageProcessor object instance.
	 *
	 * @var IImageAdapter
	 */
	public $image = NULL;

	/**
	 * Utils object instance.
	 *
	 * @var Utils
	 */
	public $utils = NULL;

	/**
	 * Template object instance.
	 *
	 * @var Template
	 */
	public $template = NULL;
	
	/**
	 * Logger object instance.
	 *
	 * @var Logger
	 */
	public $logger = NULL;

	/**
	 * Constructor.
	 *
	 */
	function __construct() {
	}

	/**
	 * Request dispatcher.
	 *
	 */
	function dispatcher() {
		// Set the system time zone...
		if (!is_null($this->_time_zone))
			date_default_timezone_set($this->_time_zone);

		// Set exception handler...
		if (!is_null($this->_exception_handler))
			set_exception_handler($this->_exception_handler);
		else
			set_exception_handler(array($this, 'defaultExceptionHandler'));

		// Set the error handling function...
		if (!is_null($this->_error_handler))
			set_error_handler($this->_error_handler);
		else
			set_error_handler(array($this, 'defaultErrorHandler'), E_USER_ERROR);
			
		// Check the result type is JSON format?
		$this->_is_json_result = $_POST['resultType'] == 'json' ? true : ($_GET['resultType'] == 'json' ? true : false);

		// Set Shutdown event callback function...
		if (!is_null($this->_shutdown_handler)) {
			if ((!is_array($this->_shutdown_handler) && !is_string($this->_shutdown_handler))|| (is_array($this->_shutdown_handler) && !method_exists($this->_shutdown_handler[0], $this->_shutdown_handler[1])) || (is_string($this->_shutdown_handler) && !function_exists($this->_shutdown_handler)))
				throw new RuntimeException('Shutdown event handler method does not exist.', -1);
			register_shutdown_function($this->_shutdown_handler);
		}

		// Loading custom exception classes...
		include (RADISHPHP_ROOT_PATH . 'Exception/IOException.class.php');
		include (RADISHPHP_ROOT_PATH . 'Exception/RuntimeException.class.php');
		include (RADISHPHP_ROOT_PATH . 'Exception/NetException.class.php');

		// Loading route resolver...
		include (RADISHPHP_ROOT_PATH . 'Core/Router.class.php');
		include (RADISHPHP_ROOT_PATH . 'Core/BaseController.php');

		// Analysis of URL address...
		$dRouterResult = Router::analyze($this);

		$c_filename = $this->_controller_dir
					. DIRECTORY_SEPARATOR . $dRouterResult['Module']
					. DIRECTORY_SEPARATOR . str_replace('Controller', '', $dRouterResult['Controller']) . '.class.php';
		$c_filename = Router::standardize($c_filename);

		if (!file_exists($c_filename))
			throw new FileNotFoundException('File does not exist.(File: ' . $c_filename . ')', -1);

		// Loading user controller class files...
		$Controller = $dRouterResult['Controller'];
		$doAction   = $dRouterResult['Action'];

		include (RADISHPHP_ROOT_PATH . 'Interface/IController.class.php');
		include (RADISHPHP_ROOT_PATH . 'Interface/ICacheAdapter.class.php');
		include (RADISHPHP_ROOT_PATH . 'Interface/IImageAdapter.class.php');
		include (RADISHPHP_ROOT_PATH . 'Interface/IDataModel.class.php');

		// Load a custom base controller...
		if (is_string($this->_base_controllers)) {
			$d_controller_result = Router::analyze($this, $this->_base_controllers);
			$base_controller_file = rtrim($this->_controller_dir, '/\\') . (!empty($d_controller_result['Module']) ? DIRECTORY_SEPARATOR . $d_controller_result['Module'] : '') . DIRECTORY_SEPARATOR . str_replace('Controller', '', $d_controller_result['Controller']) . '.class.php';
			if (is_file($base_controller_file)) {
				include ($base_controller_file);
			}
		} elseif (is_array($this->_base_controllers)) {
			foreach ($this->_base_controllers as $vPath) {
				$d_controller_result = Router::analyze($this, $vPath);
				$base_controller_file = rtrim($this->_controller_dir, '/\\') . (!empty($d_controller_result['Module']) ? DIRECTORY_SEPARATOR . $d_controller_result['Module'] : '') . DIRECTORY_SEPARATOR . str_replace('Controller', '', $d_controller_result['Controller']) . '.class.php';
				if (is_file($base_controller_file)) {
					include ($base_controller_file);
				}
			}
		}

		// Dynamic initialization controller, and call the members of a specified method...
		include ($c_filename);
		if (class_exists($Controller)) 
			$this->_base_controller = new $Controller($this);
		else 
			throw new RuntimeException('The controller `' . $Controller . '` does not exists!');

		if (!is_a($this->_base_controller, 'IController'))
			throw new RuntimeException(get_class($this->_base_controller) . ' must inherit IController Interface.', -1);

		if (!is_subclass_of($this->_base_controller, 'BaseController'))
			throw new RuntimeException(get_class($this->_base_controller) . ' is not a subclass of BaseController objects.', -1);

		if (!method_exists($this->_base_controller, 'instance'))
			throw new RuntimeException('The method `BaseController::setScope()` undefined.', -1);

		// Check the switch setting magic_quotes_gpc quotes are escaped to prevent ...
		if (get_magic_quotes_gpc()) {
			$this->deslashes($_POST);
			$this->deslashes($_GET);
		}

		include (RADISHPHP_CORE_PATH . 'SystemInformation.class.php');
		include (RADISHPHP_CORE_PATH . 'WebClient.class.php');
		include (RADISHPHP_CORE_PATH . 'Template.class.php');
		include (RADISHPHP_CORE_PATH . 'DbPdo.class.php');
		include (RADISHPHP_CORE_PATH . 'DataBridge.class.php');
		include (RADISHPHP_CORE_PATH . 'FileSystem.class.php');
		include (RADISHPHP_CORE_PATH . 'DataSerializer.class.php');
		include (RADISHPHP_CORE_PATH . 'DbTree.class.php');
		include (RADISHPHP_CORE_PATH . 'Cache.class.php');
		include (RADISHPHP_CORE_PATH . 'Utils.class.php');
		include (RADISHPHP_CORE_PATH . 'Logger.class.php');

		// Initialize the SystemInformation instance ...
		$obSystemInfo = new SystemInformation();
		$obSystemInfo->setVersion($this->_version)
					 ->setCharset($this->_charset);

		// Initialize the DataBridge / Utils object ...
		$this->db         = new DbPdo($this);
		$this->dataBridge = new DataBridge($this);
		$this->utils      = new Utils($this);
		$this->dbTree     = new DbTree($this);
		$this->cache      = new Cache($this);
		$this->http       = new WebClient($this);
		$this->template   = new Template($this);
		$this->fso        = new FileSystem($this);
		$this->logger     = new Logger($this);
		
		$this->logger->setDir($this->_log_dir)
					 ->setMaxFileSize($this->_log_max_size);
		
		// 检测 imagick 扩展是否安装。若已安装，则优先使用 imagick 扩展处理图片...
		if (extension_loaded('imagick')) {
			include (RADISHPHP_CORE_PATH . 'Magick.class.php');
			$this->image = new Magick($this);
		} else {
			include (RADISHPHP_CORE_PATH . 'GD.class.php');
			$this->image = new GD($this);
		}

		if (!empty($this->_global_tpl_vars))
			$this->template->assign($this->_global_tpl_vars_name, $this->_global_tpl_vars);

		// Initialize the PDO database object ...
		if (is_array($this->_db_params))
			$this->db->addDbParams($this->_db_params);

		$this->_base_controller->instance($this)->setSystemInformation($obSystemInfo);

		// If there are members of the controller method "initialize", the first call this method ...
		if (method_exists($this->_base_controller, 'initialize'))
			call_user_func(array($this->_base_controller, 'initialize'));

		// Triggering behavioral events ...
		if (true === $this->_do_action_mode && 0 == strcmp('POST', $_SERVER['REQUEST_METHOD'])) 
			$doAction = 'do' . Router::toPascal($doAction);
		
		if (!method_exists($this->_base_controller, $doAction)) 
			throw new ClassMethodNotFoundException('Class method does not exist.(' . $Controller . '::' . $doAction . ')', -1);
		
		call_user_func(array($this->_base_controller, $doAction));

		// Call the global controller object method of destruction ...
		register_shutdown_function(array($this, 'destroy'));
	}
	
	/**
	 * Destroy the database connection and other objects.
	 *
	 */
	function destroy() {
		if (!is_null($this->db)) {
			$this->db->close();
			$this->db         = NULL;
		}
		if (!is_null($this->dataBridge)) 
			$this->dataBridge = NULL;
		if (!is_null($this->utils)) 
			$this->utils      = NULL;
		if (!is_null($this->image)) 
			$this->image      = NULL;
		if (!is_null($this->dbTree)) 
			$this->dbTree     = NULL;
		if (!is_null($this->cache)) 
			$this->cache      = NULL;
		if (!is_null($this->http)) 
			$this->http       = NULL;
		if (!is_null($this->template)) 
			$this->template   = NULL;
		if (!is_null($this->fso)) 
			$this->fso        = NULL;
	}

	/**
	 * Filter out the string escape character.
	 *
	 * @param array $data
	 */
	function deslashes(&$data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$this->deslashes($data[$key]);
				} elseif (is_string($value)) {
					$data[$key] = stripslashes($value);
				}
			}
		} elseif (is_string($data)) {
			$data = stripslashes($data);
		}
	}

	/**
	 * Dynamically loaded extension class contains.
	 *
	 * @param string $extraClassName
	 */
	function dc($className) {
		include (RADISHPHP_CORE_PATH . $className . '.class.php');
	}

	/**
	 * Dynamically loaded extension class contains.
	 *
	 * @param string $className
	 */
	function de($className) {
		include (RADISHPHP_EXTRA_PATH . $className . '.class.php');
	}

	/**
	 * Dynamic load data model class.
	 *
	 * @param string $dataModelName
	 */
	function dm($dataModelName) {
		$file = $this->getDataModuleDir() . DIRECTORY_SEPARATOR . $dataModelName . 'Model.class.php';
		if (is_file($file))
			include ($file);
		else
			throw new RuntimeException('The model file does not exist. (' . $file . ')');
	}

	/**
	 * The default error handler.
	 *
	 * @param int $err_no
	 * @param string $err_str
	 * @param string $err_file
	 * @param int $err_line
	 * @param array $err_ctx
	 */
	function defaultErrorHandler($err_no, $err_str, $err_file, $err_line) {
		throw new ErrorException($err_str, 0, $err_no, $err_file, $err_line);
	}

	/**
	 * The default exception handler.
	 *
	 * @param Exception $exception
	 */
	function defaultExceptionHandler($exception) {
		if ($this->_is_json_result) {
			$errs = array(
				'code' => $exception->getCode(), 
				'description' => $exception->getMessage(), 
				'exception' => array(
					'file' => $exception->getFile(), 
					'line' => $exception->getLine(), 
					'message' => $exception->getMessage()
				)
			);
			
			echo json_encode($errs);
		} else {
			$trace = nl2br($exception->__toString());
			$html = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Exception Found!</title>
	<style type="text/css">
	/*<![CDATA[*/
	html, body { color: #333; }
	h1 { font-family: Arial, Verdana, Tahoma; font-size: 2.5em; }
	.color_1 { color: Red; }
	.color_2 { color: Blue; }
	.color_3 { color: Gray; }
	#container { margin: 12px; }
	#errors { border: 1px solid #FFF; background: #FFF; padding: 6px; font-family: SimSun, Tahoma, Arial; font-size: 12px; }
	#copyright { margin-top: 12px; padding-top: 12px; border-top: 1px solid #A2A2A2; font-style: italic; }
	#copyright a { color: Blue; }
	/*]]>*/
	</style>
</head>
<body>
<div id="container">
	<h1>Exception Found!</h1>
	<div id="errors">
		[Code]:&nbsp;&nbsp;&nbsp; <span class="color_1">{$exception->getCode()}</span><br />
		[File]:&nbsp;&nbsp;&nbsp; {$exception->getFile()}(Line: {$exception->getLine()})<br />
		[Message]: <span class="color_1">{$exception->getMessage()}</span><br />
		[Trace]:&nbsp;&nbsp; {$trace}
	</div>
	<div id="copyright">RadishPHP Framework v{$this->_version} version. Powered by <a href="http://www.iooogle.com/" target="_blank">iooogle.com</a></div>
</div>
</body>
</html>
EOT;
			echo $html;
		}
		exit(0);
	}

	/**
	 * Set the database connection parameters.
	 *
	 * @param array $dbParams
	 * @return RadishPHP
	 */
	function setDbParams(&$dbParams) {
		$this->_db_params = &$dbParams;
		return $this;
	}

	/**
	 * Set the global exception handler.
	 *
	 * @param string $exception_handler
	 * @return RadishPHP
	 */
	function setExceptionHandler($exception_handler) {
		$this->_exception_handler = $exception_handler;
		return $this;
	}

	/**
	 * Set the error handling function.
	 *
	 * @param string $error_handler
	 * @return RadishPHP
	 */
	function setErrorHandler($error_handler) {
		$this->_error_handler = $error_handler;
		return $this;
	}

	/**
	 * Set Shutdown event callback function.
	 *
	 * @param string $shutdown_handler
	 * @return RadishPHP
	 */
	function setShutdownHanlder($shutdown_handler) {
		$this->_shutdown_handler = $shutdown_handler;
		return $this;
	}

	/**
	 * Set the template files directory.
	 *
	 * @param string $template_dir
	 * @return RadishPHP
	 */
	function setTemplateDir($template_dir) {
		$this->_template_dir = rtrim($template_dir, '\\/') . DIRECTORY_SEPARATOR;
		return $this;
	}
	
	/**
	 * Get the template files directory.
	 *
	 * @return string
	 */
	function getTemplateDir() {
		return $this->_template_dir;
	}

	/**
	 * Set the controller stored directory.
	 *
	 * @param string $controller_dir
	 * @return RadishPHP
	 */
	function setControllerDir($controller_dir) {
		$this->_controller_dir = $controller_dir;
		return $this;
	}

	/**
	 * Set the data module directory.
	 *
	 * @param string $data_module_dir
	 * @return RadishPHP
	 */
	function setDataModuleDir($data_module_dir) {
		$this->_data_module_dir = $data_module_dir;
		return $this;
	}

	/**
	 * Set the cache directory.
	 *
	 * @param string $cache_dir
	 * @return RadishPHP
	 */
	function setCacheDir($cache_dir) {
		$this->_cache_dir = $cache_dir;
		return $this;
	}

	/**
	 * Set the time zone.
	 *
	 * @param string $time_zone
	 * @return RadishPHP
	 */
	function setTimeZone($time_zone) {
		$this->_time_zone = $time_zone;
		return $this;
	}

	/**
	 * Set the template scope variables.
	 *
	 * @param string $var_name
	 * @param array $var
	 * @return RadishPHP
	 */
	function setTemplateScopeVar($var_name, &$var) {
		$this->_global_tpl_vars_name = $var_name;
		$this->_global_tpl_vars = &$var;
		return $this;
	}

	/**
	 * Set the basic controllers.
	 *
	 * @param array $controllers
	 * @return RadishPHP
	 */
	function setBaseController($controllers) {
		$this->_base_controllers = $controllers;
		return $this;
	}

	/**
	 * Get the controller stored directory.
	 *
	 * @return string
	 */
	function getControllerDir() {
		return $this->_controller_dir;
	}

	/**
	 * Get the data module directory.
	 *
	 * @return string
	 */
	function getDataModuleDir() {
		return $this->_data_module_dir;
	}

	/**
	 * Set the routing variable name.
	 *
	 * @param string $route_var
	 * @return RadishPHP
	 */
	function setRouteVar($route_var) {
		$this->_route_var = $route_var;
		return $this;
	}

	/**
	 * Get the routing variable name.
	 *
	 * @return string
	 */
	function getRouteVar() {
		return $this->_route_var;
	}
	
	/**
	 * Indicates the type of result is JSON format?
	 *
	 * @return boolean
	 */
	function isJsonResultType() {
		return $this->_is_json_result;
	}
	
	/**
	 * Set the log file storage directory.
	 *
	 * @param string $value
	 * @return RadishPHP
	 */
	function setLogDir($value) {
		$this->_log_dir = $value;
		
		return $this;
	}
	
	/**
	 * Set a single log file size limit.
	 *
	 * @param int $value
	 * @return RadishPHP
	 */
	function setLogFileSize($value) {
		$this->_log_max_size = $value;
		
		return $this;
	}

	/**
	 * Set the cache options.
	 *
	 * @param array $options
	 * @return RadishPHP
	 */
	function setCacheOptions($options) {
		$this->_cache_options = $options;
		return $this;
	}

	/**
	 * Set the character encoding.
	 *
	 * @param string $charset
	 * @return RadishPHP
	 */
	function setCharset($charset) {
		$this->_charset = $charset;
		return $this;
	}

	/**
	 * Get the cache options.
	 *
	 * @return array
	 */
	function getCacheOptions() {
		return $this->_cache_options;
	}

	/**
	 * Set template compile directory.
	 *
	 * @param string $compile_dir
	 * @return RadishPHP
	 */
	function setCompileDir($compile_dir) {
		$this->_template_compile_dir = rtrim($compile_dir, '\\/') . DIRECTORY_SEPARATOR;
		$this->_template_compile_enabled = true;
		return $this;
	}
	
	/**
	 * Get template compile directory.
	 *
	 * @return string
	 */
	function getCompileDir() {
		return $this->_template_compile_dir;
	}
	
	/**
	 * Set whether to allow the compiled template.
	 *
	 * @param boolean $enabled
	 * @return RadishPHP
	 */
	function setCompileEnabled($enabled) {
		$this->_template_compile_enabled = $enabled;
		return $this;
	}
	
	/**
	 * Get whether to allow the compiled template.
	 *
	 * @return boolean
	 */
	function getCompileEnabled() {
		return $this->_template_compile_enabled;
	}
	
	/**
	 * Open to the POST request for doAction mode.
	 *
	 * @return RadishPHP
	 */
	function setDoActionMode() {
		$this->_do_action_mode = true;
		return $this;
	}
	
	/**
	 * Get the DbPdo instance.
	 *
	 * @return DbPdo
	 */
	function getDb() {
		return $this->_db;
	}

	/**
	 * Get the BaseController instance.
	 *
	 * @return BaseController
	 */
	function getBaseController() {
		return $this->_base_controller;
	}
}