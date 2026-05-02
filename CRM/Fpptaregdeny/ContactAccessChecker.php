<?php

class CRM_Fpptaregdeny_ContactAccessChecker {

  private $cid;
  private $context; // user | admin
  private $userId;
  private $results = [];
  private $disallow = FALSE; // boolean; by default, we don't deny access.
  private $relatedOrgCids = [];
  private $validMemberOrgCids = [];

  public function __construct($cid, $context) {
    $this->cid = $cid;
    $this->context = $context;
    $this->userId = CRM_Core_BAO_UFMatch::getUFId($cid);
  }

  public function doChecks() {
    switch ($this->context) {
      case 'admin':
        $this->checkHasWpUser();
        $this->checkUserHasPerm();
        // no `break`: 'admin' will also run all 'user' checks.
      case 'user':
        $this->checkHasOwnDisqualifyingContributions();
        $this->checkHasRelatedOrgs();
        $this->checkHasRelatedValidMembership();
        $this->checkHasRelatedMemberOrgsHavingNoDisqualifyingContributions();
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
    $adminDescription = 'Contact/user has permission to register for events?';
    // If possible, run WP "fpptarolesync" plugin functions to update user roles.
    CRM_Fpptaregdeny_Utils::maybeSyncUserRoles($this->cid);
    if (CRM_Core_Permission::check('register for events', $this->cid)) {
      $this->addResult(__FUNCTION__, TRUE, TRUE, 'You do have permission to register for events.', $adminDescription, 'User has this permission.');
    }
    else {
      $this->addResult(__FUNCTION__, FALSE, FALSE, 'You do not have permission to register for events.', $adminDescription, 'User does not hvae this permission.');
    }
  }

  // contact has Disqualifying Contributions?
  private function checkHasOwnDisqualifyingContributions() {
    $adminDescription = 'Contact has disqualifying contributions?';
    $disqualifyingContributions = CRM_Fpptaregdeny_Utils::getContactDisqualifyingContributions([$this->cid]);
    if (empty($disqualifyingContributions)) {
      $this->addResult(__FUNCTION__, FALSE, TRUE, 'You have no outstanding payments.', $adminDescription, 'No disqualifying contributions found.');
    }
    else {
      $preppedContributionsList = $this->prepContributionsList($disqualifyingContributions, 'individual');
      $contributionListAdmin = CRM_Fpptaregdeny_Utils::buildEntitiesUnorderedList($preppedContributionsList, 'contribution', TRUE);
      $contributionListUser = CRM_Fpptaregdeny_Utils::buildEntitiesUnorderedList($preppedContributionsList, 'contribution', FALSE);
      $this->addResult(__FUNCTION__, TRUE, FALSE, 'You have some outstanding payments: '. $contributionListUser, $adminDescription, 'Disqualifying contributions found:'. $contributionListAdmin);
    }
  }

  // contact has permissioned relationship to any organizations?
  private function checkHasRelatedOrgs() {
    $adminDescription = 'Contact has permissioned relationships to some organizations?';
    $relatedOrgs = CRM_Fpptaregdeny_Utils::getPermissionedOrganizations($this->cid);
    $this->relatedOrgCids = array_keys($relatedOrgs);
    if (empty($this->relatedOrgCids)) {
      $this->addResult(__FUNCTION__, FALSE, FALSE, 'We could not find any related organizations for you.', $adminDescription, 'Found no permissioned relationships to organizations.');
    }
    else {
      $orgListAdmin = CRM_Fpptaregdeny_Utils::buildEntitiesUnorderedList($relatedOrgs, 'contact', TRUE);
      $this->addResult(__FUNCTION__, TRUE, TRUE, 'You have some permissioned relationships to organizations.', $adminDescription, 'Found permissioned relationships to these organizations: '.  $orgListAdmin);
    }
  }

  // at least one of contact's organizations has a Valid Organizational Membership?
  private function checkHasRelatedValidMembership() {
    $adminDescription = "At least one of contact's organizations has a Valid Organizational Membership?";
    
    if (empty($this->relatedOrgCids)) {
      $notPerformedMessage = 'This check was not performed permissioned relationships to organizations were found.';
      $this->addResult(__FUNCTION__, NULL, NULL, $notPerformedMessage, $adminDescription, $notPerformedMessage);
      return;
    }
    
    $validMemberOrgs = CRM_Fpptaregdeny_Utils::filterContactIdsByValidMemberships($this->relatedOrgCids);
    $this->validMemberOrgCids = array_keys($validMemberOrgs);
    if (empty($validMemberOrgs)) {
      $this->addResult(__FUNCTION__, FALSE, FALSE, 'We could not find valid memberships among your related organizations.', $adminDescription, 'Found no valid memberships among related organizations.');
    }
    else {
      $orgListAdmin = CRM_Fpptaregdeny_Utils::buildEntitiesUnorderedList($validMemberOrgs, 'contact', TRUE);
      $this->addResult(__FUNCTION__, TRUE, TRUE, 'We found valid memberships among your related organizations.', $adminDescription, 'Found these related organizations with valid memberships: '.  $orgListAdmin);
    }
  }

  // orgs with valid memberships have Disqualifying Contributions?
  private function checkHasRelatedMemberOrgsHavingNoDisqualifyingContributions() {
    $checkStatus = FALSE;
    $adminDescription = "Contact has related member organizations without disqualifying contributions?";
    
    if (empty($this->validMemberOrgCids)) {
      $notPerformedMessage = 'This check was not performed because no related member organizations were found.';
      $this->addResult(__FUNCTION__, NULL, NULL, $notPerformedMessage, $adminDescription, $notPerformedMessage);
      return;
    }
    
    $disqualifyingContributions = CRM_Fpptaregdeny_Utils::getContactDisqualifyingContributions($this->validMemberOrgCids);
    if (empty($disqualifyingContributions)) {
      // contact has related member orgs, and none of those have disqualifying contributions, so this check passes.
      $this->addResult(__FUNCTION__, TRUE, TRUE, 'None of your related organizations have outstanding payments.', $adminDescription, 'No related member organizations have disqualifying contributions.');
    }
    else {
      // Some orgs have disqualifying contributions. Let's test whether that's ALL related orgs, or just some.
      // Store a temp array with validMemberOrgCids in array keys, so we can easily find and remove them.
      $keyedValidMemberOrgCids = array_flip($this->validMemberOrgCids);
      foreach ($disqualifyingContributions as $disqualifyingContribution) {
        // For any disqualifying contribution, remove the contact (by contact_id) from our temp array.
        unset($keyedValidMemberOrgCids[$disqualifyingContribution['contact_id']]);
      }
      // Now the temp array should only contain orgs with NO disqualifying contributions.
      // If that's empty, we have a problem; otherwise, we pass.
      if (empty($keyedValidMemberOrgCids)) {
        $preppedContributionsList = $this->prepContributionsList($disqualifyingContributions, 'organization');
        $contributionListAdmin = CRM_Fpptaregdeny_Utils::buildEntitiesUnorderedList($preppedContributionsList, 'contribution', TRUE);
        $contributionListUser = CRM_Fpptaregdeny_Utils::buildEntitiesUnorderedList($preppedContributionsList, 'contribution', FALSE);
        $this->addResult(__FUNCTION__, FALSE, FALSE, 'Your related organizations have outstanding payments: '. $contributionListUser, $adminDescription, 'Found disqualifying contributions for all related member organizations:'. $contributionListAdmin);
      }
      else {
        $this->addResult(__FUNCTION__, TRUE, TRUE, 'Some of your related organizations do not have outstanding payments.', $adminDescription, 'Some related member organizations have no disqualifying contributions.');
      }
    }
  }

  private function prepContributionsList(array $contributions, $checkType) {
    $ret = [];
    $messageType = "{$this->context}|{$checkType}";
    foreach ($contributions as $contribution) {      
      switch ($messageType) {
        case 'admin|individual':
          $label = "{$contribution['receive_date']}: {$contribution['total_amount']}, status: {$contribution['contribution_status']}";
          break;
        case 'admin|organization':
          $label = "{$contribution['receive_date']}: {$contribution['total_amount']}, status: {$contribution['contribution_status']}, charged to {$contribution['display_name']}";
          break;
        case 'user|individual':
          $label = "{$contribution['receive_date']}: {$contribution['total_amount']}, status: {$contribution['contribution_status']}";
          break;
        case 'user|organization':
          $label = "{$contribution['receive_date']}: charged to {$contribution['display_name']}";
          break;
      }
      $ret[$contribution['id']] = $label;
    }
    return $ret;
  }
}
