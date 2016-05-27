<?php
require_once 'CRM/Civisocial/Backend/SocialMedia.php';

class CRM_Civisocial_Backend_SocialMedia_Googleplus extends CRM_Civisocial_Backend_SocialMedia {

	/**
	 * Short name (alias) for OAuth provider
	 *
	 * @var string
	 */
	private $alias = 'googleplus';

	/**
	 * Google Plus user information
	 *
	 * @var array
	 */
	private $userProfile;

	public function __construct() {
		$this->apiUri = 'https://www.googleapis.com/oauth2/v3';
		$this->getApiCredentials($this->alias);
	}
	
	public function getLoginUri() {
		$uri = 'https://accounts.google.com/o/oauth2/auth';

		$params = array(
			'scope' => 'email profile',
			'response_type' => 'code',
			'client_id' => $this->apiKey,
			'redirect_uri' => $this->getCallbackUri($this->alias),
		);

		// URL decode because Google wants space intact in scope parameter
		return urldecode($uri."?".http_build_query($params));
	}

	/**
	 * Process information returned by OAuth provider after login
	 */
	public function handleCallback() {
		$session = CRM_Core_Session::singleton();
		$requestOrigin = $session->get("civisocialredirect");
		if (!$requestOrigin) {
			$requestOrigin = CRM_Utils_System::url('civicrm', NULL, TRUE);
			// @todo: What if the user is not logged in? Make it home url?
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
		// @todo: Check if all the required scopes are granted
		$this->login($this->alias, $this->token);

		// // Authentication is successfull. Fetch user profile
		// $userProfile = $this->get('userinfo', array('access_token' => $accessToken, 'alt' => 'json'));
		// Authentication is successful. Fetch user profile
		$userProfile = array();
	    if ($this->isAuthorized()) {
	    	$userProfile = $this->getUserProfile();
	    } else {
	    	// Start over
	    	CRM_Utils_System::redirect($this->getLoginUri());
	    }

	    $googleplusUserId = CRM_Utils_Array::value("sub", $userProfile);

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
	
	public function IsAuthorized() {
		if (isset($this->userProfile)) {
			return TRUE;
		}
		$response = $this->get('userinfo', array('access_token' => $this->token, 'alt' => 'json'));
		if (isset($response['error'])) {
			if ($response['error'] == 'invalid_token') {
				// Invalid access token
				return FALSE;
			} else {
				// Non-access token related error.
				exit($response['error'].'<br/>'.$response['error_description']);
			}
		} else {
			$this->userProfile = $response;
			return TRUE;
		}
	}
	
	public function getUserProfile() {
		return $this->userProfile;
	}

}
