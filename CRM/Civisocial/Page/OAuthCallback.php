<?php
require_once 'CRM/Core/Page.php';
require_once 'CRM/Civisocial/BAO/CivisocialUser.php';
require_once 'CRM/Civisocial/Backend/OAuthProvider/Facebook.php';
require_once 'CRM/Civisocial/Backend/OAuthProvider/Googleplus.php';
require_once 'CRM/Civisocial/Backend/OAuthProvider/Twitter.php';

class CRM_Civisocial_Page_OAuthCallback extends CRM_Core_Page {

  public function run() {
    $path = CRM_Utils_System::currentPath();
    if (FALSE !== strpos($path, '..')) {
      exit("Fatal Error: the URL can't contain '..'. Please report the issue on the forum at civicrm.org");
    }
    $path = explode('/', $path);

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
      "name" => "enable_{$backend}",
    )
    );

    if (!$isEnabled) {
      exit("Backend either doesn't exist or is not enabled.");
    }

    // @todo: Do we still need to check if the class exists?
    $classname = "CRM_Civisocial_Backend_OAuthProvider_" . ucwords($backend);
    $oAuthProvider = new $classname();

    $oAuthProvider->handleCallback();
  }

}
