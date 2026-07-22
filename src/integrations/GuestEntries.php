<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Guest Entries integration: verifies guest entry submissions before they save.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\integrations;

use Craft;
use craft\guestentries\events\SaveEvent;
use jalendport\altcha\Altcha;

/**
 * Enforces Altcha on entries submitted through Craft's Guest Entries plugin.
 *
 * A submission that doesn't check out is flagged as spam rather than rejected,
 * which is Guest Entries' own convention: the visitor gets the success response
 * while nothing is saved.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class GuestEntries
{
    // Static Methods
    // =========================================================================

    /**
     * Verifies the posted Altcha solution before a guest entry saves.
     *
     * @param SaveEvent $event the entry's before-save event
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public static function beforeSaveEntry(SaveEvent $event): void
    {
        $request = Craft::$app->getRequest();

        // Console requests have no post data at all, so a programmatic save
        // would otherwise blow up reading the payload.
        if ($request->getIsConsoleRequest()) {
            return;
        }

        $payload = (string)$request->post('altcha');

        if ($payload === '' || !Altcha::$plugin->altcha->verifySolution($payload)) {
            $event->isSpam = true;
        }
    }
}
