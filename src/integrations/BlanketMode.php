<?php
/**
 * Altcha plugin for Craft CMS 5.x
 *
 * Blanket-POST mode: verifies any allowlisted front-end action.
 *
 * @link      https://jalendport.com
 * @copyright Copyright (c) 2026 Jalen Davenport
 */

namespace jalendport\altcha\integrations;

use Craft;
use jalendport\altcha\Altcha;
use jalendport\altcha\helpers\ActionAllowlist;
use yii\base\ActionEvent;
use yii\web\BadRequestHttpException;

/**
 * Enforces Altcha on any front-end POST whose action path is listed in the
 * blanket-mode allowlist, whichever controller happens to own it.
 *
 * This is the escape hatch for form plugins that expose no captcha API of their
 * own (Freeform, Campaign, Wheelform): the check hangs off the application's
 * own before-action event, which fires ahead of every controller action in the
 * request, so no cooperation from the host plugin is needed.
 *
 * @author Jalen Davenport <hello@jalendport.com>
 * @since 1.0.0
 */
class BlanketMode
{
    // Static Methods
    // =========================================================================

    /**
     * Verifies the posted Altcha solution before an allowlisted action runs.
     *
     * @param ActionEvent $event the application's before-action event
     * @return void
     * @throws BadRequestHttpException if the solution is missing or invalid
     * @author Jalen Davenport <hello@jalendport.com>
     * @since 1.0.0
     */
    public static function beforeAction(ActionEvent $event): void
    {
        $request = Craft::$app->getRequest();

        // Console requests have no post data at all, and every console command
        // runs through this same event.
        if ($request->getIsConsoleRequest()) {
            return;
        }

        if (!$request->getIsSiteRequest()) {
            return;
        }

        if (!$request->getIsPost()) {
            return;
        }

        // Live preview replays the front end from the control panel, widget and
        // all, so its POSTs can't be held to a fresh challenge.
        if ($request->getIsLivePreview()) {
            return;
        }

        $allowlist = Altcha::$plugin->altcha->getSettings()->blanketActionAllowlist;

        if (!ActionAllowlist::matches($event->action->getUniqueId(), $allowlist)) {
            return;
        }

        $payload = (string)$request->post('altcha');

        if ($payload === '' || !Altcha::$plugin->altcha->verifySolution($payload)) {
            throw new BadRequestHttpException(Craft::t('altcha', 'Altcha verification failed. Please try again.'));
        }
    }
}
