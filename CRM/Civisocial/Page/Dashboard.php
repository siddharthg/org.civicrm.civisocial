<?php

class CRM_Civisocial_Page_Dashboard extends CRM_Core_Page {

  public function run() {
    CRM_Core_Resources::singleton()->addStyleFile('org.civicrm.civisocial', 'templates/res/css/civisocial.css', 0, 'html-header');
    CRM_Core_Resources::singleton()->addStyleFile('org.civicrm.civisocial', 'templates/res/css/dashboard.css', 0, 'html-header');
    CRM_Core_Resources::singleton()->addScriptFile('org.civicrm.civisocial', 'templates/res/js/dashboard.js');
    parent::run();
  }

}
