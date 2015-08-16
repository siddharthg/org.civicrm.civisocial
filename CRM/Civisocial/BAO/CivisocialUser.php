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

	public static function get_by_user_id($id) {
		$result = array();
		$instance = new CRM_Civisocial_DAO_CivisocialUser();
		$instance->social_user_id = $id;

		$instance->find();
		while ($instance->fetch()) {
			$row = array();
			$instance->storeValues($instance, $row);
			$result[$row['id']] = $row;
		}
		return $result;
	}

	private static function check_existing($social_user_id) {
		$result = self::get_by_user_id($social_user_id);
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
		$existing_contact_id = self::check_existing($user_data_response["id"]);
		if ($existing_contact_id) {
			return $existing_contact_id;
		} else {
			$email = $user_data_response["email"];
			$contacts = civicrm_api3('contact', 'get', array("email" => $email));

			// User was not found
			if ($contacts["count"] == 0) {
				// Create a new contact
				$this->assign('status', "new contact created");
				$params = array(
					'first_name' => $user_data_response["first_name"],
					'last_name' => $user_data_response["last_name"],
					'display_name' => $user_data_response["name"],
					'preffered_language' => $user_data_response["locale"],
					'gender' => $user_data_response["gender"],
					'email' => $user_data_response["email"],
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

			$params = array(
				'contact_id' => $contact_id,
				'social_user_id' => $user_data_response["id"],
				'access_token' => $access_token,
				'oauth_object' => $user_data_response["link"],
				'backend' => 'facebook',
			);
			self::create($params);
			return $contact_id;
		}
	}

	public static function handle_googleplus_data($user_data_response, $access_token) {
		$existing_contact_id = self::check_existing($user_data_response["sub"]);
		if ($existing_contact_id) {
			return $existing_contact_id;
		} else {
			$email = $user_data_response["email"];
			$contacts = civicrm_api3('contact', 'get', array("email" => $email));
			// User was not found
			if ($contacts["count"] == 0) {
				// Create a new contact
				$params = array(
					'first_name' => $user_data_response["given_name"],
					'last_name' => $user_data_response["family_name"],
					'display_name' => $user_data_response["name"],
					'preffered_language' => $user_data_response["locale"],
					'gender' => $user_data_response["gender"],
					'email' => $user_data_response["email"],
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
			$params = array(
				'contact_id' => $contact_id,
				'social_user_id' => $user_data_response["sub"],
				'access_token' => $access_token,
				'oauth_object' => $user_data_response["profile"],
				'backend' => 'googleplus',
			);
			self::create($params);
			return $contact_id;
		}
	}
}