<?php
class CRM_Civisocial_BAO_CivisocialUserTest extends CiviUnitTestCase {

  /**
   * Clean up after tests.
   */
  public function tearDown() {
    // $this->quickCleanUpFinancialEntities();
    // $this->quickCleanUpFinancialEntities(array('civicrm_event'));
    parent::tearDown();
  }

  /**
   * Test create method (create and update modes).
   */
  public function testCreate() {
    $contactId = $this->individualCreate();

    // Create social user
    $params = array(
      'contact_id' => $contactId,
      'social_user_id' => 123456,
      'access_token' => '7f550a9f4c44173a37664d938f1355f0f92a47a7',
      'oauth_provider' => 'facebook',
      'created_date' => '20160819213000',
    );

    $socialUser = CRM_Civisocial_BAO_CivisocialUser::create($params);
    $this->assertEquals($contactId, $socialUser->contact_id, 'Check for contact id  creation.');

    // Update Access token
    $ids = array('id' => $socialUser->id);
    $params['access_token'] = 'aaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d';

    $socialUser = CRM_Civisocial_BAO_CivisocialUser::create($params, $ids);

    $this->assertEquals($params['access_token'], $socialUser->access_token, 'Check for Access Token updation.');
  }

}
