<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'fpptaregdeny.civix.php';
// phpcs:enable

use CRM_Fpptaregdeny_ExtensionUtil as E;

/**
 * Implements hook_civicrm_alterContent().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterContent/
 */
function fpptaregdeny_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  $objName = $object->getVar('_name');
  if ($context == 'page' && $objName == 'CRM_Event_Page_Tab') {
    $action = $object->getVar('_action');
    if ($action == CRM_Core_Action::BROWSE) {
      $contactId = $object->getVar('_contactId');
      $contactType = \CRM_Contact_BAO_Contact::getContactType($contactId);
      if (strtolower($contactType) == 'individual') {
        // Inject an action button to view user/contact's 'fpptaregdeny' access levels.
        // SEE ALSO: FpptaregdenyParticipantLinksProvider, which adds this link in
        // api4.partipant.getLinks contexts (e.g. searchKit, as in civicrm_admin_ui)
        $tpl = CRM_Core_Smarty::singleton();
        $tpl->assign('fpptaregdenyStatusUrl', CRM_Utils_System::url('civicrm/fpptaregdeny/userstatus', ['cid' => $contactId]));
        $content .= $tpl->fetch('CRM/Fpptaregdeny/snippet/CRM_Event_Page_Tab_actions.tpl');
      }
    }
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 */
function fpptaregdeny_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Event_Form_Registration_Register') {
    // Skip this section unless setting fpptaregdeny_is_blocking is true.
    if (Civi::settings()->get('fpptaregdeny_is_blocking')) {
      $cid = CRM_Core_Session::getLoggedInContactID();
      $accessChecker = new CRM_Fpptaregdeny_ContactAccessChecker($cid, 'user');
      $accessChecker->doChecks();
      if ($accessChecker->getDisallow()) {
        $results = $accessChecker->getResults();
        $statusMessage = CRM_Fpptaregdeny_Utils::buildUserStatusMessage($results);
        CRM_Core_Session::setStatus($statusMessage, 'Access withheld.', 'crm-error');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $form->_eventId, FALSE, NULL, FALSE, TRUE));
      }
    }
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function fpptaregdeny_civicrm_config(\CRM_Core_Config $config): void {
  _fpptaregdeny_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function fpptaregdeny_civicrm_install(): void {
  _fpptaregdeny_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function fpptaregdeny_civicrm_enable(): void {
  _fpptaregdeny_civix_civicrm_enable();
}
