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
				$backendURI = "https://www.facebook.com/dialog/oauth?client_id=392500997627504&redirect_uri=".$this->getRedirectURI();
			}
			else if("googleplus"==$backend){
				$backendURI = "https://accounts.google.com/o/oauth2/auth";
			}
		}
		return $backendURI;
	}

	function getRedirectURI(){
		$redirectURI = NULL;
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
			$redirectURI = rawurldecode(CRM_Utils_System::url("civicrm/civisocial/".$backend."callback", NULL, TRUE));
		}
		return $redirectURI;
	}


  function run() {
  	$session = CRM_Core_Session::singleton();
  	$redirectTo = $this->getBackendURI();

  	//$session->set("userID", "2");
    return CRM_Utils_System::redirect($redirectTo);

    //return parent::run();
  }
}