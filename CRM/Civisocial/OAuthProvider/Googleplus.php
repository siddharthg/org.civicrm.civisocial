<?php
require_once 'CRM/Civisocial/OAuthProvider.php';

class CRM_Civisocial_Backend_OAuthProvider_Googleplus extends CRM_Civisocial_Backend_OAuthProvider {

  /**
   * Short name (alias) for OAuth provider
   *
   * @var string
   */
  private $alias = 'googleplus';

  /**
   * Construct Google OAuth object
   *
   * @param string $accessToken
   *        Preobtained access token. Makes the OAuth Provider ready
   *        to make requests.
   */
  public function __construct($accessToken = NULL) {
    $this->apiUri = 'https://www.googleapis.com/oauth2/v3';
    $this->getApiCredentials($this->alias);
    $this->token = $accessToken;
  }

  /**
   * Authorization URI that user will be redirected to for login
   *
   * @param array $permissions
   *        Permissions to be requested
   *
   * @return string | bool
   */
  public function getLoginUri($permissions = array()) {
    $uri = 'https://accounts.google.com/o/oauth2/auth';

    $params = array(
      'response_type' => 'code',
      'client_id' => $this->apiKey,
      'redirect_uri' => $this->getCallbackUri($this->alias),
    );
    if (empty($permissions)) {
      // Google OAuth doesn't allow you to choose which permisions
      // to allow. So, extra permissions are not requested.
      $params['scope'] = implode(' ', $this->getBasicPermissions());
    }
    else {
      $params['scope'] = implode(' ', $permissions);
    }

    // URL decode because Google wants space intact in scope parameter
    return urldecode($uri . "?" . http_build_query($params));
  }

  /**
   * Minimum permissions required to use the login
   */
  public function getBasicPermissions() {
    return array(
      'https://www.googleapis.com/auth/plus.login',
      'https://www.googleapis.com/auth/plus.me',
      'https://www.googleapis.com/auth/userinfo.profile',
      'https://www.googleapis.com/auth/userinfo.email',
    );
  }

  /**
   * Extra recommended permissions
   *
   * @todo: Create an interface to ask these permissions or do we force
   *        users to grant all access in the beginning.
   */
  public function getExtraPermissions() {
    return array(
      'https://www.googleapis.com/auth/plus.stream.write',
    );
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
    }

    // Check if the user denied acccess
    if (isset($_GET['error']) && $_GET['error'] = 'access_denied') {
      CRM_Utils_System::redirect($requestOrigin);
    }

    // Google sends a code to the callback url, this is further used to acquire
    // access token from facebook, which is needed to get all the data from Google
    if (!isset($_GET['code'])) {
      exit("Invalid request.");
    }

    // Make an API request to obtain Access Token
    // POST params
    $params = array(
      'client_id' => $this->apiKey,
      'client_secret' => $this->apiSecret,
      'code' => CRM_Utils_Array::value('code', $_GET),
      'redirect_uri' => $this->getCallbackUri($this->alias),
      'grant_type' => 'authorization_code',
    );

    $response = $this->http('token', $params, 'POST');
    if (isset($response['error'])) {
      exit($response['error']);
    }

    $this->token = CRM_Utils_Array::value('access_token', $response);

    // Authentication is successful. Fetch user profile
    $userProfile = array();
    if ($this->isAuthorized()) {
      $userProfile = $this->getUserProfile();
    }
    else {
      // Start over
      CRM_Utils_System::redirect($this->getLoginUri());
    }

    $googleplusUserId = CRM_Utils_Array::value("sub", $userProfile);
    $this->login($this->alias, $this->token, $googleplusUserId);

    if (!CRM_Civisocial_BAO_CivisocialUser::socialUserExists($googleplusUserId, $this->alias)) {
      $user = array(
        'first_name' => CRM_Utils_Array::value("given_name", $userProfile),
        'last_name' => CRM_Utils_Array::value("family_name", $userProfile),
        'display_name' => CRM_Utils_Array::value("name", $userProfile),
        'preffered_language' => CRM_Utils_Array::value("locale", $userProfile),
        'gender' => CRM_Utils_Array::value("gender", $userProfile),
        'contact_type' => 'Individual',
      );
      if ($userProfile['email_verified']) {
        $user['email'] = CRM_Utils_Array::value("email", $userProfile);
      }

      // Create contact
      $contactId = CRM_Civisocial_BAO_CivisocialUser::createContact($user);

      // Create social user
      $socialUser = array(
        'contact_id' => $contactId,
        'social_user_id' => $googleplusUserId,
        'access_token' => $this->token,
      // @todo: Rename oauth_object in table to oauth_secret?
        'backend' => $this->alias,
        'created_date' => time(), // @todo: Created Date not being recorded
      );

      CRM_Civisocial_BAO_CivisocialUser::create($socialUser);
    }

    CRM_Core_Session::setStatus(ts('Login via Google successful.'), ts('Login Successful'), 'success');
    // @todo: Is status shown on public pages?
    CRM_Utils_System::redirect($requestOrigin);
  }

  /**
   * Check if the user is connected to Google and authorized.
   * It can also be used to validate access tokens after setting one.
   *
   * @return bool
   */
  public function isAuthorized() {
    if ($this->token && isset($this->userProfile)) {
      return TRUE;
    }
    $response = $this->get('userinfo');
    if (!$response) {
      return FALSE;
    }
    $this->userProfile = $response;
    return TRUE;
  }

  /**
   * GET wrapper for Google's HTTP request
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
    $params['alt'] = 'json';
    if ($this->token) {
      $params['access_token'] = $this->token;
    }
    $response = parent::http($node, $params, $method);
    var_dump($response);
    if (isset($response['error'])) {
      if ($response['error'] == 'invalid_token' || $response['error'] == 'invalid_request') {
        // Invalid access token
        // @todo: Log error
        return FALSE;
      }
      else {
        // Non-access token related error.
        exit($response['error'] . '<br/>' . $response['error_description']);
      }
    }
    else {
      return $response;
    }
  }

}
