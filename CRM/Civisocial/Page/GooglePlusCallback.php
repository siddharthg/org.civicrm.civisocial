<?php

require_once 'CRM/Core/Page.php';

class CRM_Civisocial_Page_GooglePlusCallback extends CRM_Core_Page {
	function run() {
    	CRM_Utils_System::setTitle(ts('GoogleCallback'));
		$redirect_uri = rawurldecode(CRM_Utils_System::url('civicrm/civisocial/googlepluscallback', NULL, TRUE));
    	$client_secret = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'google_plus_secret'));
    	$client_id = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'google_plus_key'));

    	// Facebook sends a code to thge
    	if(array_key_exists('code', $_GET)){
	    	$google_code = $_GET['code'];
		}
		else if(array_key_exists('error', $_GET)){
			die ("GOOGLE FATAL: the request returned without the code. Please try loging in again.");
		}

		$url = "https://www.googleapis.com/oauth2/v3/token";
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "client_id=".$client_id."&client_secret=".$client_secret."&code=".$google_code."&redirect_uri=".$redirect_uri."&grant_type=authorization_code");
	    
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $response = curl_exec( $ch );
	    curl_close($ch); 

	    $result1 = get_object_vars(json_decode($response));

	    // Example: Assign a variable for use in a template
	    $this->assign('currentTime', $result1["access_token"]);

	    $ch = curl_init($apiURL."/me?access_token=".$result1['access_token']);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $response = curl_exec( $ch );
	    curl_close($ch); 

	    // $result = get_object_vars(json_decode($response));
	    // $this->assign('currentTime', json_encode($_SESSION));
	    
	    parent::run();
  	}
}
