<?php
require_once 'CRM/Core/Page.php';
require_once 'CRM/Civisocial/Backend/SocialMedia/Facebook.php';
require_once 'CRM/Civisocial/Backend/SocialMedia/Googleplus.php';
require_once 'CRM/Civisocial/Backend/SocialMedia/Twitter.php';

class CRM_Civisocial_Page_Login extends CRM_Core_Page {

	public function run() {
		$session = CRM_Core_Session::singleton();
		if (array_key_exists("redirect", $_GET)) {
			$session->set("civisocialredirect", $_GET["redirect"]);
		}

		$path = CRM_Utils_System::currentPath();
		if (FALSE !== strpos($path, '..')) {
			exit("FATAL ERROR: the url can't contain '..'. Please report the issue on the forum at civicrm.org");
		}
		$path = split('/', $path);
		$backend = CRM_Utils_Array::value(3, $path);
		if (!$backend) {
			exit("Bad Request");
			// @todo: Redirect to home or show Page not found?
		}

		// Check if the backend exists and is enabled
		$is_enabled = civicrm_api3(
			"setting",
			"getvalue",
			array(
				"group" => "CiviSocial Account Credentials",
				"name" => "enable_{$backend}"
			)
		);

		if (!$is_enabled) {
			exit("Backend doesn't exist or not enabled.");
		}

		$classname = "CRM_Civisocial_Backend_SocialMedia_".ucwords($backend);
		$socialMedia = new $classname();
		$redirectTo = $socialMedia->getLoginUri();

		if ($redirectTo) {
			return CRM_Utils_System::redirect($redirectTo);
		}
	}

}
