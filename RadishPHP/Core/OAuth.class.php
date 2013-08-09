<?php
/**
 * OAuth Request
 *
 * Adapted from Andy Smith's OAuth library for PHP
 *
 * @link http://oauth.net/core/1.0
 * @link http://oauth.googlecode.com/svn/spec/ext/consumer_request/1.0/drafts/1/spec.html
 * @link http://oauth.googlecode.com/svn/code/php/
 * @link http://term.ie/oauth/example/
 *
 * @package OAuth
 *
 * @author jhart
 * @copyright Copyright (c) 2008, Photobucket, Inc.
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * OAuth Request Representation
 *
 * @package OAuth
 */
class OAuth_Request {

	/**
	 * holds all parameters for request
	 *
	 * @access protected
	 * @var array
	 */
	public $parameters = array();

	/**
	 * Holds HTTP method (normalized, strtoupper)
	 *
	 * @var string
	 */
	public $http_method = '';

	/**
	 * Holds url (normalized, per function)
	 *
	 * @var string
	 */
	public $http_url = '';

	/**
	 * generated base string for this request (debugging)
	 *
	 * @var string
	 */
	public $base_string = '';

	/**
	 * generated key string for this request (debugging)
	 *
	 * @var string
	 */
	public $key_string = '';

	/**
	 * Allowed version that we support with this library
	 *
	 * @var string
	 */
	public static $version = '1.0';

	/**
	 * Request Constructor
	 *
	 * @uses getNormalizedHttpUrl()
	 * @param $http_method string
	 *       	 http method
	 * @param $http_url string
	 *       	 url
	 * @param $parameters array
	 *       	 array of parameters
	 */
	public function __construct($http_method, $http_url, $parameters = null) {
		@$parameters or $parameters = array();
		$this->parameters = $parameters;
		$this->http_method = strtoupper($http_method);
		$this->http_url = self::getNormalizedHttpUrl($http_url);
	}

	/**
	 * build up a request from what was passed to the server
	 *
	 * @param $http_method string
	 *       	 [optional, default=_SERVER[HTTP_METHOD]] HTTP method
	 *       	 (get|put|post|delete)
	 * @param $http_url string
	 *       	 [optional,
	 *       	 default=http://_SERVER[HTTP_HOST]._SERVER[REQUEST_URI]]
	 *       	 request url to sign
	 * @param $parameters array
	 *       	 [optional, default=_REQUEST] parameters to sign
	 * @return self
	 */
	public static function fromRequest($http_method = null, $http_url = null, $parameters = null) {
		@$http_url or $http_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		@$http_method or $http_method = $_SERVER['REQUEST_METHOD'];

		if ($parameters) {
			$req = new self($http_method, $http_url, $parameters);
		} else {
			$parameters = array_diff_assoc($_REQUEST, $_COOKIE);

			$request_headers = apache_request_headers();
			if (array_key_exists('Authorization', $request_headers) && substr($request_headers['Authorization'], 0, 5) == 'OAuth') {
				$header = trim(substr($request_headers['Authorization'], 5));
				$header_parameters = self::splitHeader($request_headers['Authorization']);
				$parameters = array_merge($header_parameters, $parameters);
			}

			$req = new self($http_method, $http_url, $parameters);
		}
		return $req;
	}

	/**
	 * build up a request form just a URL+querystring
	 *
	 * @param $url string
	 *       	 a whole url, querystring included.
	 * @param $http_method string
	 *       	 [optional, default=GET] http method
	 * @param $consumer OAuth_Consumer
	 *       	 [optional] consumer to sign with
	 * @param $token OAuth_Token
	 *       	 [optional] token to sign with
	 * @return self
	 */
	public static function fromUrl($url, $http_method = 'GET', $consumer = null, $token = null) {
		$parts = parse_url($url);
		$qs = array();
		parse_str($parts['query'], $qs);
		if (!$consumer) {
			return self::fromRequest($http_method, $url, $qs);
		} else {
			return self::fromConsumerAndToken($consumer, $token, $http_method, $url, $qs);
		}
	}

	/**
	 * Create request from consumer and token as well (for a new request)
	 *
	 * @param $consumer OAuth_Consumer
	 *       	 consumer
	 * @param $token OAuth_Token
	 *       	 token
	 * @param $http_method string
	 *       	 method
	 * @param $http_url string
	 *       	 url
	 * @param $parameters array
	 *       	 parameters
	 * @return OAuth_Request
	 */
	public static function fromConsumerAndToken($consumer, $token, $http_method, $http_url, $parameters) {
		@$parameters or $parameters = array();
		$defaults = array(
			'oauth_version' => self::$version,
			'oauth_nonce' => self::getNonce(),
			'oauth_timestamp' => self::getTimestamp(),
			'oauth_consumer_key' => $consumer->getKey()
		);
		$parameters = array_merge($defaults, $parameters);

		if ($token) {
			$parameters['oauth_token'] = $token->getKey();
		}
		return new self($http_method, $http_url, $parameters);
	}

	/**
	 * set a parameter
	 *
	 * @param $name string
	 * @param $value string
	 */
	public function setParameter($name, $value) {
		$this->parameters[$name] = $value;
	}

	/**
	 * get a parameter
	 *
	 * @param $name string
	 * @return string
	 */
	public function getParameter($name) {
		if (!array_key_exists($name, $this->parameters))
			return null;
		return $this->parameters[$name];
	}

	/**
	 * Get parameters array
	 *
	 * @return array of key=>value
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * normalize input url
	 *
	 * @param $url string
	 *       	 url to normalize
	 * @return string normalized url
	 */
	public static function getNormalizedHttpUrl($url) {
		$parts = parse_url($url);
		$port = '';
		if (array_key_exists('port', $parts) && $parts['port'] != '80') {
			$port = ':' . $parts['port'];
		}
		return $parts['scheme'] . '://' . $parts['host'] . $port . '/' . trim($parts['path'], '/');
	}

	/**
	 * get HTTP url in this request (normalized)
	 *
	 * @return string
	 */
	public function getHttpUrl() {
		return $this->http_url;
	}

	/**
	 * get HTTP method in this request (normalized)
	 *
	 * @return unknown
	 */
	public function getHttpMethod() {
		return $this->http_method;
	}

	/**
	 * Build whole url for request
	 *
	 * @uses toPostdata()
	 * @uses getHttpUrl()
	 * @return string http://httpurl?parameters
	 */
	public function toUrl() {
		$out = $this->getHttpUrl() . '?';
		$out .= $this->toPostdata();
		return $out;
	}

	/**
	 * build querystring for post or get
	 *
	 * @return string param=value&param=value...
	 */
	public function toPostdata() {
		return OAuth_Utils::normalizeKeyValueParameters($this->getParameters());
	}

	/**
	 * Builds Authorization: header for request
	 *
	 * @return string Authorization: OAuth ...
	 */
	public function toHeader() {
		$out = 'Authorization: OAuth realm="",';
		return $out . OAuth_Utils::normalizeKeyValueParameters($this->getParameters(), ',');
	}

	/**
	 * gets url
	 *
	 * @uses toUrl()
	 * @return string
	 */
	public function __toString() {
		return $this->toUrl();
	}

	/**
	 * Signs this request - adds parameters for signature method and the signed
	 * signature
	 *
	 * @param $signature_method string
	 *       	 signing method identifier
	 * @param $consumer OAuth_Consumer
	 *       	 to sign against
	 * @param $token OAuth_Token
	 *       	 to sign against
	 */
	public function signRequest($signature_method, $consumer, $token = null) {
		if (!($method = OAuth_Signature::getSignatureMethod($signature_method)))
			return false;

		$this->setParameter('oauth_signature_method', $method->getMethodName());
		$consumer_secret = $consumer->getSecret();
		$token_secret = ($token) ? $token->getSecret() : '';

		$signature = $method->signRequest($this, $consumer_secret, $token_secret);
		$this->setParameter('oauth_signature', $signature);
	}

	/**
	 * Get current timestamp
	 *
	 * @return int
	 */
	public static function getTimestamp() {
		// return 1191242096; //example from spec
		return time();
	}

	/**
	 * get current nonce
	 *
	 * @return string
	 */
	public static function getNonce() {
		// return 'kllo9940pd9333jh'; //example from spec
		$mt = microtime();
		$rand = mt_rand();

		return md5($mt . $rand); // md5s look nicer than numbers
	}

	/**
	 * util function for turning the Authorization: header into
	 * parameters, has to do some unescaping
	 *
	 * @param $header string
	 *       	 string to split up
	 * @return array array of oauth params
	 */
	public static function splitHeader($header) {
		// error cases: commas in parameter values
		$parts = explode(',', $header);
		$out = array();
		foreach ($parts as $param) {
			$param = trim($param);
			// skip the 'realm' param, nobody ever uses it anyway
			if (substr($param, 0, 5) != 'oauth')
				continue;

			$param_parts = explode('=', $param);

			// rawurldecode() used because urldecode() will turn a '+' in the
			// value into a space
			$out[OAuth_Utils::urldecodeRFC3986($param_parts[0])] = OAuth_Utils::urldecodeRFC3986(trim($param_parts[1], '"'));
		}
		return $out;
	}
}

/**
 * OAuth Consumer representation
 *
 * @package OAuth
 */
class OAuth_Consumer {

	/**
	 * Consumer Key string
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Consumer Secret string
	 *
	 * @var string
	 */
	public $secret;

	/**
	 * Constructor
	 *
	 * @param $key string
	 *       	 oauth_consumer_key
	 * @param $secret string
	 *       	 oauth_consumer_secret
	 */
	public function __construct($key, $secret) {
		$this->key = $key;
		$this->secret = $secret;
	}

	/**
	 * Magic function that shows who we are
	 *
	 * @return string key of consumer oauth_consumer_key
	 */
	public function __toString() {
		return urlencode($this->getKey());
	}

	/**
	 * Get the key
	 *
	 * @return string key of consumer oauth_consumer_key
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * get the secret
	 *
	 * @return string secret of consumer oauth_consumer_secret
	 */
	public function getSecret() {
		return $this->secret;
	}
}

/**
 * OAuth Token representation
 *
 * @package OAuth
 */
class OAuth_Token {

	/**
	 * Token key
	 *
	 * @var string oauth_token
	 */
	public $key;

	/**
	 * Token secret
	 *
	 * @var string oauth_token_secret
	 */
	public $secret;

	/**
	 * Constructor
	 *
	 * @param $key string
	 *       	 oauth_token
	 * @param $secret string
	 *       	 oauth_token_secret
	 */
	public function __construct($key, $secret) {
		$this->key = $key;
		$this->secret = $secret;
	}

	/**
	 * Returns postdata representation of the token
	 *
	 * @return string postdata and OAuth Standard representation
	 */
	public function __toString() {
		return 'oauth_token=' . urlencode($this->getKey()) . '&oauth_token_secret=' . urlencode($this->getSecret());
	}

	/**
	 * get key
	 *
	 * @return string oauth_token
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * get token
	 *
	 * @return string oauth_token_secret
	 */
	public function getSecret() {
		return $this->secret;
	}
}

/**
 * OAuth Request Signing
 *
 * @package OAuth
 */
class OAuth_Signature {

	/**
	 * The method of signature.
	 * (HMAC_SHA1)
	 *
	 * @var string
	 */
	const SIGNATURE_METHOD_HMAC_SHA1 = 'HMAC_SHA1';

	/**
	 * The method of signature.
	 * (PlainText)
	 *
	 * @var string
	 */
	const SIGNATURE_METHOD_PLAINTEXT = 'PlainText';

	/**
	 * Get a signature method class
	 *
	 * @todo php5.3 make this happen static to the interface.
	 * @param $method string
	 *       	 OAuth signature method
	 * @return OAuth_Signature_Interface
	 */
	public static function getSignatureMethod($method) {
		// normalize method
		$method = OAuth_Utils::normalizeHashMethod($method);
		$class = 'OAuth_Signature_' . $method;
		// if (!class_exists($class)) require_once (RADISHPHP_LIB_PATH .
		// 'OAuth/Signature/' . $method . '.php');


		return new $class();
	}

	/**
	 * Sign a request
	 *
	 * @param $method string
	 *       	 method name
	 * @param $request OAuthRequest
	 * @param $consumer_secret string
	 * @param $token_secret string
	 * @return string
	 */
	public static function buildSignature($method, $request, $consumer_secret, $token_secret = null) {
		return self::getSignatureMethod($method)->signRequest($request, $consumer_secret, $token_secret);
	}
}

/**
 * OAuth Utilities methods
 *
 * @package OAuth
 */
class OAuth_Utils {

	/**
	 * Takes an array of arrays 'keys' and 'vals', encodes them, and returns
	 * them as a string
	 *
	 * @uses urlencodeRFC3986()
	 * @param $splitparams array
	 *       	 array of two arrays with keys 'keys' and 'vals', e.g.
	 *       	 array('keys'=>array('a'), 'vals'=>array('1')))
	 * @param $delim strin
	 *       	 delimiter between parameters (headers uses ',')
	 * @return string delimited key=value string
	 */
	public static function normalizeParameters($splitparams, $delim = '&') {
		array_multisort($splitparams['keys'], $splitparams['values']);
		$vars = array();
		for ($i = 0; $i < count($splitparams['keys']); $i++) {
			$vars[] = self::urlencodeRFC3986($splitparams['keys'][$i]) . '=' . self::urlencodeRFC3986($splitparams['values'][$i]);
		}
		return implode($delim, $vars);
	}

	/**
	 * normalize array(key=>value, key=>value...) type of array and return the
	 * string
	 *
	 * @uses normalizeParameters()
	 * @param $params array
	 *       	 array of parameters in key=>value format
	 * @param $delim string
	 *       	 delimiter between parameters
	 * @return string delimited key=value string
	 */
	public static function normalizeKeyValueParameters($params, $delim = '&') {
		$karray = $varray = array();
		foreach ($params as $k => $v) {
			$karray[] = $k;
			$varray[] = $v;
		}
		return self::normalizeParameters(array(
			'keys' => $karray,
			'values' => $varray
		), $delim);
	}

	/**
	 * Encodes strings in an RFC3986 compatible encoding
	 *
	 * @param $string string
	 * @return string
	 */
	public static function urlencodeRFC3986($string) {
		return str_replace('%7E', '~', rawurlencode($string));
	}

	/**
	 * Encodes UTF8 in RFC3986 encoding
	 *
	 * @uses urlencodeRFC3986()
	 * @param $string string
	 * @return string
	 */
	public static function urlencodeRFC3986_UTF8($string) {
		return self::urlencodeRFC3986(utf8_encode($string));
	}

	/**
	 * Decodes strings from RFC3986 encoding
	 *
	 * @param $string string
	 * @return string
	 */
	public static function urldecodeRFC3986($string) {
		return rawurldecode($string); // no exta stuff needed for ~, goes
	// correctly automatically
	}

	/**
	 * Decodes UTF8 in an RFC3986 encoded string
	 *
	 * @uses urldecodeRFC3986()
	 * @param $string string
	 * @return string
	 */
	public static function urldecodeRFC3986_UTF8($string) {
		return utf8_decode(self::urldecodeRFC3986($string));
	}

	/**
	 * normalize hash method name
	 *
	 * @param $method string
	 * @return string
	 */
	public static function normalizeHashMethod($method) {
		// make 'neat' for other areas of the library
		return strtolower(str_replace(array(
			' ',
			'-'
		), '_', $method));
	}

	/**
	 * Filter parameters for things we shouldnt include in a basestring
	 *
	 * @return array
	 */
	public static function getFilteredBaseStringParams($params) {
		// remove things that shouldnt end up in the hash
		if (!empty($params['oauth_signature']))
			unset($params['oauth_signature']);
		return $params;
	}

	/**
	 * PHP implementation of hash_hmac - supports sha1 and md5 in PHP5
	 *
	 * @param $hashfunc string
	 *       	 name of hash function
	 * @param $string string
	 *       	 string to hash
	 * @param $key string
	 *       	 key to hash against
	 * @param $raw bool
	 *       	 [optional, default=false] return raw bits, or hex
	 * @param $blocksize int
	 *       	 [optional, default=64] blocksize to pad
	 * @return string result of hash
	 */
	public static function php_hash_hmac($hashfunc, $string, $key, $raw = false, $blocksize = 64) {
		if ($hashfunc != 'md5' && $hashfunc != 'sha1')
			return false;
		if (strlen($key) > $blocksize)
			$key = pack('H*', $hashfunc($key));
		$key = str_pad($key, $blocksize, chr(0));

		$ipad = str_repeat(chr(0x36), $blocksize);
		$opad = str_repeat(chr(0x5c), $blocksize);

		$ihash = pack('H*', $hashfunc(($key ^ $ipad) . $string));
		return $hashfunc(($key ^ $opad) . $ihash, $raw);
	}
}

/**
 * OAuth HMAC-SHA1 implementation
 *
 * @package OAuth
 * @subpackage Signature
 */
class OAuth_Signature_HMAC_SHA1 implements OAuth_Signature_Interface {

	/**
	 * Representation string
	 */
	const OAUTH_SIGNATURE_METHOD = 'HMAC-SHA1';

	/**
	 * Sign a request
	 *
	 * @param $request OAuth_Request
	 *       	 request to sign
	 * @param $consumer_secret string
	 *       	 consumer secret key
	 * @param $token_secret string
	 *       	 token secret key
	 * @return string calculated hash for request, secrets
	 */
	public function signRequest($request, $consumer_secret, $token_secret = '') {
		$basestr = self::generateBaseString($request->getHttpMethod(), $request->getHttpUrl(), OAuth_Utils::normalizeKeyValueParameters(OAuth_Utils::getFilteredBaseStringParams($request->getParameters())));
		// for debug purposes
		$request->base_string = $basestr;

		$keystr = self::generateKeyString($consumer_secret, $token_secret);
		// for debug purposes
		$request->key_string = $keystr;

		return self::calculateHash($basestr, $keystr);
	}

	/**
	 * Get the OAuth official string representation for this method
	 *
	 * @return string oauth method name
	 */
	public function getMethodName() {
		return self::OAUTH_SIGNATURE_METHOD;
	}

	/**
	 * Creates the basestring needed for signing per oAuth Section 9.1.2
	 * All strings are latin1
	 *
	 * @todo could be in a base class for hmac
	 * @uses urlencodeRFC3986()
	 * @param $http_method string
	 *       	 one of the http methods GET, POST, etc.
	 * @param $uri string
	 *       	 the uri; the url without querystring
	 * @param $params string
	 *       	 normalized parameters as returned from
	 *       	 OAuthUtil::normalizeParameters
	 * @return string concatenation of the encoded parts of the basestring
	 */
	protected static function generateBaseString($http_method, $uri, $params) {
		return OAuth_Utils::urlencodeRFC3986($http_method) . '&' . OAuth_Utils::urlencodeRFC3986($uri) . '&' . OAuth_Utils::urlencodeRFC3986($params);
	}

	/**
	 * Generate a key string
	 *
	 * @todo could be in a base class for hmac
	 * @param $consumersecret string
	 *       	 consumer secret key
	 * @param $tokensecret string
	 *       	 token secret key
	 * @return string single key string
	 */
	protected static function generateKeyString($consumersecret, $tokensecret = '') {
		return OAuth_Utils::urlencodeRFC3986($consumersecret) . '&' . OAuth_Utils::urlencodeRFC3986($tokensecret);
	}

	/**
	 * Calculates the HMAC-SHA1 secret
	 *
	 * @uses urlencodeRFC3986()
	 * @param $basestring string
	 *       	 gotten from generateBaseString
	 * @param $consumersecret string
	 * @param $tokensecret string
	 *       	 leave empty if no token present
	 * @return string base64 encoded signature
	 */
	protected static function calculateHash($basestring, $key) {
		return base64_encode(self::hash_hmac_sha1($basestring, $key, true));
	}

	/**
	 * run hash_hmac with sha1 (package independant)
	 *
	 * @uses php_hash_hmac
	 * @param $string string
	 *       	 string to hash
	 * @param $key string
	 *       	 key to hash against
	 * @return string result of hash
	 */
	protected static function hash_hmac_sha1($string, $key, $raw = true) {
		if (function_exists('hash_hmac')) {
			return hash_hmac('sha1', $string, $key, $raw);
		} else {
			return OAuth_Utils::php_hash_hmac('sha1', $string, $key, $raw);
		}
	}
}

/**
 * OAuth PLAINTEXT implementation
 *
 * @package OAuth
 * @subpackage Signature
 */
class OAuth_Signature_PlainText implements OAuth_Signature_Interface {

	/**
	 * Representation string
	 */
	const OAUTH_SIGNATURE_METHOD = 'PLAINTEXT';

	/**
	 * Sign a request
	 *
	 * @param $request OAuth_Request
	 *       	 request to sign
	 * @param $consumer_secret string
	 *       	 consumer secret key
	 * @param $token_secret string
	 *       	 token secret key
	 * @return string calculated hash for request, secrets
	 */
	public function signRequest($request, $consumer_secret, $token_secret = '') {
		// for debug purposes
		$request->base_string = '';

		$key = self::generateKeyString($consumer_secret, $token_secret);
		// for debug purposes
		$request->key_string = $key;

		return OAuth_Utils::urlencodeRFC3986($key);
	}

	/**
	 * Get the OAuth official string representation for this method
	 *
	 * @return string oauth method name
	 */
	public function getMethodName() {
		return self::OAUTH_SIGNATURE_METHOD;
	}

	/**
	 * Generate a key string
	 *
	 * @todo could be in a base class for hmac
	 * @param $consumersecret string
	 *       	 consumer secret key
	 * @param $tokensecret string
	 *       	 token secret key
	 * @return string single key string
	 */
	protected static function generateKeyString($consumersecret, $tokensecret = '') {
		return OAuth_Utils::urlencodeRFC3986($consumersecret) . '&' . self::urlencodeRFC3986($tokensecret);
	}
}

/**
 * OAuth Signature Method Interface
 *
 * @package OAuth
 * @subpackage Signature
 */
interface OAuth_Signature_Interface {

	/**
	 * Sign a request
	 *
	 * @param $request OAuth_Request
	 *       	 request to sign
	 * @param $consumer_secret string
	 *       	 consumer secret key
	 * @param $token_secret string
	 *       	 token secret key
	 * @return string signature hash string
	 */
	public function signRequest($request, $consumer_secret, $token_secret = '');

	/**
	 * Get the OAuth official string representation for this method
	 *
	 * @return string oauth method name
	 */
	public function getMethodName();
}