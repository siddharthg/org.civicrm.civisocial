<?php
/**
 * This class defines common functions and declares that should be overridden
 * by each Social Media (OAuth Provider)
 *
 * To add a new social media, add a new file named [social_media_alias].php
 * in SocialMedia/. Methods declared by this class should be overriden.
 */

class CRM_Civisocial_Backend_SocialMedia {
	/**
	 * API key/App ID/Consumer Key provided by OAuth provider
	 *
	 * @var string
	 */
	protected $apiKey;
	
	/**
	 * API Secret/App Secret/Consumer Secret provided by OAuth provider
	 *
	 * @var string
	 */
	protected $apiSecret;
	
	/**
	 * Base URL for API requests
	 *
	 * @var string
	 */
	protected $apiUri;

	/**
	 * Access Token
	 *
	 * @var mixed
	 */
	protected $token;

	/**
	 * Social user information
	 *
	 * @var array
	 */
	protected $userProfile;

	/**
	 * HTTP Status code of last API request
	 *
	 * @var string
	 */
	protected $httpCode;
	
	/**
	 * Default timeout
	 *
	 * @var int
	 */
	protected $timeout = 30;

	/**
	 * Default connection timeout
	 *
	 * @var int
	 */
	protected $connectTimeout = 30;

	/**
	 * Verify SSL certificate
	 *
	 * @var bool
	 */
	protected $sslVerifyPeer = FALSE;

	/**
	 * Contains the last HTTP headers returned
	 *
	 * @var string
	 */
	protected $httpInfo = array();

	/**
	 * Get social user information
	 *
	 * @return array
	 */
	public function getUserProfile() {
		return $this->userProfile;
	}

	/**
	 * Retrieve API credentails for the given Social Media
	 *
	 * @param string $backend
	 *		OAuth Provider short name (alias)
	 */
	protected function getApiCredentials($backend) {
		$this->apiKey = civicrm_api3(
			"setting",
			"getvalue",
			array(
				"group" => "CiviSocial Account Credentials",
				"name" => "{$backend}_api_key"
			)
		);
		$this->apiSecret = civicrm_api3(
			"setting",
			"getvalue",
			array(
				"group" => "CiviSocial Account Credentials",
				"name" => "{$backend}_api_secret"
			)
		);
	}

	/**
	 * URL to be redirected to after user authorizes
	 *
	 * @param string $backend
	 *		OAuth Provider short name (alias)
	 *
	 * @return string
	 */
	protected function getCallbackUri($backend) {
		return rawurldecode(CRM_Utils_System::url("civicrm/civisocial/callback/{$backend}", NULL, TRUE));
	}

	/**
	 * Authorization URI that user will be redirected to for login
	 *
	 * @return string | bool
	 */
	public function getLoginUri() {
	}

	/**
	 * Process information returned by OAuth provider after login
	 */
	public function handleCallback() {
	}

	/**
	 * Save Backend information to the session
	 *
	 * @param $backend
	 *		Shortname for OAuth provider
	 * @param $accessToken
	 *		Access Token provided by OAuth provider after successfull authentication
	 */
	public function login($backend, $accessToken) {
		$session = CRM_Core_Session::singleton();
		$session->set('civisocial_logged_in', TRUE);
		$session->set('civisocial_backend', $backend);
		$session->set('access_token', $accessToken);
	}

	/**
	 * Check if the user is connected to OAuth provider and authorized
	 *
	 * @return bool
	 */
	public function isAuthorized() {
	}

	/**
	 * GET wrapper for HTTP request
	 *
	 * @return array
	 */ 
	public function get($node, $params) {
		return $this->http($node, $params, 'GET');
	}

	/**
	 * POST wrapper for HTTP request
	 *
	 * @return array
	 */ 
	public function post($node, $params) {
		return $this->http($node, $params, 'POST');
	}

	/**
	 * Make HTTP requests
	 *
	 * @param string $node
	 *		API node
	 * @param array $params
	 *		GET/POST parameters
	 *		If it's a GET request $node may not contain query string
	 * @param string $method
	 *		HTTP method (GET/POST)
	 * @return array
	 *		JSON response decoded to an array
	 * @todo Refactor the method to merge with Twitter
	 */
	public function http($node, $params, $method = 'GET') {
		$uri = $this->apiUri . "/" . $node;
		$paramsStr = http_build_query($params);

		$ch = curl_init($uri);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			if (!empty($paramsStr)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsStr);
			}
		} elseif ($method == 'GET') {
			$uri .= '?'.http_build_query($params);
		}

		curl_setopt($ch, CURLOPT_URL, $uri);
		$response = curl_exec($ch);
		$this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return json_decode($response, TRUE);
	}

}
