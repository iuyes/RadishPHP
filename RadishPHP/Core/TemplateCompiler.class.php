<?php
/**
 * The template compiler class.
 *
 * @author Lei Lee
 * @version 1.0
 */
class TemplateCompiler {
	/**
	 * The path to the template directory.
	 *
	 * @var string
	 */
	static public $tpl_dir = NULL;
	
	/**
	 * The path to the compile directory.
	 *
	 * @var string
	 */
	static public $tpl_compile_dir = NULL;
	
	/**
	 * RadishPHP object instance.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;
	
	/**
	 * Set the template variables left qualifier.
	 *
	 * @var string
	 */
	private $left_qualifier = '<!--{';
	
	/**
	 * Set the template variables right qualifier.
	 *
	 * @var string
	 */
	private $right_qualifier = '}-->';
	
	/**
	 * Constructor.
	 *
	 * @param RadishPHP $scope
	 */
	function __construct(&$scope) {
		$this->scope = &$scope;
		
		self::$tpl_compile_dir = $this->scope->getCompileDir();
		self::$tpl_dir         = $this->scope->getTemplateDir();
	}
	
	/**
	 * Analyzer.
	 *
	 */
	function analyze($tpl_file) {
		if (!is_file($tpl_file)) 
			throw new FileNotFoundException('The template file does not exist.(' . $tpl_file . ')', -1);
		
		$hash = md5($tpl_file) . '_c.php';
		
		if (false == $this->examine($tpl_file)) {
			return $this->scope->getCompileDir() . $hash;
		}
		
		$code = file_get_contents($tpl_file);
		
		// Include / deal with the Require file.
		if (preg_match_all('<!--{(include|require)[ \t]+file[ \t]*=[ \t]*[\'"]?(.[^\'"]+)[\'"]?}-->', $code, $matches, PREG_PATTERN_ORDER)) {
			if (is_array($matches[2])) {
				foreach ($matches[2] as $v) {
					$this->analyze(self::$tpl_dir . $v);
				}
			}
		}
		
		$exPatterns = array();
		$exReplaces = array();
		
		$exPatterns[] = 'foreach[ \t]+\$([a-z0-9_]+)[ \t]+\$([a-z0-9_]+)[ \t]+\$([a-z0-9_]+)';
		$exPatterns[] = 'foreach[ \t]+\$([a-z0-9_]+)[ \t]+\$([a-z0-9_]+)';
		$exPatterns[] = '\/(foreach|iterate|for)';
		$exPatterns[] = 'if[ \t]+(.[^\{\}]+)';
		$exPatterns[] = 'elseif[ \t]+(.[^\{\}]+)';
		$exPatterns[] = 'else';
		$exPatterns[] = '\/(if|for)';
		$exPatterns[] = 'for[ \t]+(.[^\{\}]+)';
		$exPatterns[] = 'iterate[ \t]+\$([a-z0-9_\.]+)[ \t]+\$([a-z0-9_]+)[ \t]+\$([a-z0-9_]+)';
		$exPatterns[] = 'iterate[ \t]+\$([a-z0-9_\.]+)[ \t]+\$([a-z0-9_]+)';
		$exPatterns[] = 'date[ \t]+(.[^ \s\t]+)[ \t]+[\'"]+([a-z\-: ]+)[\'"]+';
		
		$exReplaces[] = '<?php if (is_array($\\1)) { foreach ($\\1 as $\\2 => $\\3) { ?>';
		$exReplaces[] = '<?php if (is_array($\\1)) { foreach ($\\1 as $\\2) { ?>';
		$exReplaces[] = '<?php }} ?>';
		$exReplaces[] = '<?php if (\\1) { ?>';
		$exReplaces[] = '<?php } elseif (\\1) { ?>';
		$exReplaces[] = '<?php } else { ?>';
		$exReplaces[] = '<?php } ?>';
		$exReplaces[] = '<?php for (\\1) { ?>';
		$exReplaces[] = '<?php if (is_array($\\1)) { foreach ($\\1 as $\\2 => $\\3) { ?>';
		$exReplaces[] = '<?php if (is_array($\\1)) { foreach ($\\1 as $\\2) { ?>';
		$exReplaces[] = '<?php echo date(\'\\2\', \\1); ?>';
		
		$this->combine($exPatterns);
		
		$code = preg_replace($exPatterns, $exReplaces, $code);
		
		// To parse a variable ...
		if (preg_match_all('/[ \t]*' . $this->left_qualifier . '(\$[a-z0-9_]+(?:\.[a-z0-9_]+)*)' . $this->right_qualifier . '/i', $code, $vars, PREG_PATTERN_ORDER)) {
			if (is_array($vars)) {
				foreach ($vars[0] as $k => $v) {
					$code = str_replace($v, '<?php echo ' . self::standardize($vars[1][$k], 1) . '; ?>', $code);
				}
			}
		}
		
		$code = preg_replace_callback('/' . $this->left_qualifier . '(include|require)[ \t]+file[ \t]*=[ \t]*[\'"]?(.[^\'"]+)[\'"]?' . $this->right_qualifier . '/i', create_function('$matches', 'return \'<?php \' . $matches[1] . \' ("\' . TemplateCompiler::standardize($matches[2], 3) . \'_c.php"); ?>\';'), $code);
		/* $code = preg_replace_callback('/' . $this->left_qualifier . 'echo[ \t]+\$([a-z0-9_\.]+);?' . $this->right_qualifier . '/i', create_function('$matches', 'return \'<?php echo \' . TemplateCompiler::standardize($matches[1], 2) . \';?>\';'), $code); */
		$code = preg_replace_callback('/' . $this->left_qualifier . 'eval[ \t]+(.[^<>]+?)' . $this->right_qualifier . '/i', create_function('$matches', 'return \'<?php \' . $matches[1] . \'?>\';'), $code);
		$code = preg_replace_callback('/(\$[a-z0-9_]+(\.[a-z0-9_\.]+)+)/i', create_function('$matches', 'return TemplateCompiler::standardize($matches[1], 1);'), $code);
		
		$file = $this->scope->getCompileDir() . $hash;
		
		$fp = fopen($file, 'w');
		if ($fp) {
			fwrite($fp, "<?php /* -- * -- RadishPHP Template Engine (v1.0) -- * -- */ ?>\r\n" . $code);
			fclose($fp);
			
			if (false == touch($file, filemtime($tpl_file))) 
				throw new FilePermissionsException('Modify the compiled file "last modified" when an error occurs.', -1);
			
			return $this->scope->getCompileDir() . $hash;
		} else {
			throw new FilePermissionsException('An exception occurred when writing templates compiled file.', -1);
		}
	}
	
	/**
	 * Merge regular expression matching pattern.
	 *
	 * @param array $exPatterns
	 */
	function combine(&$exPatterns) {
		foreach ($exPatterns as $i => $value) {
			$exPatterns[$i] = '/(?:[ \t]*)' . $this->left_qualifier . $value . $this->right_qualifier . '/i';
		}
	}
	
	/**
	 * Check whether the template file need to be recompiled.
	 *
	 * @param string $tpl_file
	 * @return boolean
	 */
	function examine($tpl_file) {
		if (!is_file($tpl_file)) 
			throw new FileNotFoundException('The template file does not exist.(' . $tpl_file . ')', -1);
		
		$hash = md5($tpl_file) . '_c.php';
		$file = $this->scope->getCompileDir() . $hash;
		
		$is_recompiled = false;
		if (!is_file($file)) 
			$is_recompiled = true;
		elseif (filemtime($file) != filemtime($tpl_file)) 
			$is_recompiled = true;
		
		return $is_recompiled;
	}
	
	/**
	 * Variable string separated by the dot notation in the form of standard PHP variables.
	 *
	 * @param string $a
	 * @return string
	 */
	static function standardize($a, $mode = 1) {
		if (!is_string($a)) 
			return '';
		
		switch ($mode) {
			case 1:
				if (false == strpos($a, '.')) {
					return $a;
				} else {
					$b = explode('.', $a);
					$c = array();
					foreach ($b as $i => $v) {
						if ($i > 1) $c[] = '\'][\'';
						elseif ($i > 0) $c[] = '[\'';
						$c[] = $v;
					}
					$c[] = '\']';
					
					return implode('', $c);
				}
			case 2:
				if (false == strpos($a, '.')) {
					return '$this->data[\'' . $a . '\']';
				} else {
					$b = explode('.', $a);
					$c = array();
					$c[] = '$this->data';
					foreach ($b as $i => $v) {
						$c[] = '[\'' . $v . '\']';
					}
					
					return implode('', $c);
				}
			case 3:
				return str_replace('\\', '/', self::$tpl_compile_dir) . md5(self::$tpl_dir . $a);
		}
	}
	
	/**
	 * Static instance of the object.
	 *
	 * @param RadishPHP $scope
	 * @return TemplateCompiler
	 */
	static function instance(&$scope) {
		return new TemplateCompiler($scope);
	}
	
	/**
	 * Recycling.
	 *
	 */
	function __destruct() {
		self::$tpl_dir = NULL;
		self::$tpl_compile_dir = NULL;
	}
}