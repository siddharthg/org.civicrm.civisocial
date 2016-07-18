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
  // @todo: Add `email` after fixing Twitter's email retrieval
  civicrm_api3_verify_mandatory($params, NULL, array('contact_type'));
  return CRM_Civisocial_BAO_CivisocialUser::createContact($params);
}

function civicrm_api3_civisocial_user_getFacebookEventInfo($params) {
  $session = CRM_Core_Session::singleton();
  $fbAccessToken = $session->get('facebook_access_token');
  if ($fbAccessToken) {
    $facebook = new CRM_Civisocial_OAuthProvider_Facebook($fbAccessToken);
    if ($facebook->isAuthorized()) {
      $eventId = $params['event_id'];
      $eventInfo = $facebook->get($eventId, array('fields' => 'name,description,place,start_time,end_time'));
      if ($eventInfo) {
        $startTime = strtotime($eventInfo['start_time']);
        $endTime = strtotime($eventInfo['end_time']);
        unset($eventInfo['start_time']);
        unset($eventInfo['end_time']);

        $eventInfo['start_date'] = date('m/d/Y', $startTime);
        $eventInfo['start_time'] = date('h:iA', $startTime);
        $eventInfo['end_date'] = date('m/d/Y', $endTime);
        $eventInfo['end_time'] = date('h:iA', $endTime);
        return $eventInfo;
      }
      else {
        return civicrm_api3_create_error("The facebook event either doesn't exist or is private.");
      }
    }
  }
  return civicrm_api3_create_error("Not connected to Facebook.");
}
