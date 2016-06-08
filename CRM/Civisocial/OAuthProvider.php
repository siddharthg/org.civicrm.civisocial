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

class CRM_Civisocial_OAuthProvider {
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
   * Contains the HTTP header from the last request
   *
   * @var string
   */
  protected $httpHeader;

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
   * @param string $OAuthProvider
   *      OAuth Provider short name (alias)
   */
  public function getApiCredentials($OAuthProvider) {
    $this->apiKey = civicrm_api3(
            "setting",
            "getvalue",
            array(
              "group" => "CiviSocial Account Credentials",
              "name" => "{$OAuthProvider}_api_key",
            )
        );
    $this->apiSecret = civicrm_api3(
            "setting",
            "getvalue",
            array(
              "group" => "CiviSocial Account Credentials",
              "name" => "{$OAuthProvider}_api_secret",
            )
        );
  }

  /**
   * URL to be redirected to after user authorizes
   *
   * @param string $OAuthProvider
   *      OAuth Provider short name (alias)
   *
   * @return string
   */
  public function getCallbackUri($OAuthProvider) {
    return CRM_Utils_System::url("civicrm/civisocial/callback/{$OAuthProvider}", NULL, TRUE, NULL, FALSE);
  }

  /**
   * Authorization URI that user will be redirected to for login
   */
  public function getLoginUri() {
  }

  /**
   * Get header from the last request
   *
   * @return array
   */
  public function getHeader() {
    return $this->httpHeader;
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
   * Save OAuth Provider information to the session.
   * Acts as a logout if no parameters is passed.
   *
   * @param string $OAuthProvider
   *   Shortname for OAuth provider
   * @param string $accessToken
   *   Access Token provided by OAuth provider after successfull authentication
   * @param string $OAuthProviderId
   *   Unique user ID to OAuthProvider
   * @param int $contactId
   *   Contact ID of the social user
   *
   */
  public function login($OAuthProvider = NULL, $accessToken = NULL, $OAuthProviderId = NULL, $contactId = NULL) {
    $session = CRM_Core_Session::singleton();

    if ($OAuthProvider == NULL && $accessToken == NULL && $OAuthProviderId == NULL && $contactId == NULL) {
      $session->set('civisocial_logged_in', FALSE);
    } else {
      $session->set('civisocial_logged_in', TRUE);
    }
    $session->set('civisocial_oauth_provider', $OAuthProvider);
    $session->set('civisocial_social_user_id', $OAuthProviderId);
    $session->set('civisocial_contact_id', $contactId);
    $session->set('access_token', $accessToken);
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
    $nodeParts = explode('?', $node);
    if (count($nodeParts) == 2) {
      $node = $nodeParts[0];
    }

    if (isset($nodeParts[1])) {
      $node .= '?' . $nodeParts[1];
    }

    if (!empty($params)) {
      if (FALSE !== strpos($node, '?')) {
        $node .= '&';
      }
      else {
        $node .= '?';
      }
      $node .= http_build_query($params);
    }

    $url = "{$this->apiUri}/{$node}";
    return $this->http($url, 'GET');
  }

  /**
   * POST wrapper for HTTP request
   *
   * @return array
   */
  public function post($node, $params) {
    $url = "{$this->apiUri}/{$node}";
    return $this->http($url, 'POST', $params);
  }

  /**
   * Make a HTTP request
   *
   * @param string $url
   *   Request URL
   * @param array $params
   *   Request parameters
   * @param string $method
   *   Request method
   *
   * @return array
   *   Response from API
   */
  public function http($url, $method = 'GET', $postFields = NULL) {
    $this->httpInfo = array();
    $ci = curl_init();

    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer);
    curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'setHeader'));
    curl_setopt($ci, CURLOPT_HEADER, FALSE);

    switch ($method) {
      case 'POST':
        curl_setopt($ci, CURLOPT_POST, TRUE);
        if (!empty($postFields)) {
          curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($postFields));
        }
        break;

      case 'DELETE':
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($postFields)) {
          $url = "{$url}?{$postFields}";
        }
    }

    curl_setopt($ci, CURLOPT_URL, $url);
    $response = curl_exec($ci);
    $this->httpCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->httpInfo = array_merge($this->httpInfo, curl_getinfo($ci));
    $this->url = $url;
    curl_close($ci);
    return $response;
  }

  /**
   * Get the header info to store.
   *
   * @return array
   */
  public function setHeader($ci, $header) {
    $i = strpos($header, ':');
    if (!empty($i)) {
      $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
      $value = trim(substr($header, $i + 2));
      $this->httpHeader[$key] = $value;
    }
    return strlen($header);
  }

}
