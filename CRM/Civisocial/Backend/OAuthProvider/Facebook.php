<?php
require_once 'CRM/Civisocial/Backend/OAuthProvider.php';

class CRM_Civisocial_Backend_OAuthProvider_Facebook extends CRM_Civisocial_Backend_OAuthProvider {

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
   *        Preobtained access token. Makes the OAuth Provider ready
   *        to make requests.
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
   *        Permissions to be requested
   * @params bool $reRequest
   *        Facebook requires that app specifies if it is rerequest
   *        or it won't show the login dialog
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
      $params['scope'] = implode(',', $permissions);
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
      $requestOrigin = CRM_Utils_System::url('civicrm', NULL, TRUE);
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
    if (isset($response['error'])) {
      exit($response['error']);
    }

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
    $this->login($this->alias, $this->token, $facebookUserId);

    if (!CRM_Civisocial_BAO_CivisocialUser::socialUserExists($facebookUserId, $this->alias)) {
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
        'social_user_id' => $facebookUserId,
        'access_token' => $this->token,
      // @todo: Rename oauth_object in table to oauth_secret?
        'backend' => $this->alias,
        'created_date' => time(), // @todo: Created Date not being recorded
      );

      CRM_Civisocial_BAO_CivisocialUser::create($socialUser);
    }

    CRM_Core_Session::setStatus(ts('Login via Facebook successful.'), ts('Login Successful'), 'success');
    // @todo: Is status shown on public pages?
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
    $response = $this->get('me');
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
   *        Permissions to check if they have been granted
   * @return array
   *        An array of permissions that were denied
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
   *        FALSE if authorization fails
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
   * GET wrapper for Facebook HTTP request
   * @param string $node
   *        API node
   * @param array $params
   *        GET/POST parameters
   * @param string $method
   *        HTTP method (GET/POST)
   *
   * @return array
   */
  public function http($node, $params = array(), $method = 'GET') {
    if ($this->token) {
      $params['access_token'] = $this->token;
    }
    $response = parent::http($node, $params, $method);
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
