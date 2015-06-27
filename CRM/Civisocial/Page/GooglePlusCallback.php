<?php

require_once 'CRM/Core/Page.php';

class CRM_Civisocial_Page_GooglePlusCallback extends CRM_Core_Page {
  
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml

 //    $apiURL = "https://graph.facebook.com/v2.3";
 //    CRM_Utils_System::setTitle(ts('FacebookCallback'));
 //    $redirect_uri = rawurldecode(CRM_Utils_System::url('civicrm/civisocial/facebookcallback', NULL, TRUE));


 //    if(array_key_exists('code', $_GET)){
	//     $facebook_code = $_GET['code'];
	// }
	// else{
	// 	die ("FACEBOOK FATAL: the request returned without the code. Please try loging in again.");
	// }

	// $url = "https://graph.facebook.com/v2.3/oauth/access_token?client_id=392500997627504&redirect_uri=".$redirect_uri."&client_secret=fb152348bf1551c651d5dd9764d7aa1a&code=".$facebook_code;

	// $ch = curl_init( $url );	
 //    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 //    $response = curl_exec( $ch );
 //    curl_close($ch); 

 //    $result1 = get_object_vars(json_decode($response));

 //    //$access_token

 //    // Example: Assign a variable for use in a template
 //    $this->assign('currentTime', $result1['access_token']);

 //    $ch = curl_init($apiURL."/me?access_token=".$result1['access_token']);
 //    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 //    $response = curl_exec( $ch );
 //    curl_close($ch); 

 //    $result = get_object_vars(json_decode($response));

 //    $this->assign('currentTime', json_encode($_SESSION));

 //    parent::run();
  }
}
