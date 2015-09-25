<?php

require_once 'CRM/Core/Page.php';

class CRM_Civisocial_Page_GooglePlusCallback extends CRM_Core_Page {

	function get_response($apiURL, $node, $is_post, $params) {
		$url = $apiURL . "/" . $node;
		$urlparams = "";
		foreach ($params as $key => $value) {
			$urlparams .= $key . "=" . $value . "&";
		}
		if ($is_post == FALSE) {
			$url = $url . "?" . $urlparams;
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($is_post == TRUE) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $urlparams);
		} else {
			curl_setopt($ch, CURLOPT_POST, 0);
		}
		$response = curl_exec($ch);
		curl_close($ch);

		return json_decode($response, true);
	}

	function run() {
		$session = CRM_Core_Session::singleton();
		$request_origin = $session->get("civisocialredirect");
		if (!$request_origin) {
			$request_origin = CRM_Utils_System::url('civicrm', NULL, TRUE);
		}

		$apiURL = "https://www.googleapis.com/oauth2/v3";
		$redirect_uri = rawurldecode(CRM_Utils_System::url('civicrm/civisocial/googlepluscallback', NULL, TRUE));

		$is_enabled = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'enable_googleplus'));
		if (!$is_enabled) {
			CRM_Core_Session::setStatus(
				ts('Facebook Login is not enabled by your admin. Please try some other login options.'),
				ts('Error'), 'error');
			return CRM_Utils_System::redirect($request_origin);
		}
		$client_secret = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'google_plus_secret'));
		$client_id = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'google_plus_key'));

		if (array_key_exists('code', $_GET)) {
			$google_code = $_GET['code'];
		} else if (array_key_exists('error', $_GET)) {
			CRM_Core_Session::setStatus(
				ts('Google+ Login Error: There was an error while processing your request.'),
				ts('Error'), 'error');
			return CRM_Utils_System::redirect($request_origin);
		}

		$access_token = "";
		$access_token_response = $this->get_response($apiURL, "token", TRUE, array("client_id" => $client_id, "client_secret" => $client_secret, "code" => $google_code, "redirect_uri" => $redirect_uri, "grant_type" => "authorization_code"));

		if (array_key_exists("error", $access_token_response)) {
			CRM_Core_Session::setStatus(
				ts($access_token_response["error"]),
				ts('Error'), 'error');
			return CRM_Utils_System::redirect($request_origin);
		} else {
			$access_token = $access_token_response["access_token"];
		}

		$user_data_response = $this->get_response($apiURL, "userinfo", FALSE, array("access_token" => $access_token, "alt" => "json"));
		$contact_id = CRM_Civisocial_BAO_CivisocialUser::handle_googleplus_data($user_data_response, $access_token);

		$session->set('userID', $contact_id);
		$session->set('backend', "Google Plus");
		return CRM_Utils_System::redirect($request_origin);
	}
}
