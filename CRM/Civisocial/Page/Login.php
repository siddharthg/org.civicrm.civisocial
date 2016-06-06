<?php
require_once 'CRM/Core/Page.php';
require_once 'CRM/Civisocial/OAuthProvider/Facebook.php';
require_once 'CRM/Civisocial/OAuthProvider/Googleplus.php';
require_once 'CRM/Civisocial/OAuthProvider/Twitter.php';

class CRM_Civisocial_Page_Login extends CRM_Core_Page {

  public function run() {
    $oAuthProvider = new CRM_Civisocial_Backend_OAuthProvider();

    $session = CRM_Core_Session::singleton();
    if (array_key_exists("redirect", $_GET)) {
      $session->set("civisocialredirect", $_GET["redirect"]);
    }

    $path = CRM_Utils_System::currentPath();

    if (FALSE !== strpos($path, '..')) {
      exit("FATAL ERROR: the url can't contain '..'. Please report the issue on the forum at civicrm.org");
    }

    $path = explode('/', $path);

    if (count($path) == 3 && $path[2] == 'logout') {
      $oAuthProvider->logout();
      CRM_Utils_System::redirect(CRM_Utils_System::url('', NULL, TRUE));
    }
    elseif (count($path) == 4 && $path[2] == 'login') {
      $oAuthProvider->handleCallback();

      $backend = CRM_Utils_Array::value(3, $path);
      if (!$backend) {
        exit("Bad Request");
        // @todo: Redirect to home or show Page not found?
      }

      // Check if the backend exists and is enabled
      $isEnabled = civicrm_api3(
      "setting",
      "getvalue",
      array(
        "group" => "CiviSocial Account Credentials",
        "name" => "enable_{$backend}",
      )
      );

      if (!$isEnabled) {
        exit("Backend doesn't exist or not enabled.");
      }

      $classname = "CRM_Civisocial_Backend_OAuthProvider_" . ucwords($backend);
      $oAuthProvider = new $classname();
      $redirectTo = $oAuthProvider->getLoginUri();
      if ($redirectTo) {
        return CRM_Utils_System::redirect($redirectTo);
      }
    }
  }

}
