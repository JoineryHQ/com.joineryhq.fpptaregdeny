<?php

use CRM_Fpptaregdeny_ExtensionUtil as E;

class CRM_Fpptaregdeny_Utils {
  
  const MESSAGE_KEY_NO_ORGS = 1;
  const MESSAGE_KEY_NO_VALID_ORG_MEMBERSHIPS = 2;
  const MESSAGE_KEY_HAS_UNPAID_REGISTRATIONS = 3;
  
  const MESSAGE_AUDIENCE_USER = 1;
  const MESSAGE_AUDIENCE_STAFF = 2;
  
  const MESSAGES = [
    self::MESSAGE_AUDIENCE_USER => [
      self::MESSAGE_KEY_NO_ORGS => "It appears you're not connected to any member organization.",
      self::MESSAGE_KEY_NO_VALID_ORG_MEMBERSHIPS => "We couldn't find a valid membership among your related organizations.",
      self::MESSAGE_KEY_HAS_UNPAID_REGISTRATIONS => "",
    ],
    self::MESSAGE_AUDIENCE_STAFF => [
      self::MESSAGE_KEY_NO_ORGS => 'User has no permissioned relationships to any organization.',
      self::MESSAGE_KEY_NO_VALID_ORG_MEMBERSHIPS => "None of user's related organizations have a valid membership.",
      self::MESSAGE_KEY_HAS_UNPAID_REGISTRATIONS => "",
    ],
  ];
  
  static function translateErrorKeys($errorKeys, $audience) {
    $ret = [];
    foreach($errorKeys as $errorKey) {
      $ret[] = self::MESSAGES[$audience][$errorKey];
    }
    return $ret;
  }
  
  /**
   * Boolean test -- is the user blocked from registering for any events?
   * Reference requirements doc: https://docs.google.com/document/d/1_448rUywsYTF072paQOHBICD9MaWXvciDZYEpHYzUDo/edit?usp=sharing
   */
  static function isUserBlocked(&$errorKeys = []) {
    // Return true if any disqualification is found, or finally return false.
    
    $userCid = CRM_Core_Session::getLoggedInContactID();
    if (!$userCid) {
      // We do not handle anonymous users (CMS permissions may of course revoke by permission configs).
      // Therefore return NULL (i.e., we don't care).
      return;
    }
    
    // Does user have permissioned relationships to any organization?
    $relatedOrgCids = self::getPermissionedOrganizations($userCid);
    if (empty($relatedOrgCids)) {
      $errorKeys[] = self::MESSAGE_KEY_NO_ORGS;
      return TRUE;
    }
    
    // Do any of the related orgs have a valid membership?
    $memberOrgCids = self::filterContactIdsByValidMemberships($relatedOrgCids);
    if (empty($memberOrgCids)) {
      $errorKeys[] = self::MESSAGE_KEY_NO_VALID_ORG_MEMBERSHIPS;
      return TRUE;
    }
    
    // Among valid member organizations' are all lacking a Disqualifying Contribution?
    
  }
  
  /**
   * Return list of permissioned organizations for a given contact.
   * Copied and modified from CRM_Contact_BAO_Relationship::getPermissionedContacts(), with
   * improvements made to support both a=>b and b=>a relationship types.
   *
   * @param int $contactID
   *   contact id whose permissioned orgs are to be found.
   *
   * @return array
   *   Array of organization contact IDs
   */
  public static function getPermissionedOrganizations($contactID) {
    $contacts = [];
    $args = [1 => [$contactID, 'Integer']];

    $query = "
      SELECT c.id as id
      FROM civicrm_relationship r, civicrm_contact c
      WHERE
        (
          (
            r.contact_id_a         = %1 AND
            r.is_permission_a_b    = 1
          )
        OR
          (
            r.contact_id_b         = %1 AND
            r.is_permission_b_a    = 1
          )
        ) AND
        c.id = if(r.contact_id_a = %1, r.contact_id_b, r.contact_id_a) AND
        IF(r.end_date IS NULL, 1, (DATEDIFF( CURDATE( ), r.end_date ) <= 0)) AND
        r.is_active = 1 AND
        c.is_deleted = 0
        AND c.contact_type = 'organization'
    ";

    $dao = CRM_Core_DAO::executeQuery($query, $args);
    $rows = $dao->fetchAll();
    $ret = array_unique(CRM_Utils_Array::collect('id', $rows));
    return $ret;
  }  
  
  public static function filterContactIdsByValidMemberships($cids) {
    $ret = [];
    $membershipTypeIds = [
      1, // Associate
      2, // Pension Board
    ];
    $memberships = \Civi\Api4\Membership::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('membership_type_id', 'IN', $membershipTypeIds)
      ->addWhere('owner_membership_id', 'IS NULL')
      ->addWhere('contact_id', 'IN', $cids)
      ->setLimit(0)
      ->addChain('membership_status', \Civi\Api4\MembershipStatus::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('id', '=', '$status_id'),
      0)
      ->execute();  
    $ret = CRM_Utils_Array::collect('contact_id', (array)$memberships);
    return $ret;
  }
}