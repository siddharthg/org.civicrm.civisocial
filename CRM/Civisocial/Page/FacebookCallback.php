<?php
require_once 'CRM/Core/Page.php';

class CRM_Civisocial_Page_FacebookCallback extends CRM_Core_Page {
    function run() {
        $apiURL = "https://graph.facebook.com/v2.3";
        CRM_Utils_System::setTitle(ts('FacebookCallback'));
        $redirect_uri = rawurldecode(CRM_Utils_System::url('civicrm/civisocial/facebookcallback', NULL, TRUE));
        $client_secret = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'facebook_secret'));
        $client_id = civicrm_api3('setting', 'getvalue', array('group' => 'CiviSocial Account Credentials', 'name' => 'facebook_app_id'));

        // Facebook sends a code to the callback url, this is further used to acquire
        // access token from facebook, which is needed to get all the data from facebook
        if(array_key_exists('code', $_GET)){
            $facebook_code = $_GET['code'];
        } else {
            die ("FACEBOOK FATAL: the request returned without the code. Please try loging in again.");
        }

        $url = "https://graph.facebook.com/v2.3/oauth/access_token?client_id=".$client_id."&redirect_uri=".$redirect_uri."&client_secret=".$client_secret."&code=".$facebook_code;

        // Acquiring Access Token
        $ch = curl_init( $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec( $ch );
        curl_close($ch); 

        $result1 = get_object_vars(json_decode($response));
        $access_token = $result1['access_token'];

        // Acquiring rest of the data
        $ch = curl_init($apiURL."/me?access_token=".$access_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec( $ch );
        curl_close($ch); 

        $result = get_object_vars(json_decode($response));
        $this->assign('status', json_encode($_SESSION));

        parent::run();
    }
}
