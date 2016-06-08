<?php
require_once 'CRM/Civisocial/OAuthProvider/OAuth/OAuth.php';

class CRM_Civisocial_OAuthProvider_Twitter extends CRM_Civisocial_OAuthProvider {

  /**
   * Short name (alias) for OAuth provider
   *
   * @var string
   */
  private $alias = "twitter";

  /**
   * Construct Twitter OAuth object
   *
   * @param string $accessToken
   *   Preobtained access token. Makes the OAuth Provider ready
   *   to make requests.
   */
  public function __construct($accessToken = NULL) {
    $this->apiUri = 'https://api.twitter.com/1.1';
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
   * @return string|bool
   */
  public function getLoginUri() {
    $tempCredentials = $this->getRequestToken($this->getCallbackUri($this->alias));
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
      $requestOrigin = CRM_Utils_System::url('', NULL, TRUE);
      // @todo: What if the user is not logged in? Make it home url?
    }

    // Check if the user denied acccess
    if (isset($_GET['denied'])) {
      CRM_Utils_System::redirect($requestOrigin);
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
    $contactId = civicrm_api3(
      'CivisocialUser',
      'socialuserexists',
      array(
        'social_user_id' => $twitterUserId,
        'oauth_provider' => $this->alias,
      )
    );

    if (!$contactId) {
      $user = array(
        'first_name' => CRM_Utils_Array::value("name", $userProfile),
        'last_name' => '',
        'display_name' => CRM_Utils_Array::value("name", $userProfile),
        'preffered_language' => CRM_Utils_Array::value("lang", $userProfile),
        'gender' => NULL,
        'email' => CRM_Utils_Array::value("email", $userProfile),
        'contact_type' => 'Individual',
      );

      // Find/create contact to map with social user
      $contactId = civicrm_api3('CivisocialUser', 'createcontact', $user);

      // Create social user
      $socialUser = array(
        'contact_id' => $contactId,
        'social_user_id' => $twitterUserId,
        'oauth_token' => $accessToken['oauth_token'],
        'oauth_secret' => $accessToken['oauth_token_secret'],
        'oauth_provider' => $this->alias,
        'created_date' => time(), // @todo: Created Date not being recorded
      );

      civicrm_api3('CivisocialUser', 'create', $socialUser);
    }
    $this->login($this->alias, $accessToken, $twitterUserId, $contactId);
    CRM_Utils_System::redirect($requestOrigin);
  }

  /**
   * Get if the user is connected to OAuth provider and authorized
   *
   * @return bool
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
   * @param array $permission
   *   Possible values: read, write, directmessages
   *
   * @return bool
   *   FALSE if one or more permssions have not been granted or
   *   the request failed
   *
   * @todo: A permission string has more than one permissions
   *       eg. read-write has read and write permission
   */
  public function checkPermissions($permissions) {
    $header = $this->getHeader();
    $accessLevel = $header['x_access_level'];
    foreach ($permissions as $permission) {
      if (FALSE === strpos($accessLevel, $permission)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get a request_token from Twitter.
   *
   * @param string $oauthCallback
   *   URI that will be redirected to after the user authorizes the app
   * 
   * @return array
   *   A key/value array containing oauth_token and oauth_token_secret
   */
  public function getRequestToken($oauthCallback) {
    $params = array();
    $params['oauth_callback'] = $oauthCallback;
    $request = $this->oAuthRequest('https://api.twitter.com/oauth/request_token', 'GET', $params);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * Get the authorize URL
   *
   * @param mixed $requestToken
   *   Request token obtained from Twitter
   * @param bool $silentSignIn
   *   If FALSE the user will see 'Authorize App' screen regardless if they
   *   they have previously authorized the app.
   *
   * @return string
   */
  public function getAuthorizeURL($requestToken, $silentSignIn = TRUE) {
    if (is_array($requestToken)) {
      $requestToken = $requestToken['oauth_token'];
    }
    if ($silentSignIn) {
      return "https://api.twitter.com/oauth/authenticate?oauth_token={$requestToken}";
    }
    else {
      return "https://api.twitter.com/oauth/authorize?oauth_token={$requestToken}";
    }
  }

  /**
   * Exchange request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @param string $oauthVerifier
   *   OAuth Verifier string provided by Twitter
   *
   * @return array
   *   OAuth token and secret
   */
  public function getAccessToken($oauthVerifier) {
    $params = array();
    $params['oauth_verifier'] = $oauthVerifier;
    $request = $this->oAuthRequest('https://api.twitter.com/oauth/access_token', 'GET', $params);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * GET wrapper for oAuthRequest.
   *
   * @param string $node
   *   Twitter REST API node
   * @param array $params
   *   Parameters to REST API
   *
   * @return array
   *   Response from Twitter REST API
   */
  public function get($node, $params = array()) {
    $response = $this->oAuthRequest($node, 'GET', $params);
    return json_decode($response, TRUE);
  }

  /**
   * POST wrapper for oAuthRequest.
   *
   * @param string $node
   *   Twitter REST API node
   * @param array $params
   *   Parameters to REST API
   *
   * @return array
   *   Response from Twitter REST API
   */
  public function post($node, $params = array()) {
    $response = $this->oAuthRequest($node, 'POST', $params);
    return json_decode($response, TRUE);
  }

  /**
   * DELETE wrapper for oAuthRequest.
   *
   * @param string $node
   *   Twitter REST API node
   * @param array $params
   *   Parameters to REST API
   *
   * @return array
   *   Response from Twitter REST API
   */
  public function delete($node, $params = array()) {
    $response = $this->oAuthRequest($node, 'DELETE', $params);
    return json_decode($response, TRUE);
  }

  /**
   * Format and sign an OAuth / API request
   *
   * @param string $node
   *   Twitter REST API node
   * @param array $params
   *   Parameters to REST API
   *
   * @return array
   *   Response from Twitter REST API
   */
  private function oAuthRequest($node, $method, $params) {
    if (strrpos($node, 'https://') !== 0 && strrpos($node, 'http://') !== 0) {
      $url = "{$this->apiUri}/{$node}.json";
    }
    else {
      $url = $node;
    }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $params);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
      case 'GET':
        return $this->http($request->to_url(), 'GET');

      default:
        return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
    }
  }

}
