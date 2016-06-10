<?php
require_once 'CRM/Core/Page.php';
require_once 'CRM/Civisocial/OAuthProvider/Facebook.php';
require_once 'CRM/Civisocial/OAuthProvider/Googleplus.php';
require_once 'CRM/Civisocial/OAuthProvider/Twitter.php';

class CRM_Civisocial_Page_Login extends CRM_Core_Page {

  public function run() {
    $oap = new CRM_Civisocial_OAuthProvider();
    $session = CRM_Core_Session::singleton();

    $path = CRM_Utils_System::currentPath();

    if (FALSE !== strpos($path, '..')) {
      exit("FATAL ERROR: the url can't contain '..'. Please report the issue on the forum at civicrm.org");
    }

    $path = explode('/', $path);

    if (count($path) == 3 && $path[2] == 'logout') {
      $oap->login();
      $oap->redirect();
    }
    elseif (count($path) == 4 && $path[2] == 'login') {
      // Check if already logged in
      $oap->handleCallback();

      $OAuthProvider = CRM_Utils_Array::value(3, $path);
      if (!$OAuthProvider) {
        exit("Bad Request");
        // @todo: Redirect to home or show Page not found?
      }

      // Check if the OAuth Provider exists and is enabled
      $isEnabled = civicrm_api3(
        "setting",
        "getvalue",
        array(
          "group" => "CiviSocial Account Credentials",
          "name" => "enable_{$OAuthProvider}",
        )
      );

      if (!$isEnabled) {
        exit("OAuth Provider either doesn't exist or not enabled.");
      }
      // Save redirect
      if (isset($_GET['continue'])) {
        $session->set('civisocial_redirect', $_GET['continue']);
      }
      $classname = "CRM_Civisocial_OAuthProvider_" . ucwords($OAuthProvider);
      $oap = new $classname();
      $redirectTo = $oap->getLoginUri();
      if ($redirectTo) {
        return CRM_Utils_System::redirect($redirectTo);
      }
    }
  }

}
