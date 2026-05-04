<?php

use CRM_Fpptaregdeny_ExtensionUtil as E;

return [
  'fpptaregdeny_is_blocking' => [
    'name' => 'fpptaregdeny_is_blocking',
    'type' => 'Boolean',
    'title' => E::ts('Enforce access limitations?'),
    'description' => E::ts('If this is disabled, user blocking is not enforced, but staff can still examine status.'),
    'default' => FALSE,
    'html_type' => 'checkbox',
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['fpptaregdeny' => ['weight' => 1]],
  ],
  'fpptaregdeny_limit_days' => [
    'name' => 'fpptaregdeny_limit_days',
    'type' => 'Int',
    'title' => E::ts('Days before limiting'),
    'description' => E::ts('Disqualifying contributions are older than this many days, per their %1 field value.', [1 => E::ts('Contribution Date')]),
    'default' => 90,
    'html_type' => 'text',
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['fpptaregdeny' => ['weight' => 10]],
  ],
  'fpptaregdeny_dq_statusids' => [
    'name' => 'fpptaregdeny_dq_statusids',
    'type' => 'String',
    'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    'title' => E::ts('Disqualifying contribution statuses'),
    'description' => 'Disqualifying contributions are of one of these statuses.',
    'default' => [],
    'html_type' => 'checkboxes',
    'pseudoconstant' => ['optionGroupName' => 'contribution_status'],
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['fpptaregdeny' => ['weight' => 20]],
  ],
];
