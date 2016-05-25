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

	private static function check_existing_social_user($social_user_id) {
		$result = self::get(array("social_user_id" => $social_user_id));
		if (count($result) > 0) {
			$existing_civisocial_id = 0;
			foreach ($result as $key => $value) {
				$existing_civisocial_id = $key;
			}
			$contact_id = $result[$existing_civisocial_id]["contact_id"];
			return $contact_id;
		} else {
			return NULL;
		}
	}

	public static function handle_facebook_data($user_data_response, $access_token) {
		$existing_contact_id = self::check_existing_social_user(CRM_Utils_Array::value("id", $user_data_response));
		if ($existing_contact_id) {
			return $existing_contact_id;
		} else {
			$email = CRM_Utils_Array::value("email", $user_data_response);
			$contacts = civicrm_api3('contact', 'get', array("email" => $email));

			// User was not found or no email
			if (($contacts["count"] == 0) or ($email == NULL)) {
				// Create a new contact
				$params = array(
					'first_name' => CRM_Utils_Array::value("first_name", $user_data_response),
					'last_name' => CRM_Utils_Array::value("last_name", $user_data_response),
					'display_name' => CRM_Utils_Array::value("name", $user_data_response),
					'preffered_language' => CRM_Utils_Array::value("locale", $user_data_response),
					'gender' => CRM_Utils_Array::value("gender", $user_data_response),
					'email' => CRM_Utils_Array::value("email", $user_data_response),
					'contact_type' => 'Individual',
				);
				$result = civicrm_api3('Contact', 'create', $params);
				// Create a new civisocial user.
				$contact_id = $result["id"];
			} else {
				// Contact was found
				$contact_id = 0;
				foreach ($contacts["values"] as $key => $value) {
					$contact_id = $key;
				}
			}

			$dateTime = date('YmdHis', time());
			$params = array(
				'contact_id' => $contact_id,
				'social_user_id' => CRM_Utils_Array::value("id", $user_data_response),
				'access_token' => $access_token,
				'oauth_object' => CRM_Utils_Array::value("link", $user_data_response),
				'backend' => 'facebook',
				'created_date' => $dateTime,
				'modified_date' => $dateTime,
			);
			// exit( var_dump( $params ) );
			self::create($params);
			return $contact_id;
		}
	}

	public static function handle_googleplus_data($user_data_response, $access_token) {
		$existing_contact_id = self::check_existing_social_user(CRM_Utils_Array::value("sub", $user_data_response));
		if ($existing_contact_id) {
			return $existing_contact_id;
		} else {
			$email = CRM_Utils_Array::value("email", $user_data_response);
			$contacts = civicrm_api3('contact', 'get', array("email" => $email));
			// User was not found
			if (($contacts["count"] == 0) or ($email == NULL)) {
				// Create a new contact
				$params = array(
					'first_name' => CRM_Utils_Array::value("given_name", $user_data_response),
					'last_name' => CRM_Utils_Array::value("family_name", $user_data_response),
					'display_name' => CRM_Utils_Array::value("name", $user_data_response),
					'preffered_language' => CRM_Utils_Array::value("locale", $user_data_response),
					'gender' => CRM_Utils_Array::value("gender", $user_data_response),
					'email' => CRM_Utils_Array::value("email", $user_data_response),
					'contact_type' => 'Individual',
				);
				$result = civicrm_api3('Contact', 'create', $params);
				// Create a new civisocial user.
				$contact_id = $result["id"];
			} else {
				// Contact was found
				$contact_id = 0;
				foreach ($contacts["values"] as $key => $value) {
					$contact_id = $key;
				}
			}

			$dateTime = date('Y-m-d H:i:s', time());
			$params = array(
				'contact_id' => $contact_id,
				'social_user_id' => CRM_Utils_Array::value("sub", $user_data_response),
				'access_token' => $access_token,
				'oauth_object' => CRM_Utils_Array::value("profile", $user_data_response),
				'backend' => 'googleplus',
				'created_date' => $dateTime,
				'modified_date' => $dateTime,
			);
			self::create($params);
			return $contact_id;
		}
	}
}