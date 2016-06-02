<?php
require_once 'CRM/Core/Page.php';
require_once 'CRM/Civisocial/BAO/CivisocialUser.php';
require_once 'CRM/Civisocial/Backend/OAuthProvider/Facebook.php';
require_once 'CRM/Civisocial/Backend/OAuthProvider/Googleplus.php';
require_once 'CRM/Civisocial/Backend/OAuthProvider/Twitter.php';

class CRM_Civisocial_Page_OAuthCallback extends CRM_Core_Page {

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
		$path = CRM_Utils_System::currentPath();
		if (false !== strpos($path, '..')) {
			exit("Fatal Error: the URL can't contain '..'. Please report the issue on the forum at civicrm.org");
		}
		$path = split('/', $path);

		$backend = CRM_Utils_Array::value(3, $path);
		if (!$backend) {
			exit("BACKEND ERROR: No backend found in request");
		}

		// Check if the backend exists and is enabled
		// @todo: this is getting redundant. Maybe create a method in 
		//			OAuthProvider class
		$isEnabled = civicrm_api3(
			"setting",
			"getvalue",
			array(
				"group" => "CiviSocial Account Credentials",
				"name" => "enable_{$backend}"
			)
		);

		if (!$isEnabled) {
			exit("Backend either doesn't exist or is not enabled.");
		}

		// @todo: Do we still need to check if the class exists?
		$classname = "CRM_Civisocial_Backend_OAuthProvider_".ucwords($backend);
		$oAuthProvider = new $classname();

		$oAuthProvider->handleCallback();
	}

}
