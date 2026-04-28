<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'fpptaregdeny.civix.php';
// phpcs:enable

use CRM_Fpptaregdeny_ExtensionUtil as E;


/**
 * Implements hook_civicrm_permission_check().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission_check/
 */
function fpptaregdeny_civicrm_permission_check($permission, &$granted) {
  if ($permission == 'register for events') {
    $staticKey = 'isUserBlocked';
    if (!isset(\Civi::$statics[__METHOD__][$staticKey])) {
      \Civi::$statics[__METHOD__][$staticKey] = CRM_Fpptaregdeny_Utils::isUserBlocked();
    }
    if (\Civi::$statics[__METHOD__][$staticKey] === TRUE) {
      // We do not grant this permission in any case. We only revoke it by certain criteria.
      $granted = FALSE;
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
