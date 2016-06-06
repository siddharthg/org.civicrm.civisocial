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
  civicrm_api3_verify_mandatory($params, NULL, array('social_user_id', 'backend'));
  return CRM_Civisocial_BAO_CivisocialUser::socialUserExists($params['social_user_id'], $params['backend']);
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
  civicrm_api3_verify_mandatory($params, NULL, array('contact_id', 'social_user_id', 'backend'));
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
  civicrm_api3_verify_mandatory($params, NULL, array('contact_type', 'display_name'));
  return CRM_Civisocial_BAO_CivisocialUser::createContact($params);
}
