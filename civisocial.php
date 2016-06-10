<?php

require_once 'civisocial.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function civisocial_civicrm_config(&$config) {
  _civisocial_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * git s * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function civisocial_civicrm_xmlMenu(&$files) {
  _civisocial_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function civisocial_civicrm_install() {
  _civisocial_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function civisocial_civicrm_uninstall() {
  _civisocial_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function civisocial_civicrm_enable() {
  _civisocial_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function civisocial_civicrm_disable() {
  _civisocial_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function civisocial_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _civisocial_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function civisocial_civicrm_managed(&$entities) {
  _civisocial_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function civisocial_civicrm_caseTypes(&$caseTypes) {
  _civisocial_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function civisocial_civicrm_angularModules(&$angularModules) {
  _civisocial_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function civisocial_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _civisocial_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Include the settings page in civicrm navigation menu.
 */
function civisocial_civicrm_navigationMenu(&$params) {
  $maxID = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_navigation") + 300;

  $civisocial_settings_url = "civicrm/admin/setting/preferences/civisocial";

  $administerMenuID = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Navigation', 'Administer', 'id', 'name');

  $params[$administerMenuID]['child'][$maxID + 1] = array(
    'attributes' => array(
      'label' => 'Civisocial Credentials',
      'name' => 'Civisocial Credentials',
      'url' => $civisocial_settings_url,
      'permission' => 'administer CiviReport',
      'operator' => NULL,
      'separator' => NULL,
      'parentID' => $administerMenuID,
      'navID' => $maxID + 1,
      'active' => 1,
    ),
  );
}

/**
 * Include the social buttons. Show the logged in user information if already
 * logged in.
 */
function civisocial_civicrm_buildForm($formName, &$form) {
  $session = CRM_Core_Session::singleton();
  $smarty = CRM_Core_Smarty::singleton();

  // @todo: Add this to head
  CRM_Core_Resources::singleton()->addStyleFile('org.civicrm.civisocial', 'templates/res/css/civisocial.css');

  $currentUrl = CRM_Utils_System::url(ltrim($_SERVER['REQUEST_URI'], '/'), NULL, TRUE, NULL, FALSE);
  $session->set('civisocialredirect', $currentUrl);

  if ($session->get('civisocial_logged_in')) {
    // User is connected to some social network
    $oAuthProvider = ucwords($session->get('civisocial_oauth_provider'));
    $token = $session->get('access_token');
    $className = "CRM_Civisocial_OAuthProvider_" . $oAuthProvider;
    $oap = new $className($token);

    // Check if the user is still authorized
    if ($oap->isAuthorized()) {
      $oAuthUser = $oap->getUserProfile();
      $smarty->assign("oAuthProvider", $oAuthProvider);
      $smarty->assign("name", $oAuthUser['name']);
      $smarty->assign("profileUrl", $oAuthUser['profile_url']);

      CRM_Core_Region::instance('page-header')->add(array(
        'template' => "LoggedIn.tpl",
      ));
      CRM_Core_Region::instance('page-body')->add(array(
        'template' => "LoggedIn.tpl",
      ));
      
      // Populate fields
      $defaults = array();
      $defaults['email-5'] = $oAuthUser['email'];
      $defaults['email-Primary'] = $oAuthUser['email'];
      $defaults['first_name'] = $oAuthUser['first_name'];
      $defaults['billing_first_name'] = $oAuthUser['first_name'];
      if ($oAuthUser['last_name']) {
        $defaults['last_name'] = $oAuthUser['last_name'];
        $defaults['billing_last_name'] = $oAuthUser['last_name'];
      }

      $form->setDefaults($defaults);
      return;
    }
  }

  // User is not connected to any network
  CRM_Core_Region::instance('page-header')->add(array(
    'template' => "SocialButtons.tpl",
  ));
  CRM_Core_Region::instance('page-body')->add(array(
    'template' => "SocialButtons.tpl",
  ));
}
