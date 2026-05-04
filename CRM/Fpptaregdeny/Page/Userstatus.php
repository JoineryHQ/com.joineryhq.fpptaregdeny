<?php
declare(strict_types = 1);

use CRM_Fpptaregdeny_ExtensionUtil as E;

class CRM_Fpptaregdeny_Page_Userstatus extends CRM_Core_Page {

  public function run() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Int', NULL, TRUE);

    $accessChecker = new CRM_Fpptaregdeny_ContactAccessChecker($cid, 'admin');
    $accessChecker->doChecks();
    $adminResults = $accessChecker->getResults();
    $this->assign('results', $adminResults);
    $this->assign('disallow', $accessChecker->getDisallow());

    $contact = Civi\Api4\Contact::get()
      ->addWhere('id', '=', $cid)
      ->execute()
      ->first();
    $this->assign('displayName', $contact['display_name']);
    $this->assign('cid', $cid);

    $this->assign('hasCmsUser', $adminResults['checkHasCmsUser']['access']);
    $this->assign('userHasPerm', $adminResults['checkUserHasPerm']['access']);
    $userResults = $accessChecker->getResults('user');
    $userStatusMessage = CRM_Fpptaregdeny_Utils::buildUserStatusMessage($userResults);
    $this->assign('userStatusMessage', $userStatusMessage);

    CRM_Core_Resources::singleton()->addStyleFile(E::LONG_NAME, 'css/CRM_Fpptaregdeny_Page_Userstatus.css');
    parent::run();

  }

}
