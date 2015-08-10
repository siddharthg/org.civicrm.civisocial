<?php
require_once 'CRM/Core/Page.php';
require_once 'CRM/Civisocial/BAO/CivisocialUser.php';

class CRM_Civisocial_Page_FacebookCallback extends CRM_Core_Page {

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
        $session = CRM_Core_Session::singleton();

        $apiURL = "https://graph.facebook.com/v2.3";
        $redirect_uri = rawurldecode(CRM_Utils_System::url('civicrm/civisocial/facebookcallback', NULL, TRUE));
        
        // Retreive client_id and client_secret from settings
        $is_enabled = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'enable_facebook'));
        if(!$is_enabled){
            die ("Backend not enabled.");
        }

        $client_secret = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'facebook_secret'));
        $client_id = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'facebook_app_id'));

        // Facebook sends a code to the callback url, this is further used to acquire
        // access token from facebook, which is needed to get all the data from facebook
        if(array_key_exists('code', $_GET)){
            $facebook_code = $_GET['code'];
        } else {
            die ("FACEBOOK FATAL: the request returned without the code. Please try loging in again.");
        }

        // Get the access token from facebook for the user
        $access_token = "";
        $access_token_response = $this->get_response($apiURL, "oauth/access_token", FALSE, array("client_id"=>$client_id, "client_secret"=>$client_secret, "code"=>$facebook_code, "redirect_uri"=>$redirect_uri));

        if(array_key_exists("error", $access_token_response)){
            die ($access_token_response["error"]);
            $access_token = "";
        }
        else{
            $access_token = $access_token_response["access_token"];
        }

        $user_data_response = $this->get_response($apiURL, "me", FALSE, array("access_token"=>$access_token));

        $contact_id = CRM_Civisocial_BAO_CivisocialUser::handle_facebook_data($user_data_response);
        $this->assign('status', $contact_id);
        $session->set('userID', $contact_id);
        parent::run();
    }
}
