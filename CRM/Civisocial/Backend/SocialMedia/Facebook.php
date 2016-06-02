<?php
require_once 'CRM/Civisocial/Backend/SocialMedia.php';

class CRM_Civisocial_Backend_SocialMedia_Facebook extends CRM_Civisocial_Backend_SocialMedia {

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
	 *		Preobtained access token. Makes the OAuth Provider ready
	 *		to make requests.
	 */
	public function __construct($accessToken = NULL) {
		$this->apiUri = 'https://graph.facebook.com/v2.6';
		$this->getApiCredentials($this->alias);
		$this->token = $accessToken;
	}

	/**
	 * Authorization URI that user will be redirected to for login
	 *
	 * @return string | bool
	 */
	public function getLoginUri() {
		$uri = 'https://www.facebook.com/dialog/oauth';
		$params = array(
			'client_id' => $this->apiKey,
			'redirect_uri' => $this->getCallbackUri($this->alias),
		);
		return $uri."?".http_build_query($params);
	}

	/**
	 * Process authentication information returned by OAuth provider after login
	 */
	public function handleCallback() {
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

		$response = $this->http('oauth/access_token', $params);
		if (isset($response['error'])) {
			exit($response['error']);
		}

		$this->token = CRM_Utils_Array::value('access_token', $response);
		// @todo: Check if all the required scopes are granted
		$this->login($this->alias, $this->token);

		// Authentication is successful. Fetch user profile
		$userProfile = array();
	    if ($this->isAuthorized()) {
	    	$userProfile = $this->getUserProfile();
	    } else {
	    	// Start over
	    	CRM_Utils_System::redirect($this->getLoginUri());
	    }

	    $facebookUserId = CRM_Utils_Array::value("id", $userProfile);

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
	
	public function isAuthorized() {
		if ($this->token && isset($this->userProfile)) {
			return TRUE;
		}
		
		$response = $this->get('me', array('access_token' => $this->token));
		if (isset($response['error'])) {
			if ($response['error']['type'] == 'OAuthException') {
				// Invalid access token
				return FALSE;
			} else {
				// Non-access token related error.
				exit($response['error']['message']);
			}
		} else {
			$this->userProfile = $response;

			return TRUE;
		}
	}

}
