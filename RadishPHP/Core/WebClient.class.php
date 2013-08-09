<?php
/**
 * Simple HTTP related functions integrated class object.
 *
 * Main features: CURL component implements the remote request, single or multiple file upload method.
 *
 * @author Lei Lee
 * @version 1.0
 */
class WebClient {

	/**
	 * Single upload mode.
	 *
	 */
	const FILE_UPLOAD_SINGLE = 1;

	/**
	 * Multiple file upload mode.
	 *
	 */
	const FILE_UPLOAD_MULTIPLE = 2;

	/**
	 * HTTP Method GET.
	 *
	 */
	const HTTP_METHOD_GET = 1;

	/**
	 * HTTP Method POST.
	 *
	 */
	const HTTP_METHOD_POST = 2;

	/**
	 * HTTP Method PUT.
	 *
	 */
	const HTTP_METHOD_PUT = 3;

	/**
	 * HTTP Method DELETE.
	 *
	 */
	const HTTP_METHOD_DELETE = 4;

	/**
	 * Whether to allow only the HTTP 200 status of the request?
	 *
	 * @var boolean
	 */
	private $allow_http_code_200_only = false;

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
	}

	/**
	 * Send remote HTTP requests.
	 *
	 * @param string $url
	 * @param int $httpMethod
	 * @param array $dataParam
	 * @param boolean $use_ck
	 * @param string $ckfile
	 * @param int $timeout
	 * @param boolean $useSSL
	 * @param array $proxy
	 * @return string
	 */
	function sendRequest($url, $httpMethod = 1, $dataParam = array(), $header = array(), $use_ck = false, $ckfile = NULL, $timeout = 60, $useSSL = false, $proxy = array()) {
		$http_header = array(
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 
			'Connection: close'
		);
		if (is_array($header) && !empty($header))
			$http_header = $header;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if (!empty($proxy)) {
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
			curl_setopt($ch, CURLOPT_PROXY, $proxy['host'] . ':' . $proxy['port']);
		}
		
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, floor($timeout / 2));
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $useSSL);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $useSSL);
		
		if (!empty($ckfile)) {
			if ($use_ck)
				curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
			else
				curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
		}
		
		switch ($httpMethod) {
			case self::HTTP_METHOD_POST :
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
				break;
			case self::HTTP_METHOD_PUT :
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				break;
			case self::HTTP_METHOD_DELETE :
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
		}
		if (!empty($dataParam)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataParam);
		}
		
		try {
			$sResult = curl_exec($ch);
		} catch (Exception $ex) {
			throw new RpcNetworkReceivedException('An error has occured in sending the remote request! (' . $ex->getMessage() . ')', -1);
		}
		
		if ($this->allow_http_code_200_only === true) {
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($http_code != 200)
				throw new RpcNetworkStatusException('The server returned an invalid status code. (Status: ' . $http_code . ')');
		}
		
		curl_close($ch);
		
		if (empty($sResult)) {
			throw new RpcNoneResultException('No returned result from remote request! Please check the service end script.', -1);
		}
		
		return (empty($sResult) ? false : $sResult);
	}

	/**
	 * Upload files.
	 *
	 * @param string $form_name Name of the form.
	 * @param string $store_directory Files are stored directory path.
	 * @param boolean $is_hash_subdir Whether to automatically create a subdirectory store?
	 * @param int $style Form mode. (1, Single file upload mode. 2, Upload file array mode. 3, Different file name of the form, and multi-file upload.)
	 * @param int $max_bytes Limit upload file size. (Default: 1 MB)
	 * @param array $allow_exts Array of allowed file extensions. (Default: GIF, JPG, PNG | Don't contain "." characters)
	 * @param int $sub_dir_count The maximum number of hash directory.
	 * @return array
	 */
	function sendFile($form_name, $store_directory, $is_hash_subdir = false, $style = 1, $max_bytes = 1024000, $allow_exts = array('gif', 'jpg', 'jpeg', 'png'), $sub_dir_count = 200) {
		if (!is_array($allow_exts))
			throw new RuntimeException('The parameter `$allow_exts` must be a sequence that contains an array of extensions.', -1);
		
		foreach ($allow_exts as $key => $value)
			$allow_exts[$key] = strtolower($value);
		
		$cdir = $is_hash_subdir ? sprintf('%03d', time() % $sub_dir_count) : '';
		$store_directory = rtrim($store_directory, '/\\');
		if (!empty($cdir))
			$store_directory .= DIRECTORY_SEPARATOR . $cdir;
		$store_directory = Router::standardize($store_directory);
		
		// Check the target directory path exist?
		if (!(file_exists($store_directory) && is_dir($store_directory))) {
			if (false == mkdir($store_directory, 0777, true))
				throw new CreateDirectoryException('Failed to create destination directory. (Path: ' . Router::standardize($store_directory) . ')', -1);
		}
		
		$files = $_FILES[$form_name];
		
		if (empty($files))
			return false;
		
		$dResults = array();
		if ($style == 1) {
			// Check the file size limit ...
			if ($files['size'] > $max_bytes)
				throw new FileSizeOverflowException('File size out limit specified number of bytes. (Max: ' . $max_bytes . ' bytes)', -1);
				
			// Read the file name of the relevant information ...
			$fns = pathinfo($files['name']);
			$ext = strtolower($fns['extension']);
			if (!in_array($ext, $allow_exts))
				throw new FileExtensionInvalidException('File extension is invalid!', -1);
			
			mt_srand(microtime(true));
			
			$dResults['file_count'] = 1;
			$dResults['extension'] = $ext;
			$dResults['base_name'] = md5(date('Y-m-d H:i:s') . strval(microtime(true)) . strval(mt_rand(1, 10000)));
			$dResults['name'] = $dResults['base_name'] . '.' . $ext;
			$dResults['size'] = $files['size'];
			$dResults['type'] = $files['type'];
			$dResults['cdir'] = $cdir;
			$dResults['dirname'] = !empty($cdir) ? $cdir . '/' : '';
			$dResults['abs_dir'] = $store_directory;
			$dResults['filename'] = $files['name'];
			$dResults['image'] = in_array($ext, array('jpg', 'jpeg', 'png', 'gif')) ? true : false;
			
			// Save the file to the target directory ...
			if (move_uploaded_file($files['tmp_name'], $dResults['abs_dir'] . DIRECTORY_SEPARATOR . $dResults['name'])) {
				if ($dResults['image'] === true) {
					list($dResults['width'], $dResults['height']) = getimagesize($dResults['abs_dir'] . DIRECTORY_SEPARATOR . $dResults['name']);
				}
				
				return $dResults;
			} else {
				throw new FileUploadFailedException('The file you are uploading error occurred during the move!', -1);
			}
		} elseif ($style == 2) {
			$file_count = count($files['name']);
			for ($i = 0; $i < $file_count; $i++) {
				if ($files['size'][$i] > $max_bytes)
					continue;
				
				$fns = pathinfo($files['name'][$i]);
				$ext = strtolower($fns['extension']);
				if (!in_array($ext, $allow_exts))
					continue;
				
				mt_srand(microtime(true));
				
				$dResults[$i]['extension'] = $ext;
				$dResults[$i]['base_name'] = md5(date('Y-m-d H:i:s') . strval(microtime(true)) . strval($i) . strval(mt_rand(1, 10000)));
				$dResults[$i]['name'] = $dResults[$i]['base_name'] . '.' . $ext;
				$dResults[$i]['size'] = $files['size'][$i];
				$dResults[$i]['type'] = $files['type'][$i];
				$dResults[$i]['cdir'] = $cdir;
				$dResults[$i]['abs_dir'] = $store_directory;
				$dResults[$i]['filename'] = $files['name'][$i];
				
				// Save the file to the target directory ...
				if (false == move_uploaded_file($files['tmp_name'][$i], $dResults[$i]['abs_dir'] . DIRECTORY_SEPARATOR . $dResults[$i]['name'])) {
					unset($dResults[$i]);
					continue;
				}
			}
			$dResults['file_count'] = count($dResults);
			if (0 < $dResults['file_count']) {
				return $dResults;
			}
		}
		
		return false;
	}

	/**
	 * If you call this method only if the HTTP status code returned to 200 when the resulting text. Otherwise, throw an exception.
	 *
	 */
	function absolute() {
		$this->allow_http_code_200_only = true;
	}

	/**
	 * Returns the name of the HTTP methods.
	 *
	 * @param int $httpMethod
	 * @return string
	 */
	function getHttpMethodName($httpMethod) {
		switch ($httpMethod) {
			case self::HTTP_METHOD_GET :
				return 'GET';
			case self::HTTP_METHOD_POST :
				return 'POST';
			case self::HTTP_METHOD_PUT :
				return 'PUT';
			case self::HTTP_METHOD_DELETE :
				return 'DELETE';
		}
		
		return 'GET';
	}
}