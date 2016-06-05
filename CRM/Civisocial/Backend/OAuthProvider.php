<?php
/**
 * This class defines common functions and declares that should be overridden
 * by each OAuth Provider
 *
 * To add a new OAuth Provider, add a new file named [oauth_provider_alias].php
 * in OAuthProvider/ and extend this class. Methods declared by this class should
 * be overriden by the new class. All letters of oauth_provider_alias should be in
 * small case. For eg. googleplus. The class name of new OAuthProvider class should
 * start with an uppercase letter. Rest of the letters should be in small case.
 * For eg. Googleplus is a valid class name. GooglePlus is not a valid class name.
 */

class CRM_Civisocial_Backend_OAuthProvider {
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
   *      OAuth Provider short name (alias)
   */
  public function getApiCredentials($backend) {
    $this->apiKey = civicrm_api3(
            "setting",
            "getvalue",
            array(
              "group" => "CiviSocial Account Credentials",
              "name" => "{$backend}_api_key",
            )
        );
    $this->apiSecret = civicrm_api3(
            "setting",
            "getvalue",
            array(
              "group" => "CiviSocial Account Credentials",
              "name" => "{$backend}_api_secret",
            )
        );
  }

  /**
   * URL to be redirected to after user authorizes
   *
   * @param string $backend
   *      OAuth Provider short name (alias)
   *
   * @return string
   */
  public function getCallbackUri($backend) {
    return rawurldecode(CRM_Utils_System::url("civicrm/civisocial/callback/{$backend}", NULL, TRUE));
  }

  /**
   * Authorization URI that user will be redirected to for login
   */
  public function getLoginUri() {
  }

  /**
   * Process information returned by OAuth provider after login
   */
  public function handleCallback() {
    if ($this->isLoggedIn()) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('', NULL, TRUE));
    }
  }

  /**
   * Save Backend information to the session
   *
   * @param string $backend
   *      Shortname for OAuth provider
   * @param string $accessToken
   *      Access Token provided by OAuth provider after successfull authentication
   * @param string $backendId
   *      Unique user ID to OAuthProvider
   */
  public function login($backend, $accessToken, $backendId) {
    $session = CRM_Core_Session::singleton();
    $session->set('civisocial_logged_in', TRUE);
    $session->set('civisocial_backend', $backend);
    $session->set('civisocial_backend_user_id', $backendId);
    $session->set('access_token', $accessToken);
  }

  /**
   * Disconnect with OAuth provider
   */
  public function logout() {
    $session = CRM_Core_Session::singleton();
    $session->set('civisocial_logged_in', NULL);
    $session->set('civisocial_backend', NULL);
    $session->set('access_token', NULL);
  }

  /**
   * Check if the user is already logged in
   *
   * @return bool
   */
  public function isLoggedIn() {
    $session = CRM_Core_Session::singleton();
    if ($session->get('civisocial_logged_in')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the user is connected to OAuth provider and authorized.
   * It can also be used to validate access tokens after setting one.
   */
  public function isAuthorized() {
  }

  /**
   * GET wrapper for HTTP request
   *
   * @return array
   */
  public function get($node, $params = array()) {
    return $this->http($node, $params, 'GET');
  }

  /**
   * POST wrapper for HTTP request
   *
   * @return array
   */
  public function post($node, $params = array()) {
    return $this->http($node, $params, 'POST');
  }

  /**
   * Make HTTP requests
   *
   * @param string $node
   *      API node
   * @param array $params
   *      GET/POST parameters
   * @param string $method
   *      HTTP method (GET/POST)
   *
   * @return array
   *      JSON response decoded to an array
   * @todo Refactor the method to merge with Twitter
   */
  public function http($node, $params, $method = 'GET') {
    $nodeParts = explode('?', $node);
    if (count($nodeParts) == 2) {
      $node = $nodeParts[0];
    }

    $paramsStr = http_build_query($params);

    $ch = curl_init();
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
      $node = implode('?', $nodeParts);
    }
    elseif ($method == 'GET') {
      $node .= '?' . $paramsStr;
      if (isset($nodeParts[1])) {
        $node .= '&' . $nodeParts[1];
      }
    }

    $uri = $this->apiUri . '/' . $node;
    curl_setopt($ch, CURLOPT_URL, $uri);
    $response = curl_exec($ch);
    $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return json_decode($response, TRUE);
  }

}
