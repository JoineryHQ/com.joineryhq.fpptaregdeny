<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'fpptaregdeny.civix.php';
// phpcs:enable

use CRM_Fpptaregdeny_ExtensionUtil as E;


function fpptaregdeny_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Event_Form_Registration_Register') {
    $errorKeys = [];
    if (CRM_Fpptaregdeny_Utils::isUserBlocked($errorKeys)) {
      $errors = CRM_Fpptaregdeny_Utils::translateErrorKeys($errorKeys, CRM_Fpptaregdeny_Utils::MESSAGE_AUDIENCE_USER);
      if (empty($errors)) {
        $statusMessage = 'Access denied.';
      }
      else {
        $statusMessage = "You're not able to register for events at this time, because:<ul>";
        foreach ($errors as $error) {
          $statusMessage .= "<li>$error</li>";
        }
        $statusMessage .= "</ul>";
      }
      CRM_Core_Session::setStatus($statusMessage);
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $form->_eventId,FALSE, NULL, FALSE, TRUE ));
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
