<?php
/**
 * Imgur Api Services.
 *
 * @author Lei Lee
 * @version 1.0
 */
class Imgur {
	/**
	 * OAuth Consumer Key.
	 *
	 * @var string
	 */
	private $_consumer_key = NULL;

	/**
	 * OAuth Consumer Secret.
	 *
	 * @var string
	 */
	private $_consumer_secret = NULL;

	/**
	 * OAuth authorize url.
	 *
	 * @var string
	 */
	private $_authorize_url = NULL;

	/**
	 * OAuth request token url.
	 *
	 * @var string
	 */
	private $_auth_request_url = NULL;

	/**
	 * OAuth access token url.
	 *
	 * @var string
	 */
	private $_auth_access_token_url = NULL;

	/**
	 * OAuth callback url.
	 *
	 * @var string
	 */
	private $_auth_callback = NULL;

	/**
	 * OAuth access token success url.
	 *
	 * @var string
	 */
	private $_auth_success_url = NULL;

	/**
	 * OAuth cache key.
	 *
	 * @var string
	 */
	private $_auth_cache_key = 'Imgur.Api.v1.0';

	/**
	 * Imgur Api base-url.
	 *
	 * @var string
	 */
	private $_api_base_uri = 'http://api.imgur.com/2';

	/**
	 * The instance of the RadishPHP object.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;

	/**
	 * Constructor.
	 *
	 * @param string $consumer_key
	 * @param string $consumer_secret
	 * @param RadishPHP $scope
	 */
	function __construct($consumer_key, $consumer_secret, &$scope) {
		$this->_consumer_key = $consumer_key;
		$this->_consumer_secret = $consumer_secret;
		$this->scope = &$scope;

		$this->scope->dc('OAuth');
	}

	/**
	 * Return a Imgur instance of an object.
	 *
	 * @param string $consumer_key
	 * @param string $consumer_secret
	 * @param RadishPHP $scope
	 * @return Imgur
	 */
	static function instance($consumer_key, $consumer_secret, &$scope) {
		return new Imgur($consumer_key, $consumer_secret, $scope);
	}

	/**
	 * The server sends the authentication request and obtain the authorization key.
	 *
	 */
	function authorize() {
		$consumer = new OAuth_Consumer($this->_consumer_key, $this->_consumer_secret);

		$token = NULL;
		$authorized = empty($_GET['oauth_token']) ? false : true;
		$url = $authorized ? $this->_auth_access_token_url : $this->_auth_request_url;

		if ($authorized === true) {
			$token_cache = $this->scope->cache->get($this->_auth_cache_key);
			if (!empty($token_cache) && is_array($token_cache)) {
				$token = new OAuth_Token($token_cache['oauth_token'], $token_cache['oauth_token_secret']);
			} else {
				throw new RuntimeException('Not found a valid Request_Token data.');
			}
		}

		$standard = OAuth_Request::fromConsumerAndToken($consumer, $token, 'POST', $url, NULL);
		$standard->signRequest(OAuth_Signature::SIGNATURE_METHOD_HMAC_SHA1, $consumer, $token);

		$sResult = $this->scope->http->curl($standard->toUrl(), WebClient::HTTP_METHOD_POST, $standard->toPostdata(), array(
			$standard->toHeader()
		));
		$dResult = $this->tokenParser($sResult);

		$this->scope->cache->set($this->_auth_cache_key, $dResult, 0, 'Imgur Api cache data.');

		if ($authorized === false) {
			$authorize_url = $this->_authorize_url . '?oauth_token=' . OAuth_Utils::urlencodeRFC3986($dResult['oauth_token']);
			$authorize_url .= '&oauth_callback=' . OAuth_Utils::urlencodeRFC3986(empty($this->_auth_callback) ? $this->getCallbackUri() : $this->_auth_callback);

			header('Location: ' . $authorize_url);
		} else {
			header('Location: ' . $this->_auth_success_url);
		}
		exit(0);
	}

	/**
	 * Calling API interface and get the returned data object.
	 *
	 * @param string $api_method
	 * @param int $httpMethod
	 * @param array $data
	 * @return array
	 */
	function call($api_method, $httpMethod = 1, $data = array()) {
		$dResult = NULL;

		$token_cache = $this->scope->cache->get($this->_auth_cache_key);
		if (!empty($token_cache) && is_array($token_cache)) {
			$url = rtrim($this->_api_base_uri, '\\/ ');
			$url .= rtrim($api_method, '\\/ ') . '.json';

			$data = array_merge($data, array(
				'_fake_status' => 200
			));

			$consumer = new OAuth_Consumer($this->_consumer_key, $this->_consumer_secret);
			$token = new OAuth_Token($token_cache['oauth_token'], $token_cache['oauth_token_secret']);

			$standard = OAuth_Request::fromConsumerAndToken($consumer, $token, $this->scope->http->getHttpMethodName($httpMethod), $url, $data);
			$standard->signRequest(OAuth_Signature::SIGNATURE_METHOD_HMAC_SHA1, $consumer, $token);

			$sResult = $this->scope->http->curl($standard->toUrl(), $httpMethod, $standard->toPostdata(), array(
				$standard->toHeader()
			), false, NULL, 300);
			$dResult = $this->dataParser($sResult);

			if (empty($dResult))
				throw new RpcNoneResultException('The server returns the result is empty.');
			elseif ($dResult['error'])
				throw new RpcResultParserException('Imgur return an error message. (' . $dResult['error']['message'] . ')');
		} else {
			throw new RuntimeException('Not found a valid Access_Token data. Please try to log in again and get a new license.');
		}

		return $dResult;
	}
	
	/**
	 * Upload an image to Imgur album.
	 *
	 * @param string $filename
	 * @return array
	 */
	function image($filename) {
		$dResult = NULL;

		$token_cache = $this->scope->cache->get($this->_auth_cache_key);
		if (!empty($token_cache) && is_array($token_cache)) {
			$url = rtrim($this->_api_base_uri, '\\/ ');
			$url .= '/account/images.json';

			$consumer = new OAuth_Consumer($this->_consumer_key, $this->_consumer_secret);
			$token = new OAuth_Token($token_cache['oauth_token'], $token_cache['oauth_token_secret']);

			$standard = OAuth_Request::fromConsumerAndToken($consumer, $token, 'POST', $url, NULL);
			$standard->signRequest(OAuth_Signature::SIGNATURE_METHOD_HMAC_SHA1, $consumer, $token);

			$ch = curl_init($standard->toUrl());
			curl_setopt($ch, CURLOPT_TIMEOUT, 300);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array(
				'image' => '@' . $filename
			));
			
			$sResult = curl_exec($ch);
			
			curl_close($ch);
			
			$dResult = $this->dataParser($sResult);

			if (empty($dResult))
				throw new RpcNoneResultException('The server returns the result is empty.');
			elseif ($dResult['error'])
				throw new RpcResultParserException('Imgur return an error message. (' . $dResult['error']['message'] . ')');
		} else {
			throw new RuntimeException('Not found a valid Access_Token data. Please try to log in again and get a new license.');
		}

		return $dResult;
	}

	/**
	 * Set the API required parameter values​​.
	 *
	 * @param array $options
	 * @return Imgur
	 */
	function setParameters($options) {
		if (isset($options['key']))
			$this->_auth_cache_key = $options['key'];
		if (isset($options['request']))
			$this->_auth_request_url = $options['request'];
		if (isset($options['access']))
			$this->_auth_access_token_url = $options['access'];
		if (isset($options['authorize']))
			$this->_authorize_url = $options['authorize'];
		if (isset($options['callback']))
			$this->_auth_callback = $options['callback'];
		if (isset($options['success']))
			$this->_auth_success_url = $options['success'];

		return $this;
	}

	/**
	 * Parsing the authentication server returns data.
	 *
	 * @param string $data_str
	 * @return array
	 */
	private function tokenParser($data_str) {
		$data = NULL;
		parse_str($data_str, $data);

		if (is_array($data) && !empty($data))
			return $data;
		else
			return false;
	}

	/**
	 * Remote results of data parser.
	 *
	 * @param string $data_str
	 * @return array
	 */
	private function dataParser($data_str) {
		return json_decode($data_str, true);
	}

	/**
	 * Returns the full URL path.
	 *
	 * @return string
	 */
	private function getCallbackUri() {
		return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
}