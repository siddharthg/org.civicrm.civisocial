<?php
/**
 * Create a social user.
 *
 * @param array $params
 *
 * @return int|bool
 *   Returns ID if exits, FALSE otherwise
 */
function civicrm_api3_civisocial_user_socialUserExists($params) {
  civicrm_api3_verify_mandatory($params, NULL, array('social_user_id', 'oauth_provider'));
  return CRM_Civisocial_BAO_CivisocialUser::socialUserExists($params['social_user_id'], $params['oauth_provider']);
}

/**
 * Create a social user.
 *
 * @param array $params
 *
 * @return array
 *   Array of created values
 */
function civicrm_api3_civisocial_user_create($params) {
  civicrm_api3_verify_mandatory($params, NULL, array('contact_id', 'social_user_id', 'oauth_provider'));
  return _civicrm_api3_basic_create('CRM_Civisocial_BAO_CivisocialUser', $params);
}

/**
 * Creates a contact if doesn't exist and returns it's id.
 *
 * @param array $params
 *
 * @return int
 *   Contact id of created/existing contact
 */
function civicrm_api3_civisocial_user_createContact($params) {
  civicrm_api3_verify_mandatory($params, NULL, array('email, contact_type'));
  return CRM_Civisocial_BAO_CivisocialUser::createContact($params);
}

/**
 * Fetches Facebook event information
 *
 * @param array $params
 *
 * @return array
 *   Array of Facebook event information or error messgaes
 */
function civicrm_api3_civisocial_user_getFacebookEventInfo($params) {
  civicrm_api3_verify_mandatory($params, NULL, array('event_id'));

  $session = CRM_Core_Session::singleton();
  $fbAccessToken = $session->get('facebook_access_token');
  if ($fbAccessToken) {
    $facebook = new CRM_Civisocial_OAuthProvider_Facebook($fbAccessToken);
    if ($facebook->isAuthorized()) {
      $eventId = $params['event_id'];
      $eventInfo = $facebook->get($eventId, array('fields' => 'name,description,place,start_time,end_time'));
      if ($eventInfo) {
        $eventInfo['description'] = nl2br($eventInfo['description']);
        return $eventInfo;
      }
      else {
        return civicrm_api3_create_error("The facebook event either doesn't exist or is private.");
      }
    }
  }
  return civicrm_api3_create_error("Not connected to Facebook.");
}

/**
 * Makes a post to Facebook and/or Twitter
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_civisocial_user_updateStatus($params) {
  civicrm_api3_verify_mandatory($params, NULL, array('post_content'));

  $session = CRM_Core_Session::singleton();
  $response = array();

  if (isset($params['facebook'])) {
    $pageId = $session->get('facebook_page_id');
    $fbAccessToken = $session->get('facebook_page_access_token');
    if ($pageId && $fbAccessToken) {
      // Connected to page
      $facebook = new CRM_Civisocial_OAuthProvider_Facebook($fbAccessToken);
      // Check if token is still valid
      $pageInfo = $facebook->get("{$pageId}?fields=name,picture");
      if ($pageInfo) {
        // Token valid
        $post['message'] = $params['post_content'];
        $result = $facebook->post("{$pageId}/feed", $post);
        $response['facebook']['post_id'] = $result['id'];
      }
      else {
        return civicrm_api3_create_error("Invalid Facebook access token.");
      }
    }
    else {
      return civicrm_api3_create_error("Not connected to Facebook.");
    }
  }

  if (isset($params['twitter'])) {
    $twitterId = $session->get('twitter_id');
    $twitterAccessToken = $session->get('twitter_access_token');
    if ($twitterId && $twitterAccessToken) {
      // Connected to Twitter
      $twitter = new CRM_Civisocial_OAuthProvider_Twitter($twitterAccessToken);
      // Check if token is still valid
      if ($twitter->isAuthorized()) {
        $post['status'] = $params['post_content'];
        $result = $twitter->post('statuses/update', $post);
        if ($result && $result['id']) {
          $response['twitter']['tweet_id'] = $result['id'];
        }
      }
      else {
        return civicrm_api3_create_error("Invalid Twitter access token.");
      }
    }
    else {
      return civicrm_api3_create_error("Not connected to Twitter.");
    }
  }

  return $response;
}
