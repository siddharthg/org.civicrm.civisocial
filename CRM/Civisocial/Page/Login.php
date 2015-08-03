<?php

require_once 'CRM/Core/Page.php';

class CRM_Civisocial_Page_Login extends CRM_Core_Page {

	function getBackendURI(){
		$backendURI = NULL;
		$path = CRM_Utils_System::currentPath();
		if (false !== strpos($path, '..')) {
	    	die ("SECURITY FATAL: the url can't contain '..'. Please report the issue on the forum at civicrm.org");
	    }
		$path = split('/',$path);

		if(!CRM_Utils_Array::value(3,$path)){
			die ("BACKEND ERROR: No backend found in request");
		}
		else{
			$backend = CRM_Utils_Array::value(3,$path);
			if("facebook"==$backend){
				$enabled = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'enable_facebook'));
				if($enabled){
					$facebook_client_id = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'facebook_app_id'));
					$backendURI = "https://www.facebook.com/dialog/oauth?";
					$backendURI .= "client_id=".$facebook_client_id;
					$backendURI .= "&redirect_uri=".$this->getRedirectURI("facebook");
				}
			}
			else if("googleplus"==$backend){
				$enabled = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'enable_googleplus'));
				if($enabled){
					$googleplus_client_id = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'google_plus_key'));
					$backendURI = "https://accounts.google.com/o/oauth2/auth?scope=email%20profile&response_type=code&";
					$backendURI .= "client_id=".$googleplus_client_id;
					$backendURI .= "&redirect_uri=".$this->getRedirectURI("googleplus");
				}
			}
		}
		return $backendURI;
	}

	function getRedirectURI($backend){
		$redirectURI = NULL;
		if(!$backend){
			die ("BACKEND ERROR: No backend found in request");
		}
		else{
			$redirectURI = rawurldecode(CRM_Utils_System::url("civicrm/civisocial/".$backend."callback", NULL, TRUE));
		}
		return $redirectURI;
	}


  function run() {
  	$session = CRM_Core_Session::singleton();
  	
  	$redirectTo = $this->getBackendURI();
  	//$session->set("userID", "2");
  	if($redirectTo){
    	return CRM_Utils_System::redirect($redirectTo);
    }
    $this->assign('status', "Backend Not Supported");
    parent::run();
  }
}