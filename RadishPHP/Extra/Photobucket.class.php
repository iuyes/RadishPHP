<?php
/**
 * Photobucket Api Services.
 *
 * @author Lei Lee
 * @version 1.0
 */
class Photobucket {

	/**
	 * Create a new album.
	 *
	 */
	const ACTION_CREATE_ALBUM = 1;

	/**
	 * Rename the specified album.
	 *
	 */
	const ACTION_RENAME_ALBUM = 2;

	/**
	 * Delete the specified album.
	 *
	 */
	const ACTION_DELETE_ALBUM = 3;

	/**
	 * Update album privacy setting.
	 *
	 */
	const ACTION_UPDATE_ALBUM_PRIVACY = 4;

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
	private $_auth_cache_key = 'Photobucket.Api.v1.0';

	/**
	 * Photobucket Api base-url.
	 *
	 * @var string
	 */
	private $_api_base_uri = 'http://api.photobucket.com';

	/**
	 * The instance of the WebClient object.
	 *
	 * @var RadishPHP
	 */
	private $scope = NULL;

	/**
	 * Album Privacy.
	 *
	 * @var array
	 */
	private $privacy = array(
		1 => 'public', 
		2 => 'private'
	);

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
	}

	/**
	 * Return a Photobucket instance of an object.
	 *
	 * @param string $consumer_key
	 * @param string $consumer_secret
	 * @param RadishPHP $scope
	 * @return Photobucket
	 */
	static function instance($consumer_key, $consumer_secret, &$scope) {
		return new Photobucket($consumer_key, $consumer_secret, $scope);
	}

	/**
	 * The server sends the authentication request and obtain the authorization key.
	 *
	 */
	function authorize() {
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
		
		$instance = $this->initialize($url, NULL, WebClient::HTTP_METHOD_POST, $token);
		
		$sResult = $this->scope->http->curl($instance->toUrl(), true, $instance->toPostdata(), array(
			$instance->toHeader()
		));
		$dResult = $this->tokenParser($sResult);
		
		$this->scope->cache->set($this->_auth_cache_key, $dResult, 0, 'Photobucket Api cache data.');
		
		if ($authorized === false) {
			$authorize_url = $this->_authorize_url . '?oauth_token=' . OAuth_Utils::urlencodeRFC3986($dResult['oauth_token']);
			$authorize_url .= '&oauth_callback=' . OAuth_Utils::urlencodeRFC3986(empty($this->_auth_callback) ? $this->getCallbackUri() : $this->_auth_callback);
			
			header('Location: ' . $authorize_url);
		} else {
			switch ($this->scope->dataBridge->gets('status')) {
				case 'ready' :
					header('Location: ' . $this->_auth_success_url);
					break;
				case 'denied' :
					exit('The request token is not signed because the End-User denied access to their account on the Authorization page.');
				case 'canceled' :
					exit('The request token is not signed because the End-User canceled the login process on the Login page.');
			}
		}
		exit(0);
	}

	/**
	 * Call the API method.
	 *
	 * @param int $action
	 * @param PhotobucketAlbum $dataEntity
	 * @return array
	 */
	function api($action, $dataEntity) {
		$dataResult = false;
		
		switch ($action) {
			case self::ACTION_CREATE_ALBUM :
				if (!is_a($dataEntity, 'PhotobucketAlbum'))
					throw new RuntimeException('The $dataEntity is not a PhotobucketAlbum type.');
				$dataResult = $this->call('album', $dataEntity->getAlbumPath(), WebClient::HTTP_METHOD_POST, array(
					'name' => $dataEntity->getAlbumName()
				));
				break;
			case self::ACTION_RENAME_ALBUM :
				if (!is_a($dataEntity, 'PhotobucketAlbum'))
					throw new RuntimeException('The $dataEntity is not a PhotobucketAlbum type.');
				$dataResult = $this->call('album', $dataEntity->getAlbumPath(), WebClient::HTTP_METHOD_PUT, array(
					'name' => $dataEntity->getAlbumName()
				));
				break;
			case self::ACTION_DELETE_ALBUM :
				if (!is_a($dataEntity, 'PhotobucketAlbum'))
					throw new RuntimeException('The $dataEntity is not a PhotobucketAlbum type.');
				$dataResult = $this->call('album', $dataEntity->getAlbumPath(), WebClient::HTTP_METHOD_DELETE);
				break;
			case self::ACTION_UPDATE_ALBUM_PRIVACY :
				if (!is_a($dataEntity, 'PhotobucketAlbum'))
					throw new RuntimeException('The $dataEntity is not a PhotobucketAlbum type.');
				$album_privacy_id = $dataEntity->getAlbumPrivacy();
				$dataResult = $this->call('album', $dataEntity->getAlbumPath() . '/privacy', WebClient::HTTP_METHOD_PUT, array(
					'privacy' => $this->privacy[$album_privacy_id]
				));
				unset($album_privacy_id);
				break;
		}
		
		return $dataResult;
	}

	/**
	 * Create a new album.
	 *
	 * @param string $album_name To create the album name.
	 * @param string $album_in_name Belongs to the complete path of the album.
	 * @return boolean
	 */
	function create($album_name, $album_in_name = NULL) {
		$dataResult = $this->call('/album/{identifier}', $album_in_name, WebClient::HTTP_METHOD_POST, array(
			'name' => $album_name
		));
		
		return ('OK' == $dataResult['status'] ? true : false);
	}

	/**
	 * Rename the specified album.
	 *
	 * @param string $album_name
	 * @param string $album_name_new
	 * @return boolean
	 */
	function rename($album_name, $album_name_new) {
		$dataResult = $this->call('/album/{identifier}', $album_name, WebClient::HTTP_METHOD_PUT, array(
			'name' => $album_name_new
		));
		
		return ('OK' == $dataResult['status'] ? true : false);
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
	 * Generate a OAuth_Request object.
	 *
	 * @param string $url
	 * @param array $data_params
	 * @param int $http_method
	 * @param OAuth_Token $token
	 * @return OAuth_Request
	 */
	private function initialize($url, $data_params = NULL, $http_method = 1, $token = NULL) {
		$consumer = new OAuth_Consumer($this->_consumer_key, $this->_consumer_secret);
		
		$standard = OAuth_Request::fromConsumerAndToken($consumer, $token, $this->scope->http->getHttpMethodName($http_method), $url, $data_params);
		$standard->signRequest(OAuth_Signature::SIGNATURE_METHOD_HMAC_SHA1, $consumer, $token);
		
		return $standard;
	}

	/**
	 * Calling API interface and get the returned data object.
	 *
	 * @param string $api
	 * @param string $extra
	 * @param int $http_method
	 * @param array $data
	 * @return array
	 */
	private function call($api, $extra = NULL, $http_method = 1, $data = NULL) {
		$dResult = NULL;
		
		$token_cache = $this->scope->cache->get($this->_auth_cache_key);
		if (!empty($token_cache) && is_array($token_cache)) {
			$url = rtrim($this->_api_base_uri, '\\/ ');
			$url .= '/' . trim($api, '\\/ ');
			$url .= '/' . $token_cache['username'];
			
			if (!empty($extra)) {
				$extra = '/' . trim($extra, '\\/ ');
				$extra = preg_replace('/[\\/]{2,}/', '/', $extra);
				$url .= OAuth_Utils::urlencodeRFC3986($extra);
			}
			
			if (!is_array($data))
				$data = array();
			
			$data = array_merge($data, array(
				'format' => 'json'
			));
			
			$token = new OAuth_Token($token_cache['oauth_token'], $token_cache['oauth_token_secret']);
			
			$instance = $this->initialize($url, $data, $http_method, $token);
			
			$sResult = $this->scope->http->curl($instance->toUrl(), $http_method, $instance->toPostdata(), array(
				$instance->toHeader()
			));
			$dResult = $this->dataParser($sResult);
			
			if (empty($dResult))
				throw new RpcNoneResultException('The server returns the result is empty.');
			elseif ($dResult['status'] == 'Exception')
				throw new PhotobucketException('Photobucket return an error message. (' . $dResult['message'] . ')');
		} else {
			throw new RuntimeException('Not found a valid Access_Token data. Please try to log in again and get a new license.');
		}
		
		return $dResult;
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

/**
 * Photobucket Album data entity objects.
 *
 * @author Lei Lee
 */
class PhotobucketAlbum {

	/**
	 * Album name.
	 *
	 * @var string
	 */
	private $albumName = NULL;

	/**
	 * Album where the path.
	 *
	 * @var string
	 */
	private $albumPath = NULL;

	/**
	 * The album privacy.
	 *
	 * @var int
	 */
	private $albumPrivacy = 1;

	/**
	 * Set the specified name of the album.
	 *
	 * @param string $albumName
	 * @return PhotobucketAlbum
	 */
	function setAlbumName($albumName) {
		$this->albumName = $albumName;
		return $this;
	}

	/**
	 * Get the specified name of the album.
	 *
	 * @return string
	 */
	function getAlbumName() {
		return $this->albumName;
	}

	/**
	 * Set the path of the specified album.
	 *
	 * @param string $albumPath
	 * @return PhotobucketAlbum
	 */
	function setAlbumPath($albumPath) {
		$this->albumPath = $albumPath;
		return $this;
	}

	/**
	 * Get the path of the specified album.
	 *
	 * @return string
	 */
	function getAlbumPath() {
		return $this->albumPath;
	}

	/**
	 * Set the album privacy.
	 *
	 * @param int $albumPrivacy
	 * @return PhotobucketAlbum
	 */
	function setAlbumPrivacy($albumPrivacy) {
		$this->albumPrivacy = $albumPrivacy;
		return $this;
	}

	/**
	 * Get the album privacy.
	 *
	 * @return int
	 */
	function getAlbumPrivacy() {
		return $this->albumPrivacy;
	}
}

class PhotobucketException extends Exception {
}