<?php

require_once 'CRM/Core/Page.php';

class CRM_Civisocial_Page_Logout extends CRM_Core_Page {
	function run() {
		$session = CRM_Core_Session::singleton();
		$session->set('userID', NULL);
		$session->set('civisocial_backend', NULL);
		return CRM_Utils_System::redirect(CRM_Utils_System::url('', NULL, TRUE));
	}
}