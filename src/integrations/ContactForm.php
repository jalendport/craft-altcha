<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Contact Form integration: verifies contact form submissions before they send.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\integrations;

use Craft;
use craft\contactform\events\SendEvent;
use jalendport\altcha\Altcha;

/**
 * Enforces Altcha on submissions made through Craft's Contact Form plugin.
 *
 * A submission that doesn't check out is flagged as spam rather than rejected,
 * which is Contact Form's own convention: the sender sees the usual success
 * message while the message goes nowhere.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class ContactForm
{
    // Static Methods
    // =========================================================================

    /**
     * Verifies the posted Altcha solution before a contact form message sends.
     *
     * @param SendEvent $event the message's before-send event
     * @return void
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public static function beforeSend(SendEvent $event): void
    {
        $request = Craft::$app->getRequest();

        // Console requests have no post data at all, so a programmatic send
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
