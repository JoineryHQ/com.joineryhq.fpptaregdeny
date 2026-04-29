<?php

class CRM_Fpptaregdeny_ContactAccessChecker {
  
  private $cid;
  private $context; // user | admin
  private $userId;
  private $results = [];
  private $disallow = FALSE; // boolean; by default, we don't deny access.
  private $relatedOrgCids = [];
  
  public function __construct($cid, $context) {
    $this->cid = $cid;
    $this->context = $context;
    $this->userId = CRM_Core_BAO_UFMatch::getUFId($cid);
  }
  
  public function doChecks() {
    // fixme stub
    switch ($this->context) {
      case 'admin':
        $this->checkHasWpUser();
        $this->checkUserHasPerm();
        // no `break`: 'admin' will also run all 'user' checks.
      case 'user':
        $this->checkHasOwnDisqualifyingContributions();
        $this->checkHasRelatedOrgs();
        $this->checkHasRelatedValidMembership();
        $this->checkHasRelatedDisqualifyingContributions();
    }
    foreach ($this->results as $result) {
      if ($result['access'] === FALSE) {
        $this->disallow = TRUE;
        break;
      }
    }
  }
  
  public function getResults() {
    switch ($this->context) {
      case 'admin': 
        // For admin: return all results.
        return $this->results;
      case 'user':
        // For user: return only access-deying results.
        $ret = [];
        foreach ($this->results as $result) {
          if ($result['access'] === FALSE) {
            $ret[] = $result;
          }
        }
        return $ret;
    }
  }
  
  public function getDisallow() {
    return $this->disallow;
  }

  private function addResult($name, $checkStatus, $accessStatus, $userMessage, $adminDescription, $adminMessage) {
    $result = [
      'check' => $checkStatus,
      'access' => $accessStatus,
      'user' => $userMessage,
      'adminDescription' => $adminDescription, 
      'admin' => $adminMessage,
    ];
    $this->results[$name] = $result;
  }
  
  // contact has a wp user?
  private function checkHasWpUser() {
    $adminDescription = 'Contact has a user account?';
    if (empty($this->userId)) {
      $this->addResult(__FUNCTION__, FALSE, FALSE, 'You have no user account', $adminDescription, 'No user account found.');
    }
    else {
      $userRecordUrl = CRM_Core_Config::singleton()->userSystem->getUserRecordUrl($this->cid);
      $adminMessage = "User account: <a href=\"{$userRecordUrl}\">{$this->userId}</a>";
      $this->addResult(__FUNCTION__, TRUE, TRUE, 'You have a user account', $adminDescription, $adminMessage);
    }
  }
  
  // user has 'register for events' permission?
  private function checkUserHasPerm() {
    
  }
  
  // contact has Disqualifying Contributions?
  private function checkHasOwnDisqualifyingContributions() {
    $adminDescription = 'Contact has disqualifying contributions?';
    if ('fixme') {
      $this->addResult(__FUNCTION__, TRUE, FALSE, 'You have some disqualifying contributions fixme-show-list.', $adminDescription, 'Disqualifying contributions found: fixme-show-list');
    }
    else {
      $this->addResult(__FUNCTION__, TRUE, FALSE, 'You have no disqualifying contributions fixme.', $adminDescription, 'No disqualifying contributions found.');
    }
  }
  
  // contact has permissioned relationship to any organizations?
  private function checkHasRelatedOrgs() {
    
    $this->relatedOrgCids[] = 1;
  }
  
  // at least one of contact's organizations has a Valid Organizational Membership?
  private function checkHasRelatedValidMembership() {
    
  }
  // orgs with valid memberships have Disqualifying Contributions?
  private function checkHasRelatedDisqualifyingContributions() {
    
  }

}
