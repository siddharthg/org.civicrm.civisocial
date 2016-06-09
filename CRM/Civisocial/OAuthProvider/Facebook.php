<?php

class CRM_Civisocial_OAuthProvider_Facebook extends CRM_Civisocial_OAuthProvider {

  /**
   * Short name (alias) for OAuth provider
   *
   * @var string
   */
  private $alias = 'facebook';

  /**
   * Construct Facebook OAuth object
   *
   * @param string $accessToken
   *   Preobtained access token. Makes the OAuth Provider ready
   *   to make requests.
   */
  public function __construct($accessToken = NULL) {
    $this->apiUri = 'https://graph.facebook.com/v2.6';
    $this->getApiCredentials($this->alias);
    $this->token = $accessToken;
  }

  /**
   * Authorization URI that user will be redirected to for login
   *
   * @param array $permissions
   *   Permissions to be requested
   * @params bool $reRequest
   *   Facebook requires that app specifies if it is rerequest
   *   or it won't show the login dialog
   *
   * @return string | bool
   * @todo Check if requests have been reviewed by Facebook
   */
  public function getLoginUri($permissions = array(), $reRequest = FALSE) {
    $uri = 'https://www.facebook.com/dialog/oauth';
    $params = array(
      'client_id' => $this->apiKey,
      'redirect_uri' => $this->getCallbackUri($this->alias),
    );
    if (empty($permissions)) {
      $params['scope'] = implode(',', array_merge($this->getBasicPermissions(), $this->getExtraPermissions()));
    }
    else {
      $params['scope'] = implode(',', array_merge($this->getBasicPermissions(), $permissions));
    }
    if ($reRequest) {
      $params['auth_type'] = 'rerequest';
    }
    return $uri . "?" . http_build_query($params);
  }

  /**
   * Minimum permissions required to use the login
   */
  public function getBasicPermissions() {
    return array(
      'public_profile',
      'email',
    );
  }

  /**
   * Extra recommended permissions
   * 'rsvp_events' and 'publish_actions' require to be reviewed by
   * Facebook before the app can request it
   */
  public function getExtraPermissions() {
    return array(
      'user_likes',
      'rsvp_event',
      'publish_actions',
    );
  }

  /**
   * Process authentication information returned by OAuth provider after login
   */
  public function handleCallback() {
    parent::handleCallback();
    $session = CRM_Core_Session::singleton();
    $requestOrigin = $session->get("civisocialredirect");
    if (!$requestOrigin) {
      $requestOrigin = CRM_Utils_System::url('', NULL, TRUE);
    }

    // Check if the user denied acccess
    if (isset($_GET['error']) && $_GET['error'] = 'access_denied') {
      CRM_Utils_System::redirect($requestOrigin);
    }

    // Facebook sends a code to the callback url, this is further used to acquire
    // access token from facebook, which is needed to get all the data from facebook
    if (!isset($_GET['code'])) {
      exit("Invalid request.");
    }

    // Make an API request to obtain Access Token
    // GET params
    $params = array(
      'client_id' => $this->apiKey,
      'client_secret' => $this->apiSecret,
      'code' => CRM_Utils_Array::value('code', $_GET),
      'redirect_uri' => $this->getCallbackUri($this->alias),
    );
    $response = $this->get('oauth/access_token', $params);
    $this->token = CRM_Utils_Array::value('access_token', $response);

    // Check if all basic perimissions have been granted
    $deniedPermissions = $this->checkPermissions($this->getBasicPermissions());

    if (!empty($deniedPermissions)) {
      CRM_Utils_System::redirect($this->getLoginUri($deniedPermissions, TRUE));
      // @todo:	It would be better if we inform first (eg. You need to provide
      //			email to continue) and then provide a link to re-authorize
    }

    // Authentication is successful. Fetch user profile
    $userProfile = array();
    if ($this->isAuthorized()) {
      $userProfile = $this->getUserProfile();
    }
    else {
      // Start over
      CRM_Utils_System::redirect($this->getLoginUri());
    }

    $facebookUserId = CRM_Utils_Array::value("id", $userProfile);
    $contactId = civicrm_api3(
      'CivisocialUser',
      'socialuserexists',
      array(
        'social_user_id' => $facebookUserId,
        'oauth_provider' => $this->alias,
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
        'social_user_id' => $facebookUserId,
        'oauth_token' => $this->token,
        'oauth_provider' => $this->alias,
        'created_date' => time(), // @todo: Created Date not being recorded
      );

      civicrm_api3('CivisocialUser', 'create', $socialUser);
    }
    $this->login($this->alias, $this->token, $facebookUserId, $contactId);
    CRM_Utils_System::redirect($requestOrigin);
  }

  /**
   * Check if the user is connected to Facebook and authorized.
   * It can also be used to validate access tokens after setting one.
   *
   * @return bool
   */
  public function isAuthorized() {
    if ($this->token && isset($this->userProfile)) {
      return TRUE;
    }
    $response = $this->get('me?fields=id,first_name,last_name,name,locale,gender,email');
    if (!$response) {
      return FALSE;
    }
    $this->userProfile = $response;
    return TRUE;
  }

  /**
   * Check if all passed permissions have beeen granted
   *
   * @param array $permissions
   *   Permissions to check if they have been granted
   *
   * @return array
   *   An array of permissions that were denied
   */
  public function checkPermissions($permissions = array()) {
    $grantedPermissions = $this->getGrantedPermissions();
    if (count($permissions) > count($grantedPermissions)) {
      return FALSE;
    }
    return array_diff($permissions, $grantedPermissions);
  }

  /**
   * Get a list of granted permissions
   *
   * @return array | bool
   *   FALSE if authorization fails
   */
  public function getGrantedPermissions() {
    $response = $this->get('me/permissions');
    if ($response) {
      $grantedPermissions = array();
      foreach ($response['data'] as $permission) {
        if ($permission['status'] == 'granted') {
          $grantedPermissions[] = $permission['permission'];
        }
      }
      return $grantedPermissions;
    }
    return FALSE;
  }

  /**
   * Appends an access token, makes HTTP request and handles the repsonse
   *
   * @param string $url
   *   Request URL
   * @param array $params
   *   Request parameters
   * @param string $method
   *   HTTP method
   *
   * @return array
   */
  public function http($url, $method, $postParams = array(), $getParams = array()) {
    if ($this->token) {
      $getParams['access_token'] = $this->token;
    }
    $responseJson = parent::http($url, $method, $postParams, $getParams);
    $response = json_decode($responseJson, TRUE);
    if (isset($response['error'])) {
      if ($response['error']['type'] == 'OAuthException') {
        // Invalid access token
        return FALSE;
      }
      else {
        // Non-access token related error.
        exit($response['error']['message']);
      }
    }
    else {
      return $response;
    }
  }

}
