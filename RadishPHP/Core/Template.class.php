<?php
/**
 * Simple template class object.
 *
 * @author Lei Lee
 */
class Template {
	/**
	 * Reference Utils object.
	 *
	 * @var Utils
	 */
	private $utils = NULL;

	/**
	 * Reference DataBridge object.
	 *
	 * @var DataBridge
	 */
	private $dataBridge = NULL;

	/**
	 * RadishPHP object instance.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;
	
	/**
	 * IController object instance.
	 *
	 * @var IController
	 */
	private $cls = NULL;

	/**
	 * Template data set.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Constructor.
	 *
	 * @param RadishPHP $scope
	 */
	function __construct(&$scope) {
		$this->scope      = &$scope;
		$this->cls        = &$this->scope->getBaseController();
		$this->dataBridge = &$this->scope->dataBridge;
		$this->utils      = &$this->scope->utils;
	}

	/**
	 * Assign values to template variables.
	 *
	 * @param string $var
	 * @param mixed $data
	 */
	function assign($var, $data) {
		$this->data[$var] = $data;
	}
	
	/**
	 * Get the specified data.
	 *
	 * @param string $var
	 * @return mixed
	 */
	function v($var) {
		return $this->data[$var];
	}

	/**
	 * Print the template file.
	 *
	 * @param string $tpl_file
	 * @param boolean $exit
	 * @param array $extra_data
	 */
	function display($tpl_file, $exit = false, $extra_data = NULL) {
		$tpl_file = Router::standardize($this->scope->getTemplateDir() . $tpl_file);

		if (!file_exists($tpl_file))
			throw new FileNotFoundException('Template file does not exist.(File: ' . $tpl_file . ')', -1);

		if (is_array($extra_data)) {
			$this->data = array_merge($this->data, $extra_data);
		}
		
		if (true === $this->scope->getCompileEnabled()) {
			include_once (RADISHPHP_CORE_PATH . 'TemplateCompiler.class.php');
			
			$tpl_file = TemplateCompiler::instance($this->scope)->analyze($tpl_file);
			
			extract($this->data);
			unset($this->data);
		}
		
		include ($tpl_file);

		if ($exit) 
			exit(0);
	}
	
	/**
	 * Clear all cache files.
	 *
	 */
	function clean() {
		$this->scope->fso->deleteRecursive($this->scope->getCompileDir());
	}
}