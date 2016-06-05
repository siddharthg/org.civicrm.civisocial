<?php
require_once 'CRM/Civisocial/Backend/OAuthProvider.php';
require_once 'CRM/Civisocial/Backend/OAuthProvider/OAuth/OAuth.php';

class CRM_Civisocial_Backend_OAuthProvider_Twitter extends CRM_Civisocial_Backend_OAuthProvider {

  /**
   * Short name (alias) for OAuth provider
   *
   * @var string
   */
  private $alias = "twitter";

  /**
   * Contains the HTTP header from the last request
   *
   * @var string
   */
  private $httpHeader;

  /**
   * Construct Twitter OAuth object
   *
   * @param string $accessToken
   *        Preobtained access token. Makes the OAuth Provider ready
   *        to make requests.
   */
  public function __construct($accessToken = NULL) {
    $this->apiUri = 'https://api.twitter.com/1.1/';
    $this->getApiCredentials($this->alias);

    // Twitter, why you no upgrade to OAuth 2.0?
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($this->apiKey, $this->apiSecret);

    if ($accessToken && isset($accessToken['oauth_token']) && isset($accessToken['oauth_token_secret'])) {
      $this->token = new OAuthConsumer($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
    }
  }

  /**
   * Authorization URI that user will be redirected to for login
   *
   * @return string | bool
   */
  public function getLoginUri() {
    $tempCredentials = $this->getRequestToken($this->getCallbackUri($this->alias));
    // var_dump($tempCredentials); exit;
    $session = CRM_Core_Session::singleton();
    $session->set('oauth_token', $tempCredentials['oauth_token']);
    $session->set('oauth_token_secret', $tempCredentials['oauth_token_secret']);

    return $this->getAuthorizeURL($tempCredentials['oauth_token']);
  }

  /**
   * Process information returned by OAuth provider after login
   */
  public function handleCallback() {
    parent::handleCallback();
    $session = CRM_Core_Session::singleton();
    $requestOrigin = $session->get("civisocialredirect");
    if (!$requestOrigin) {
      $requestOrigin = CRM_Utils_System::url('civicrm', NULL, TRUE);
      // @todo: What if the user is not logged in? Make it home url?
    }

    // Get temporary credentials from the session
    $requestToken = array();
    $requestToken['oauth_token'] = $session->get('oauth_token');
    $requestToken['oauth_token_secret'] = $session->get('oauth_token_secret');

    if (isset($_REQUEST['denied'])) {
      CRM_Utils_System::redirect($requestOrigin);
      // @todo: Find a way to show the error message
    }

    // If the oauth_token is not what we expect, bail
    if (isset($_REQUEST['oauth_token']) && $requestToken['oauth_token'] !== $_REQUEST['oauth_token']) {
      // Not a valid callback.
      CRM_Utils_System::redirect($requestOrigin);
      // @todo: Find a way to show the error message
    }

    $this->token = new OAuthConsumer($requestToken['oauth_token'], $requestToken['oauth_token_secret']);

    // Request Access Token from twitter
    $accessToken = $this->getAccessToken($_REQUEST['oauth_verifier']);
    // @todo: Check HTTP code

    $session->set('access_token', $accessToken);

    // Remove no longer needed request tokens
    $session->set('oauth_token', NULL);
    $session->set('oauth_token_secret', NULL);
    //@todo: Can't I UNSET using Session class?

    $this->token = new OAuthConsumer($accessToken['oauth_token'], $accessToken['oauth_token_secret']);

    $userProfile = array();
    if ($this->isAuthorized()) {
      $userProfile = $this->getUserProfile();
    }
    else {
      // Start over
      CRM_Utils_System::redirect($this->getLoginUri());
    }

    $twitterUserId = CRM_Utils_Array::value("id", $userProfile);
    $this->login($this->alias, $accessToken, $twitterUserId);

    if (!CRM_Civisocial_BAO_CivisocialUser::socialUserExists($twitterUserId, $this->alias)) {
      $user = array(
        'first_name' => CRM_Utils_Array::value("name", $userProfile),
        'last_name' => '',
        'display_name' => CRM_Utils_Array::value("name", $userProfile),
        'preffered_language' => CRM_Utils_Array::value("lang", $userProfile),
        'gender' => NULL,
        'email' => CRM_Utils_Array::value("email", $userProfile),
        'contact_type' => 'Individual',
      );

      // Create contact
      $contactId = CRM_Civisocial_BAO_CivisocialUser::createContact($user);

      // Create social user
      $socialUser = array(
        'contact_id' => $contactId,
        'social_user_id' => $twitterUserId,
        'access_token' => $accessToken['oauth_token'],
                // @todo: Rename oauth_object in table to oauth_secret?
        'oauth_object' => $accessToken['oauth_token_secret'],
        'backend' => $this->alias,
        'created_date' => time(), // @todo: Created Date not being recorded
      );

      CRM_Civisocial_BAO_CivisocialUser::create($socialUser);
    }

    CRM_Core_Session::setStatus(ts('Login via Twitter successful.'), ts('Login Successful'), 'success');
    // @todo: Is status shown on public pages?
    CRM_Utils_System::redirect($requestOrigin);
  }

  /**
   * Get if the user is connected to OAuth provider and authorized
   *
   * @returns bool
   */
  public function isAuthorized() {
    if ($this->token && isset($this->userProfile)) {
      return TRUE;
    }

    $userProfile = $this->get('account/verify_credentials.json?include_email=true');
    if (200 == $this->httpCode) {
      $this->userProfile = $userProfile;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the connected app has certain permission.
   * Requires isAuthorized() have been called first.
   *
   * @param string $permission
   *
   * @return bool
   *        FALSE if the permssion has not been granted or
   *        the request failed
   *
   * @todo: A permission string have more than one permissions
   *            eg. read-write has read and write permission
   */
  public function checkPermissions($permission) {
    $header = $this->getHeader();
    if ($header['x_access_level'] == $permission) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get a request_token from Twitter
   *
   * @return    array
   *         a key/value array containing oauth_token and oauth_token_secret
   */
  public function getRequestToken($oauthCallback) {
    $parameters = array();
    $parameters['oauth_callback'] = $oauthCallback;
    $request = $this->oAuthRequest('https://api.twitter.com/oauth/request_token', 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * Get the authorize URL
   *
   * @return string
   */
  public function getAuthorizeURL($token, $sign_in_with_twitter = TRUE) {
    if (is_array($token)) {
      $token = $token['oauth_token'];
    }
    if (empty($sign_in_with_twitter)) {
      return "https://api.twitter.com/oauth/authorize?oauth_token={$token}";
    }
    else {
      return "https://api.twitter.com/oauth/authenticate?oauth_token={$token}";
    }
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
   * Exchange request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @return array
   *        OAuth token and secret
   */
  public function getAccessToken($oauth_verifier) {
    $params = array();
    $params['oauth_verifier'] = $oauth_verifier;
    $request = $this->oAuthRequest('https://api.twitter.com/oauth/access_token', 'GET', $params);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * GET wrapper for oAuthRequest.
   */
  public function get($url, $params = array()) {
    $response = $this->oAuthRequest($url, 'GET', $params);
    return json_decode($response, TRUE);
  }

  /**
   * POST wrapper for oAuthRequest.
   */
  public function post($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'POST', $parameters);
    return json_decode($response, TRUE);
  }

  /**
   * DELETE wrapper for oAuthReqeust.
   */
  public function delete($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'DELETE', $parameters);
    return json_decode($response, TRUE);
  }

  /**
   * Format and sign an OAuth / API request
   */
  private function oAuthRequest($url, $method, $parameters) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
      $url = "{$this->apiUri}{$url}.json";
    }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
      case 'GET':
        return $this->http($request->to_url(), 'GET');

      default:
        return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
    }
  }

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  public function http($url, $method, $postfields = NULL) {
    $this->httpInfo = array();
    $ci = curl_init();
    /* Curl settings */
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
        if (!empty($postfields)) {
          curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
        }
        break;

      case 'DELETE':
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($postfields)) {
          $url = "{$url}?{$postfields}";
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
