<?php
/**
 * Based on and modified from Civi\Api4\Service\Links\ParticipantLinksProvider
 * which bore the following copyright notice:
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC. All rights reserved.                        |
 * |                                                                    |
 * | This work is published under the GNU AGPLv3 license with some      |
 * | permitted exceptions and without any warranty. For full license    |
 * | and copyright information, see https://civicrm.org/licensing       |
 * +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Service\Links;

use Civi\API\Event\RespondEvent;

/**
 * @service
 * @internal
 */
class FpptaregdenyParticipantLinksProvider extends \Civi\Core\Service\AutoSubscriber {
  use LinksProviderTrait;

  public static function getSubscribedEvents(): array {
    return [
      'civi.api.respond' => ['alterParticipantLinksResult', -50],
    ];
  }

  /**
   * Customize event participant links
   *
   * @param \Civi\API\Event\RespondEvent $e
   * @return void
   * @throws \CRM_Core_Exception
   */
  public static function alterParticipantLinksResult(RespondEvent $e): void {
    $request = $e->getApiRequest();
    if ($request['version'] == 4 && $request->getEntityName() === 'Participant' && is_a($request, '\Civi\Api4\Action\GetLinks')) {
      $links = (array) $e->getResponse();
      $addLinkIndex = self::getActionIndex($links, 'add');
      if (isset($addLinkIndex)) {
        $contactId = $request->getValue('contact_id');
        if ($contactId) {
          if ($request->getExpandMultiple()) {
            // Inject a link to view contact's Event Registration access levels.
            $fpptaregdenyStatusLink = [];
            $fpptaregdenyStatusLink['target'] = 'crm-popup';
            $fpptaregdenyStatusLink['text'] = ts('User can register self?');
            $fpptaregdenyStatusLink['icon'] = 'fa-shield';
            $fpptaregdenyStatusLink['path'] = "civicrm/fpptaregdeny/userstatus?cid=$contactId";
            $links[] = $fpptaregdenyStatusLink;
          }
        }
      }
      $e->getResponse()->exchangeArray(array_values($links));
    }
  }

}
