<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.6                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
 */
/**
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 *
 */
class CRM_Civisocial_BAO_CivisocialUser {

  public static function get($params) {
    $result = array();
    $instance = new CRM_Civisocial_DAO_CivisocialUser();
    if (!empty($params)) {
      $fields = $instance->fields();
      foreach ($params as $key => $value) {
        if (isset($fields[$key])) {
          $instance->$key = $value;
        }
      }
    }
    $instance->find();
    while ($instance->fetch()) {
      $row = array();
      $instance->storeValues($instance, $row);
      $result[$row['id']] = $row;
    }
    return $result;
  }

  /**
   * Create social user
   *
   * @param string $params
   */
  public static function create($params) {
    $className = 'CRM_Civisocial_DAO_CivisocialUser';
    $entityName = 'CivisocialUser';
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);
    return $instance;
  }

  /**
   * Create contact
   *
   * @param array $userInfo
   *
   * @return int
   *        Contact ID of created or existing contact
   */
  public static function createContact($userInfo) {
    $email = CRM_Utils_Array::value("email", $userInfo);
    $contacts = civicrm_api3(
    'contact',
    'get',
    array("email" => $email)
    );

    if (($contacts["count"] == 0) || ($email == NULL)) {
      $result = civicrm_api3('Contact', 'create', $userInfo);
      return $result["id"];
    }
    else {
      $contactId = 0;
      foreach ($contacts["values"] as $key => $value) {
        $contactId = $key;
        // @todo: Update the contact with the new info
      }
      return $contactId;
    }
  }

  /**
   * Check if social media user already exists
   *
   * @param int $socialUserId
   * @return int | bool
   */
  public static function socialUserExists($socialUserId, $backend) {
    $result = self::get(array("social_user_id" => $socialUserId, "backend" => $backend));
    if (count($result) > 0) {
      $civisocialId = 0;
      foreach ($result as $key => $value) {
        $civisocialId = $key;
      }
      $contactId = $result[$civisocialId]["contact_id"];
      return $contactId;
    }
    else {
      return FALSE;
    }
  }

}
