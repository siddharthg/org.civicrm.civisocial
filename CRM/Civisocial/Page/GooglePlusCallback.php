<?php

require_once 'CRM/Core/Page.php';

class CRM_Civisocial_Page_GooglePlusCallback extends CRM_Core_Page {

	function get_response($apiURL, $node, $is_post, $params){
        $url = $apiURL."/".$node;
        $urlparams = "";
        foreach($params as $key=>$value){
            $urlparams .= $key."=".$value."&";
        }
        if($is_post==FALSE){
            $url = $url."?".$urlparams;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($is_post==TRUE){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $urlparams);
        }
        else{
            curl_setopt($ch, CURLOPT_POST, 0);
        }
        $response = curl_exec($ch);
        curl_close($ch); 

        return json_decode($response, true);   
    }

	function run() {
		$apiURL = "https://www.googleapis.com/oauth2/v3";
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


		// Getting Access Token 
		$access_token = "";
        $access_token_response = $this->get_response($apiURL, "token", TRUE, array("client_id"=>$client_id, "client_secret"=>$client_secret, "code"=>$google_code, "redirect_uri"=>$redirect_uri, "grant_type"=>"authorization_code"));
        
        if(array_key_exists("error", $access_token_response)){
            die ($access_token_response["error"]);
        }
        else{
            $access_token = $access_token_response["access_token"];
        }

		// Get the user data
		$user_data_response = $this->get_response($apiURL, "userinfo", FALSE, array("access_token"=>$access_token, "alt"=>"json"));
        $this->assign('status', $user_data_response);

	    $this->assign('currentTime', $user_data_response);
	    
	    parent::run();
  	}
}
