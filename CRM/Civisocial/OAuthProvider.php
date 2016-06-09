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
  protected $userProfile = array();

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
   *   Returns array with keys:
   *    - id
   *    - first_name
   *    - last_name
   *    - full_name
   *    - gender
   *    - locale
   *    - email
   *    - profile_url
   *    - picture_url
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
   * Save social user to the database
   *
   * @param string $oAuthProvider
   *   Shortname for OAuth provider
   * @param array $userProfile
   *   Social user information
   */
  public function saveSocialUser($oAuthProvider, $userProfile) {
    $session = CRM_Core_Session::singleton();

    $socialUserId = CRM_Utils_Array::value("id", $userProfile);
    $contactId = civicrm_api3(
      'CivisocialUser',
      'socialuserexists',
      array(
        'social_user_id' => $socialUserId,
        'oauth_provider' => $oAuthProvider,
      )
    );

    if (!$contactId) {
      $user = array(
        'first_name' => CRM_Utils_Array::value('first_name', $userProfile),
        'last_name' => CRM_Utils_Array::value('last_name', $userProfile),
        'display_name' => CRM_Utils_Array::value("name", $userProfile),
        'preffered_language' => CRM_Utils_Array::value("locale", $userProfile),
        'gender' => CRM_Utils_Array::value('gender', $userProfile),
        'email' => CRM_Utils_Array::value("email", $userProfile),
        'contact_type' => 'Individual',
      );

      // Find/create contact to map with social user
      $contactId = civicrm_api3('CivisocialUser', 'createcontact', $user);

      // Create social user
      $socialUser = array(
        'contact_id' => $contactId,
        'social_user_id' => $socialUserId,
        'access_token' => serialize($session->get('access_token')),
        'oauth_provider' => $oAuthProvider,
        'created_date' => time(), // @todo: Created Date not being recorded
      );

      civicrm_api3('CivisocialUser', 'create', $socialUser);
    }
    $this->login($oAuthProvider, $socialUserId, $contactId);
    $this->redirect();
  }

  /**
   * Save OAuth Provider information to the session.
   * Acts as a logout if no parameters is passed.
   *
   * @param string $OAuthProvider
   *   Shortname for OAuth provider
   * @param string $OAuthProviderId
   *   Unique user ID to OAuthProvider
   * @param int $contactId
   *   Contact ID of the social user
   *
   */
  public function login($OAuthProvider = NULL, $OAuthProviderId = NULL, $contactId = NULL) {
    $session = CRM_Core_Session::singleton();

    if ($OAuthProvider == NULL && $OAuthProviderId == NULL && $contactId == NULL) {
      $session->set('civisocial_logged_in', FALSE);
    }
    else {
      $session->set('civisocial_logged_in', TRUE);
    }
    $session->set('civisocial_oauth_provider', $OAuthProvider);
    $session->set('civisocial_social_user_id', $OAuthProviderId);
    $session->set('civisocial_contact_id', $contactId);
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
   * Redirect to the request origin
   */
  public function redirect() {
    $session = CRM_Core_Session::singleton();
    $requestOrigin = $session->get("civisocialredirect");
    if (!$requestOrigin) {
      $requestOrigin = CRM_Utils_System::url('', NULL, TRUE);
    }
    CRM_Utils_System::redirect($requestOrigin);
  }

  /**
   * GET wrapper for HTTP request
   *
   * @param $node
   *   API node
   * @param $getParams
   *   GET parameters
   *
   * @return array
   *   Response to API request
   */
  public function get($node, $getParams = array()) {
    $url = "{$this->apiUri}/{$node}";
    return $this->http($url, 'GET', array(), $getParams);
  }

  /**
   * POST wrapper for HTTP request
   *
   * @param string $node
   *   API node
   * @param array $postParams
   *   POST parameters
   * @param array $getParams
   *   GET parameters
   *
   * @return array
   *   Response to API request
   */
  public function post($node, $postParams = array(), $getParams = array()) {
    $url = "{$this->apiUri}/{$node}";
    return $this->http($url, 'POST', $postParams, $getParams);
  }

  /**
   * Make a HTTP request
   *
   * @param string $url
   *   API request URL
   * @param string $method
   *   HTTP request method
   * @param array $postParams
   *   POST parameters
   * @param array $getParams
   *   GET parameters
   *
   * @return string
   *   Response to API request
   */
  public function http($url, $method, $postParams = array(), $getParams = array()) {
    $url = $this->appendQueryString($url, $getParams);

    $ci = curl_init();
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer);
    curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'setHeader'));
    curl_setopt($ci, CURLOPT_HEADER, FALSE);

    if ('POST' == $method) {
      curl_setopt($ci, CURLOPT_POST, TRUE);
      if (!empty($postParams)) {
        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($postParams));
      }
    }
    elseif ('DELETE' == $method) {
      curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
      if (!empty($postParams)) {
        $url = $this->appendQueryString($url, $postParams);
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

  /**
   * Append query string to the URL
   *
   * @param string $url
   * @param array $query
   *
   * @return string
   */
  private function appendQueryString($url, $query) {
    if (!empty($query)) {
      $urlParts = explode('?', $url);
      $url = $urlParts[0];

      if (isset($urlParts[1])) {
        $url .= '?' . $urlParts[1];
      }
      if (FALSE !== strpos($url, '?')) {
        $url .= '&';
      }
      else {
        $url .= '?';
      }
      $url .= http_build_query($query);
    }
    return $url;
  }

}
