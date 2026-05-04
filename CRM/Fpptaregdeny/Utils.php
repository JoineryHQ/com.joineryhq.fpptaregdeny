<?php

use CRM_Fpptaregdeny_ExtensionUtil as E;

class CRM_Fpptaregdeny_Utils {

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
      SELECT c.id as id, c.display_name
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
    $keyedRows = CRM_Utils_Array::rekey($rows, 'id');
    $ret = CRM_Utils_Array::collect('display_name', $keyedRows);
    return $ret;
  }

  /**
   * For a given set of contactIds, return those that have a Valid Organizational Membership.
   *
   * @param Array $cids (integers)
   *
   * @return Array key=cid; value=display_name
   */
  public static function filterContactIdsByValidMemberships($cids) {
    $ret = [];
    $membershipTypeIds = [
      // Associate:
      1,
      // Pension Board:
      2,
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
      ->addChain('contact', \Civi\Api4\Contact::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('id', '=', '$contact_id'),
      0)
      ->execute();
    foreach ($memberships as $membership) {
      $ret[$membership['contact_id']] = $membership['contact']['display_name'];
    }
    return $ret;
  }

  /**
   * For a given contactId, fire off the FpptarolesyncUtil::updateRolesForCids()
   * function, if it exists (i.e., if wp plugin fpptarolesync is active).
   *
   * @staticvar array $doneCids Caching to avoid repeat processing on any contacts.
   *
   * @param Int $cid
   */
  public static function maybeSyncUserRoles($cid) {
    static $doneCids = [];
    if (in_array($cid, $doneCids)) {
      $doneCids[] = $cid;
      if (CIVICRM_UF === 'WordPress' && FPPTAROLESYNC_DIR) {
        $pluginUtilFile = FPPTAROLESYNC_DIR . '/includes/class-util.php';
        if (file_exists($pluginUtilFile)) {
          require_once $pluginUtilFile;
          $cidsToUpdate = [$cid];
          FpptarolesyncUtil::updateRolesForCids($cidsToUpdate);
        }
      }
    }
  }

  /**
   * Create an html unordered list from an array of entity information.
   *
   * @param Array $entities key=entityId; value=text_label
   * @param String $entityType contact|contribution
   * @param Bool $doLink Create label as a link to the entity?
   *
   * @return String Full <ul> html content.
   */
  public static function buildEntitiesUnorderedList($entities, $entityType, bool $doLink) {
    $ret = '<ul>';
    foreach ($entities as $entityId => $entityLabel) {
      if ($doLink) {
        switch ($entityType) {
          case 'contact':
            $url = CRM_Utils_System::url('civicrm/contact/view', "action=view&reset=1&cid={$entityId}");
            break;

          case 'contribution':
            $url = CRM_Utils_System::url('civicrm/contact/view/contribution', "action=view&reset=1&id={$entityId}");
            break;

        }
        $ret .= "<li><a href=\"$url\">{$entityLabel}</a></li>";
      }
      else {
        $ret .= "<li>{$entityLabel}</li>";
      }
    }
    $ret .= '</ul>';
    return $ret;
  }

  /**
   * For the given contactIds, get a list of Disqualifying Contributions by those contacts.
   *
   * @param Array $cids
   *
   * @return Array key=contribution_id; value=[array of contribution properties]
   */
  public static function getContactDisqualifyingContributions(array $cids) {
    $ret = [];
    if (empty($cids)) {
      // Someone has passed in a null value; we can't know enough, so return empty array.
      return $ret;
    }
    $dqStatusIds = \Civi::settings()->get('fpptaregdeny_dq_statusids');
    if (is_string($dqStatusIds)) {
      $dqStatusIds = json_decode($dqStatusIds);
    }
    $limitDays = \Civi::settings()->get('fpptaregdeny_limit_days');
    $limitDaysDate = date('Y-m-d', strtotime("$limitDays days ago"));
    // Get all contributions matching our settings for 'fpptaregdeny_limit_days' and 'fpptaregdeny_dq_statusids',
    // with enough data to print a reasonable status report.
    $contributions = Civi\Api4\Contribution::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('contact_id', 'IN', $cids)
      ->addWhere('receive_date', '<', $limitDaysDate)
      ->addWhere('contribution_status_id', 'IN', $dqStatusIds)
      ->addSelect('id')
      ->addSelect('contact_id')
      ->addSelect('total_amount')
      ->addSelect('receive_date')
      ->addSelect('contribution_status_id')
      ->addSelect('contribution_status_id:label')
      ->addOrderBy('receive_date', 'ASC')
      ->addChain('contact', \Civi\Api4\Contact::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('id', '=', '$contact_id')
        ->addSelect('id')
        ->addSelect('display_name'),
      0)
      ->execute();
    foreach ($contributions as $contribution) {
      $ret[$contribution['id']] = [
        'id' => $contribution['id'],
        'contact_id' => $contribution['contact_id'],
        'total_amount' => CRM_Utils_Money::format($contribution['total_amount']),
        'receive_date' => CRM_Utils_Date::customFormat($contribution['receive_date'], "%Y-%m-%d"),
        'contribution_status' => $contribution['contribution_status_id:label'],
        'display_name' => $contribution['contact']['display_name'],
      ];
    }
    return $ret;
  }

}
