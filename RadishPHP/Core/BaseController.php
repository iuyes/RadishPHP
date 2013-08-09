<?php
/**
 * RadishPHP based controller object.
 *
 * @author Lei Lee
 * @version 1.0
 */
class BaseController {
	/**
	 * Reference Template object.
	 *
	 * @var Template
	 */
	protected $template = NULL;

	/**
	 * Reference DbPdo object.
	 *
	 * @var DbPdo
	 */
	protected $db = NULL;

	/**
	 * Reference DbTree object.
	 *
	 * @var DbTree
	 */
	protected $dbTree = NULL;

	/**
	 * Reference Cache object.
	 *
	 * @var Cache
	 */
	protected $cache = NULL;

	/**
	 * Reference WebClient object.
	 *
	 * @var WebClient
	 */
	protected $http = NULL;

	/**
	 * Reference ImageProcessor object.
	 *
	 * @var IImageAdapter
	 */
	protected $image = NULL;

	/**
	 * Reference FileSystem object.
	 *
	 * @var FileSystem
	 */
	protected $fso = NULL;

	/**
	 * Reference Utils object.
	 *
	 * @var Utils
	 */
	protected $utils = NULL;

	/**
	 * Reference DataBridge object.
	 *
	 * @var DataBridge
	 */
	protected $dataBridge = NULL;

	/**
	 * Reference SystemInformation object.
	 *
	 * @var SystemInformation
	 */
	protected $system = NULL;
	
	/**
	 * Reference Logger object.
	 *
	 * @var Logger
	 */
	protected $logger = NULL;

	/**
	 * RadishPHP object.
	 *
	 * @var RadishPHP
	 */
	protected $scope = NULL;

	/**
	 * Whether the POST request method?
	 *
	 * @var boolean
	 */
	protected $isPost = false;

	/**
	 * The current system timestamp.
	 *
	 * @var int
	 */
	protected $currentTime = 0;

	/**
	 * Constructor.
	 *
	 */
	function __construct() {
		$this->isPost = (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0 ? true : false);
		$this->currentTime = time();
	}

	/**
	 * Template object assignment.
	 *
	 * @param string $var
	 * @param mixed $data
	 */
	function assign($var, $data) {
		if ($this->scope->template == NULL)
			throw new RuntimeException('Template object does not exist or has not been instantiated.', -1);

		$this->scope->template->assign($var, $data);
	}

	/**
	 * Print the template file.
	 *
	 * @param string $tpl_file
	 * @param boolean $tpl_auto_suffix
	 * @param boolean $exit
	 * @param array $extra_data
	 */
	function display($tpl_file, $tpl_auto_suffix = true, $exit = false, $extra_data = NULL) {
		if ($this->scope->template == NULL)
			throw new RuntimeException('Template object does not exist or has not been instantiated.', -1);

		$this->scope->template->assign('sys', array(
			'do'      => $this->scope->getRouteVar(),
			'doValue' => $_GET[$this->scope->getRouteVar()] ? $_GET[$this->scope->getRouteVar()] : 'index',
			'gets'    => &$_GET,
			'forms'   => &$_POST,
			'envs'    => &$_SERVER,
			'uri'     => $_SERVER['REQUEST_URI'], 
			'referer' => rawurlencode($_SERVER['HTTP_REFERER'])
		));

		$this->scope->template->display($tpl_file . ($tpl_auto_suffix ? '.tpl.php' : ''), $exit, $extra_data);
	}

	/**
	 * Redirected to the specified module page.
	 * Note: Allowed to pass three parameters, or just pass in a dot-separated string.
	 * -------------------------------------------------------------------------------
	 * eg: 1 => goto($module, $controller, $action)
	 *     2 => goto('$module.$controller.$action');
	 * -------------------------------------------------------------------------------
	 */
	function go() {
		$r = '';
		$suffix = '';
		$args_size = func_num_args();

		if (0 == $args_size) {
			header('Location: ' . $this->scope->dataBridge->envs('HTTP_REFERER'));
		} else {
			if ($args_size == 3 || $args_size == 2) {
				$args = func_get_args();
				$r = implode('.', $args);
			} elseif ($args_size == 1) {
				$r = func_get_arg(0);
				if (is_int($r)) {
					switch ($r) {
						case 404 :
							header('HTTP/1.1 404 Not Found');
							exit(0);
						case 401 :
							header('HTTP/1.1 401 Unauthorized');
							exit(0);
					}
				} elseif (is_string($r)) {
					header('Location: ' . $r);
					exit(0);
				}
			}

			header('Location: ?' . $this->scope->getRouteVar() . '=' . $r . $suffix);
		}
		exit(0);
	}

	/**
	 * Set the RadishPHP instance.
	 *
	 * @param RadishPHP $scope
	 * @return BaseController
	 */
	function instance(&$scope) {
		$this->scope      = &$scope;
		$this->template   = &$this->scope->template;
		$this->cache      = &$this->scope->cache;
		$this->dataBridge = &$this->scope->dataBridge;
		$this->db         = &$this->scope->db;
		$this->dbTree     = &$this->scope->dbTree;
		$this->http       = &$this->scope->http;
		$this->image      = &$this->scope->image;
		$this->utils      = &$this->scope->utils;
		$this->fso        = &$this->scope->fso;
		$this->logger     = &$this->scope->logger;

		return $this;
	}

	/**
	 * Set the SystemInformation instance.
	 *
	 * @param SystemInformation $v
	 * @return BaseController
	 */
	function setSystemInformation(&$v) {
		$this->system = &$v;
		return $this;
	}

	/**
	 * Get the SystemInformation instance.
	 *
	 * @return SystemInformation
	 */
	function getSystemInformation() {
		return $this->system;
	}
}